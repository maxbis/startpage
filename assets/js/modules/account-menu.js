/**
 * Header account menu, add-bookmark action, and activity legend.
 */

const accountMenuButton = document.getElementById('accountMenuButton');
const accountMenu = document.getElementById('accountMenu');
const addBookmarkButton = document.getElementById('addBookmarkButton');
const activityLegendModal = document.getElementById('activityLegendModal');
const activityLegendClose = document.getElementById('activityLegendClose');
let activityLegendReturnFocus = null;

function getAccountMenuItems() {
  return accountMenu
    ? Array.from(accountMenu.querySelectorAll('[role="menuitem"]')).filter(item => !item.hidden)
    : [];
}

function closeAccountMenu(options = {}) {
  if (!accountMenu || !accountMenuButton) return;
  accountMenu.classList.add('hidden');
  accountMenuButton.setAttribute('aria-expanded', 'false');
  if (options.restoreFocus) accountMenuButton.focus();
}

function openAccountMenu(options = {}) {
  if (!accountMenu || !accountMenuButton) return;
  window.closeBookmarkActionsMenu?.();
  const pageMenu = document.getElementById('pageDropdownMenu');
  const pageButton = document.getElementById('pageDropdown');
  pageMenu?.classList.add('hidden');
  pageButton?.setAttribute('aria-expanded', 'false');

  accountMenu.classList.remove('hidden');
  accountMenuButton.setAttribute('aria-expanded', 'true');
  if (options.focusFirst) getAccountMenuItems()[0]?.focus();
}

function toggleAccountMenu() {
  if (!accountMenu) return;
  if (accountMenu.classList.contains('hidden')) {
    openAccountMenu();
  } else {
    closeAccountMenu({ restoreFocus: true });
  }
}

function openActivityLegend() {
  if (!activityLegendModal) return;
  activityLegendReturnFocus = accountMenuButton;
  closeAccountMenu();
  activityLegendModal.classList.remove('hidden');
  activityLegendModal.classList.add('flex');
  activityLegendClose?.focus();
}

function closeActivityLegend() {
  if (!activityLegendModal) return;
  activityLegendModal.classList.add('hidden');
  activityLegendModal.classList.remove('flex');
  activityLegendReturnFocus?.focus();
  activityLegendReturnFocus = null;
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

document.addEventListener('click', (event) => {
  if (!accountMenu || accountMenu.classList.contains('hidden')) return;
  if (!accountMenu.contains(event.target) && !accountMenuButton?.contains(event.target)) {
    closeAccountMenu();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  if (activityLegendModal && !activityLegendModal.classList.contains('hidden')) {
    event.preventDefault();
    closeActivityLegend();
  } else if (accountMenu && !accountMenu.classList.contains('hidden')) {
    event.preventDefault();
    closeAccountMenu({ restoreFocus: true });
  }
});

window.closeAccountMenu = closeAccountMenu;
window.openAccountMenu = openAccountMenu;
window.openActivityLegend = openActivityLegend;
