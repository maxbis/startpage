/**
 * Header account menu, add-bookmark action, and activity legend.
 */

const accountMenuButton = document.getElementById('accountMenuButton');
const accountMenu = document.getElementById('accountMenu');
const addBookmarkButton = document.getElementById('addBookmarkButton');
const activityLegendModal = document.getElementById('activityLegendModal');
const activityLegendClose = document.getElementById('activityLegendClose');
const aboutModal = document.getElementById('aboutModal');
const aboutModalClose = document.getElementById('aboutModalClose');
let activityLegendReturnFocus = null;
let aboutReturnFocus = null;

function getAccountMenuItems() {
  return accountMenu
    ? Array.from(accountMenu.querySelectorAll('[role="menuitem"]')).filter(item => !item.hidden)
    : [];
}

function closeAccountMenu(options = {}) {
  if (!accountMenu || !accountMenuButton) return;
  window.wpUiState.closeMenu(accountMenu);
  accountMenuButton.setAttribute('aria-expanded', 'false');
  if (options.restoreFocus) accountMenuButton.focus();
}

function openAccountMenu(options = {}) {
  if (!accountMenu || !accountMenuButton) return;
  window.closeBookmarkActionsMenu?.();
  const pageMenu = document.getElementById('pageDropdownMenu');
  const pageButton = document.getElementById('pageDropdown');
  window.wpUiState.closeMenu(pageMenu);
  pageButton?.setAttribute('aria-expanded', 'false');

  window.wpUiState.openMenu(accountMenu);
  accountMenuButton.setAttribute('aria-expanded', 'true');
  if (options.focusFirst) getAccountMenuItems()[0]?.focus();
}

function toggleAccountMenu() {
  if (!accountMenu) return;
  if (!window.wpUiState.isMenuOpen(accountMenu)) {
    openAccountMenu();
  } else {
    closeAccountMenu({ restoreFocus: true });
  }
}

function openActivityLegend() {
  if (!activityLegendModal) return;
  activityLegendReturnFocus = accountMenuButton;
  closeAccountMenu();
  window.wpUiState.openDialog(activityLegendModal);
  activityLegendClose?.focus();
}

function closeActivityLegend() {
  if (!activityLegendModal) return;
  window.wpUiState.closeDialog(activityLegendModal);
  activityLegendReturnFocus?.focus();
  activityLegendReturnFocus = null;
}

function openAboutModal() {
  if (!aboutModal) return;
  aboutReturnFocus = accountMenuButton;
  closeAccountMenu();
  window.wpUiState.openDialog(aboutModal);
  aboutModalClose?.focus();
}

function closeAboutModal() {
  if (!aboutModal) return;
  window.wpUiState.closeDialog(aboutModal);
  aboutReturnFocus?.focus();
  aboutReturnFocus = null;
}

accountMenuButton?.addEventListener('click', (event) => {
  event.preventDefault();
  event.stopPropagation();
  toggleAccountMenu();
});

accountMenuButton?.addEventListener('keydown', (event) => {
  if (event.key === 'ArrowDown') {
    event.preventDefault();
    openAccountMenu({ focusFirst: true });
  }
});

accountMenu?.addEventListener('click', (event) => {
  const item = event.target.closest('[role="menuitem"]');
  if (!item) return;

  const action = item.dataset.accountAction;
  if (action === 'activity') {
    event.preventDefault();
    openActivityLegend();
  } else if (action === 'password') {
    event.preventDefault();
    closeAccountMenu();
    window.openPasswordChangeModal?.();
  } else if (action === 'trash') {
    event.preventDefault();
    closeAccountMenu();
    window.openCategoryTrash?.();
  } else if (action === 'about') {
    event.preventDefault();
    openAboutModal();
  } else {
    closeAccountMenu();
  }
});

accountMenu?.addEventListener('keydown', (event) => {
  const items = getAccountMenuItems();
  const currentIndex = items.indexOf(document.activeElement);

  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault();
    const direction = event.key === 'ArrowDown' ? 1 : -1;
    const nextIndex = (currentIndex + direction + items.length) % items.length;
    items[nextIndex]?.focus();
  } else if (event.key === 'Home' || event.key === 'End') {
    event.preventDefault();
    items[event.key === 'Home' ? 0 : items.length - 1]?.focus();
  } else if (event.key === 'Escape') {
    event.preventDefault();
    closeAccountMenu({ restoreFocus: true });
  }
});

addBookmarkButton?.addEventListener('click', () => {
  closeAccountMenu();
  window.openQuickAddModal?.();
});

activityLegendClose?.addEventListener('click', closeActivityLegend);
activityLegendModal?.addEventListener('click', (event) => {
  if (event.target === activityLegendModal) closeActivityLegend();
});
aboutModalClose?.addEventListener('click', closeAboutModal);
aboutModal?.addEventListener('click', (event) => {
  if (event.target === aboutModal) closeAboutModal();
});

document.addEventListener('click', (event) => {
  if (!window.wpUiState.isMenuOpen(accountMenu)) return;
  if (!accountMenu.contains(event.target) && !accountMenuButton?.contains(event.target)) {
    closeAccountMenu();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  if (window.wpUiState.isDialogOpen(activityLegendModal)) {
    event.preventDefault();
    closeActivityLegend();
  } else if (window.wpUiState.isDialogOpen(aboutModal)) {
    event.preventDefault();
    closeAboutModal();
  } else if (window.wpUiState.isMenuOpen(accountMenu)) {
    event.preventDefault();
    closeAccountMenu({ restoreFocus: true });
  }
});

window.closeAccountMenu = closeAccountMenu;
window.openAccountMenu = openAccountMenu;
window.openActivityLegend = openActivityLegend;
window.openAboutModal = openAboutModal;
