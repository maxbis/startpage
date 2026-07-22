// Content-height category expansion and measured CSS-grid masonry layout.
const categoriesContainer = document.getElementById('categories-container');
const mobileCategoryLayout = window.matchMedia('(max-width: 768px)');
const masonryRowHeight = 4;
const masonryGap = 12;
let masonryFrame = null;

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

  categoriesContainer.querySelectorAll(':scope > section[data-category-id]').forEach(section => {
    const content = section.querySelector('.section-content');
    const bookmarkCount = content?.querySelectorAll('.bookmark-item[data-id]').length || 0;
    const hiddenCount = Math.max(0, bookmarkCount - 5);
    let indicator = section.querySelector('.expand-indicator');

    if (!content || hiddenCount === 0) {
      content?.classList.remove('has-expand-control', 'expanded');
      section.classList.remove('overlay-expanded');
      section.querySelector('.expand-control-footer')?.remove();
      return;
    }

    content.classList.add('has-expand-control');
    if (!indicator) indicator = createExpandFooter(section, hiddenCount);
    indicator.dataset.hiddenCount = String(hiddenCount);
    updateExpandControl(section, content.classList.contains('expanded'));
  });

  refreshCategoryMasonry();
}

function measureCategory(section) {
  if (!section) return;
  if (mobileCategoryLayout.matches) {
    section.style.removeProperty('--category-row-span');
    return;
  }

  // Keep the collapsed masonry footprint while the visible card floats above it.
  if (section.classList.contains('overlay-expanded')) return;

  const card = section.querySelector('.category-card');
  if (!card) return;
  const rowSpan = Math.max(1, Math.ceil((card.getBoundingClientRect().height + masonryGap) / masonryRowHeight));
  section.style.setProperty('--category-row-span', rowSpan);
}

function refreshCategoryMasonry() {
  if (!categoriesContainer) return;
  cancelAnimationFrame(masonryFrame);
  masonryFrame = requestAnimationFrame(() => {
    categoriesContainer.querySelectorAll(':scope > section[data-category-id]').forEach(measureCategory);
  });
}

function collapseCategory(section, returnFocus = false) {
  if (!section) return;
  section.querySelector('.section-content')?.classList.remove('expanded');
  section.classList.remove('overlay-expanded');
  updateExpandControl(section, false);
  refreshCategoryMasonry();
  if (returnFocus) section.querySelector('.expand-indicator')?.focus();
}

function expandCategory(section) {
  if (!section) return;

  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    const openSection = content.closest('section[data-category-id]');
    if (openSection !== section) collapseCategory(openSection);
  });

  if (!mobileCategoryLayout.matches) {
    // Capture the collapsed height before taking the card out of normal flow.
    measureCategory(section);
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
    entries.forEach(entry => measureCategory(entry.target.closest('section[data-category-id]')));
  });
  categoriesContainer.querySelectorAll('.category-card').forEach(card => categoryResizeObserver.observe(card));
}

mobileCategoryLayout.addEventListener('change', () => {
  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    collapseCategory(content.closest('section[data-category-id]'));
  });
  refreshCategoryMasonry();
});
window.addEventListener('load', refreshCategoryMasonry);
window.addEventListener('resize', refreshCategoryMasonry);

window.refreshCategoryMasonry = refreshCategoryMasonry;
window.syncCategoryExpandControls = syncCategoryExpandControls;
window.collapseCategory = collapseCategory;

syncCategoryExpandControls();
