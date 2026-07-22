// Height-balanced, column-major category layout and category expansion.
const categoriesContainer = document.getElementById('categories-container');
const mobileCategoryLayout = window.matchMedia('(max-width: 768px)');
const categoryGap = 12;
const maximumCategoryColumns = 6;
let categoryLayoutFrame = null;
let categoryLayoutFrozen = false;

function getCategorySections() {
  if (!categoriesContainer) return [];
  return Array.from(categoriesContainer.querySelectorAll('section[data-category-id]'));
}

function updateExpandControl(section, expanded) {
  const indicator = section?.querySelector('.expand-indicator');
  if (!indicator) return;

  const hiddenCount = parseInt(indicator.dataset.hiddenCount || '0', 10);
  const categoryName = section.querySelector('h2')?.textContent.trim() || 'category';
  const label = indicator.querySelector('.expand-indicator-label');

  indicator.classList.toggle('expanded', expanded);
  indicator.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  indicator.setAttribute(
    'aria-label',
    expanded
      ? `Show fewer bookmarks in ${categoryName}`
      : `Show ${hiddenCount} more bookmarks in ${categoryName}`
  );
  if (label) label.textContent = expanded ? 'Show less' : `Show ${hiddenCount} more`;
}

function createExpandFooter(section, hiddenCount) {
  const footer = document.createElement('div');
  footer.className = 'expand-control-footer';

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'expand-indicator';
  button.dataset.sectionId = section.dataset.categoryId;
  button.dataset.hiddenCount = String(hiddenCount);
  button.setAttribute('aria-controls', `category-content-${section.dataset.categoryId}`);
  button.setAttribute('aria-expanded', 'false');
  button.innerHTML = `
    <span class="expand-indicator-label"></span>
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
    </svg>
  `;
  footer.appendChild(button);
  section.querySelector('.category-card')?.appendChild(footer);
  return button;
}

function syncCategoryExpandControls() {
  if (!categoriesContainer) return;

  getCategorySections().forEach(section => {
    const content = section.querySelector('.section-content');
    const bookmarkCount = content?.querySelectorAll('.bookmark-item[data-id]').length || 0;
    const hiddenCount = Math.max(0, bookmarkCount - 5);
    let indicator = section.querySelector('.expand-indicator');

    if (!content || hiddenCount === 0) {
      content?.classList.remove('has-expand-control', 'expanded');
      section.classList.remove('overlay-expanded');
      section.style.height = '';
      section.querySelector('.expand-control-footer')?.remove();
      return;
    }

    content.classList.add('has-expand-control');
    if (!indicator) indicator = createExpandFooter(section, hiddenCount);
    indicator.dataset.hiddenCount = String(hiddenCount);
    updateExpandControl(section, content.classList.contains('expanded'));
  });

  scheduleCategoryLayout();
}

function getCategoryWidth(section) {
  const configuredWidth = parseFloat(getComputedStyle(section).getPropertyValue('--category-width'));
  return Number.isFinite(configuredWidth) && configuredWidth > 0
    ? configuredWidth
    : section.getBoundingClientRect().width;
}

function getCollapsedCategoryHeight(section) {
  if (!section.classList.contains('overlay-expanded') && !section.querySelector('.section-content.expanded')) {
    const measuredHeight = section.getBoundingClientRect().height;
    if (measuredHeight > 0) section.dataset.collapsedHeight = String(measuredHeight);
  }

  return parseFloat(section.dataset.collapsedHeight || '0') || section.getBoundingClientRect().height || 1;
}

// Split an ordered list into contiguous groups while minimizing the tallest column.
function partitionByHeight(items, requestedColumnCount) {
  const itemCount = items.length;
  const columnCount = Math.max(1, Math.min(requestedColumnCount, itemCount));
  if (columnCount === 1) return [items.slice()];

  const prefixHeights = [0];
  items.forEach(item => prefixHeights.push(prefixHeights[prefixHeights.length - 1] + item.height + categoryGap));

  const costs = Array.from({ length: columnCount + 1 }, () => Array(itemCount + 1).fill(Infinity));
  const splits = Array.from({ length: columnCount + 1 }, () => Array(itemCount + 1).fill(0));
  costs[0][0] = 0;

  for (let columns = 1; columns <= columnCount; columns += 1) {
    for (let end = columns; end <= itemCount; end += 1) {
      for (let start = columns - 1; start < end; start += 1) {
        const groupHeight = prefixHeights[end] - prefixHeights[start];
        const cost = Math.max(costs[columns - 1][start], groupHeight);
        if (cost < costs[columns][end]) {
          costs[columns][end] = cost;
          splits[columns][end] = start;
        }
      }
    }
  }

  const groups = [];
  let end = itemCount;
  for (let columns = columnCount; columns > 0; columns -= 1) {
    const start = splits[columns][end];
    groups.unshift(items.slice(start, end));
    end = start;
  }
  return groups;
}

function getRequiredLayoutWidth(groups) {
  const columnWidths = groups.map(group => Math.max(...group.map(item => item.width)));
  return columnWidths.reduce((total, width) => total + width, 0) + categoryGap * Math.max(0, groups.length - 1);
}

function chooseCategoryGroups(items) {
  if (mobileCategoryLayout.matches || items.length < 2) return [items];

  const availableWidth = categoriesContainer.clientWidth;
  const maximumColumns = Math.min(maximumCategoryColumns, items.length);
  for (let columnCount = maximumColumns; columnCount >= 2; columnCount -= 1) {
    const groups = partitionByHeight(items, columnCount);
    if (getRequiredLayoutWidth(groups) <= availableWidth + 1) return groups;
  }

  return [items];
}

function ensureCategoryColumns(columnCount) {
  let columns = Array.from(categoriesContainer.querySelectorAll(':scope > .category-column'));

  while (columns.length < columnCount) {
    const column = document.createElement('div');
    column.className = 'category-column';
    categoriesContainer.appendChild(column);
    columns.push(column);
  }

  return columns;
}

function rebalanceCategoryColumns(force = false) {
  if (!categoriesContainer || (categoryLayoutFrozen && !force)) return;
  if (categoriesContainer.querySelector('.overlay-expanded')) return;

  const sections = getCategorySections();
  if (sections.length === 0) return;

  const items = sections.map(section => ({
    section,
    height: getCollapsedCategoryHeight(section),
    width: getCategoryWidth(section)
  }));
  const groups = chooseCategoryGroups(items);
  const columns = ensureCategoryColumns(groups.length);

  groups.forEach((group, columnIndex) => {
    const column = columns[columnIndex];
    column.dataset.categoryColumn = String(columnIndex);
    column.style.width = `${Math.max(...group.map(item => item.width))}px`;
    group.forEach(item => column.appendChild(item.section));
  });

  columns.slice(groups.length).forEach(column => {
    column._categorySortable?.destroy();
    column.remove();
  });

  categoriesContainer.dataset.layoutReady = 'true';
  document.dispatchEvent(new CustomEvent('category-columns-changed'));
}

function scheduleCategoryLayout() {
  if (!categoriesContainer || categoryLayoutFrozen) return;
  cancelAnimationFrame(categoryLayoutFrame);
  categoryLayoutFrame = requestAnimationFrame(() => rebalanceCategoryColumns());
}

function setCategoryLayoutFrozen(frozen) {
  categoryLayoutFrozen = Boolean(frozen);
}

function collapseCategory(section, returnFocus = false) {
  if (!section) return;
  section.querySelector('.section-content')?.classList.remove('expanded');
  section.classList.remove('overlay-expanded');
  section.style.height = '';
  updateExpandControl(section, false);
  scheduleCategoryLayout();
  if (returnFocus) section.querySelector('.expand-indicator')?.focus();
}

function expandCategory(section) {
  if (!section) return;

  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    const openSection = content.closest('section[data-category-id]');
    if (openSection !== section) collapseCategory(openSection);
  });

  if (!mobileCategoryLayout.matches) {
    const collapsedHeight = section.getBoundingClientRect().height;
    section.dataset.collapsedHeight = String(collapsedHeight);
    section.style.height = `${collapsedHeight}px`;
    section.classList.add('overlay-expanded');
  }

  section.querySelector('.section-content')?.classList.add('expanded');
  updateExpandControl(section, true);
}

categoriesContainer?.addEventListener('click', event => {
  const indicator = event.target.closest('.expand-indicator');
  if (!indicator) return;
  event.stopPropagation();
  const section = indicator.closest('section[data-category-id]');
  const content = section?.querySelector('.section-content');
  if (!section || !content) return;

  if (content.classList.contains('expanded')) {
    collapseCategory(section);
  } else {
    expandCategory(section);
  }
});

document.addEventListener('click', event => {
  document.querySelectorAll('section[data-category-id].overlay-expanded').forEach(section => {
    if (!section.contains(event.target)) collapseCategory(section);
  });
});

document.addEventListener('keydown', event => {
  if (event.key !== 'Escape') return;
  const focusedSection = document.activeElement?.closest?.('section[data-category-id]');
  const section = focusedSection?.querySelector('.section-content.expanded')
    ? focusedSection
    : document.querySelector('section[data-category-id] .section-content.expanded')?.closest('section[data-category-id]');
  if (section) collapseCategory(section, true);
});

if (categoriesContainer && 'ResizeObserver' in window) {
  const categoryResizeObserver = new ResizeObserver(entries => {
    const collapsedCardChanged = entries.some(entry => {
      const section = entry.target.closest('section[data-category-id]');
      return section && !section.classList.contains('overlay-expanded') && !section.querySelector('.section-content.expanded');
    });
    if (collapsedCardChanged) scheduleCategoryLayout();
  });
  getCategorySections().forEach(section => {
    const card = section.querySelector('.category-card');
    if (card) categoryResizeObserver.observe(card);
  });
}

mobileCategoryLayout.addEventListener('change', () => {
  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    collapseCategory(content.closest('section[data-category-id]'));
  });
  scheduleCategoryLayout();
});
window.addEventListener('load', scheduleCategoryLayout);
window.addEventListener('resize', scheduleCategoryLayout);

window.rebalanceCategoryColumns = rebalanceCategoryColumns;
window.refreshCategoryMasonry = scheduleCategoryLayout;
window.setCategoryLayoutFrozen = setCategoryLayoutFrozen;
window.syncCategoryExpandControls = syncCategoryExpandControls;
window.collapseCategory = collapseCategory;

syncCategoryExpandControls();
