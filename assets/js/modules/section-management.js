// Section expand/collapse functionality
const expandIndicators = document.querySelectorAll('.expand-indicator');
const desktopCategoryLayout = window.matchMedia('(min-width: 769px)');

function collapseCategory(section, returnFocus = false) {
  if (!section) return;

  const content = section.querySelector('.section-content');
  const indicator = section.querySelector('.expand-indicator');

  content?.classList.remove('expanded');
  indicator?.classList.remove('expanded');
  indicator?.setAttribute('aria-expanded', 'false');
  const categoryName = section.querySelector('h2')?.textContent.trim() || 'category';
  indicator?.setAttribute('aria-label', `Show more bookmarks in ${categoryName}`);
  indicator?.setAttribute('title', `Show more bookmarks in ${categoryName}`);
  section.classList.remove('overlay-expanded');
  section.style.height = '';

  if (returnFocus) indicator?.focus();
}

function expandCategory(section, indicator) {
  // Only one category can cover the grid at a time.
  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    const openSection = content.closest('section[data-category-id]');
    if (openSection !== section) collapseCategory(openSection);
  });

  const content = section.querySelector('.section-content');

  if (desktopCategoryLayout.matches) {
    // Preserve the exact space occupied by the collapsed card before floating it.
    section.style.height = `${section.getBoundingClientRect().height}px`;
    section.classList.add('overlay-expanded');
  }

  content.classList.add('expanded');
  indicator.classList.add('expanded');
  indicator.setAttribute('aria-expanded', 'true');
  const categoryName = section.querySelector('h2')?.textContent.trim() || 'category';
  indicator.setAttribute('aria-label', `Show fewer bookmarks in ${categoryName}`);
  indicator.setAttribute('title', `Show fewer bookmarks in ${categoryName}`);
}

expandIndicators.forEach(indicator => {
  DEBUG.log('SECTION', 'Adding click listener to indicator:', indicator.dataset.sectionId);

  indicator.addEventListener('click', event => {
    event.stopPropagation(); // Prevent category drag and outside-click handling.

    const section = indicator.closest('section[data-category-id]');
    const content = section?.querySelector('.section-content');
    if (!section || !content) return;

    if (content.classList.contains('expanded')) {
      DEBUG.log('SECTION', 'Collapsing section...');
      collapseCategory(section);
    } else {
      DEBUG.log('SECTION', 'Expanding section...');
      expandCategory(section, indicator);
    }
  });
});

// Clicking elsewhere dismisses the floating category.
document.addEventListener('click', event => {
  document.querySelectorAll('section[data-category-id].overlay-expanded').forEach(section => {
    if (!section.contains(event.target)) collapseCategory(section);
  });
});

// Escape closes the open category and returns focus to its toggle.
document.addEventListener('keydown', event => {
  if (event.key !== 'Escape') return;

  const section = document.querySelector('section[data-category-id] .section-content.expanded')
    ?.closest('section[data-category-id]');
  if (section) collapseCategory(section, true);
});

// Reset open categories when crossing the mobile/desktop layout breakpoint.
desktopCategoryLayout.addEventListener('change', () => {
  document.querySelectorAll('section[data-category-id] .section-content.expanded').forEach(content => {
    collapseCategory(content.closest('section[data-category-id]'));
  });
});
