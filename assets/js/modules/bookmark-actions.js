/**
 * Bookmark activity and actions
 * Renders the four-segment usage meter and exposes bookmark actions without a
 * permanent edit icon.
 */

const BOOKMARK_ACTIVITY_SEGMENTS = {
  recent: 4,
  fortnight: 3,
  normal: 2,
  stale: 1,
};

let activeBookmarkMenu = null;
let activeBookmarkTrigger = null;
let bookmarkLongPressTimer = null;
let suppressBookmarkClickUntil = 0;

function parseBookmarkTimestamp(value) {
  if (!value) return null;
  const parsed = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatBookmarkUsage(state, lastClickedAt) {
  const clickedAt = parseBookmarkTimestamp(lastClickedAt);
  if (!clickedAt) return 'Never used';

  const elapsedMs = Math.max(0, Date.now() - clickedAt.getTime());
  const elapsedMinutes = Math.floor(elapsedMs / 60000);
  const elapsedHours = Math.floor(elapsedMs / 3600000);
  const elapsedDays = Math.floor(elapsedMs / 86400000);

  if (elapsedMinutes < 2) return 'Used just now';
  if (elapsedHours < 1) return `Used ${elapsedMinutes} minutes ago`;
  if (elapsedHours < 24) return `Used ${elapsedHours} hour${elapsedHours === 1 ? '' : 's'} ago`;
  if (elapsedDays < 60) return `Used ${elapsedDays} day${elapsedDays === 1 ? '' : 's'} ago`;

  const exactDate = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(clickedAt);
  return state === 'stale' ? `Last used ${exactDate}` : `Used ${exactDate}`;
}

function updateBookmarkActivity(bookmarkElement, state, lastClickedAt) {
  if (!bookmarkElement) return;

  const normalizedState = Object.hasOwn(BOOKMARK_ACTIVITY_SEGMENTS, state) ? state : 'normal';
  const button = bookmarkElement.querySelector('.bookmark-activity-button');
  if (!button) return;

  bookmarkElement.dataset.usageState = normalizedState;
  if (typeof lastClickedAt !== 'undefined') {
    bookmarkElement.dataset.lastClickedAt = lastClickedAt || '';
  }
  button.dataset.usageState = normalizedState;

  const filledCount = BOOKMARK_ACTIVITY_SEGMENTS[normalizedState];
  button.querySelectorAll('.bookmark-activity-segment').forEach((segment, index) => {
    segment.classList.toggle('is-filled', index < filledCount);
  });

  const usageText = formatBookmarkUsage(normalizedState, bookmarkElement.dataset.lastClickedAt);
  button.setAttribute('aria-label', `${usageText}. Bookmark actions`);
  button.title = `${usageText} — bookmark actions`;
}

function closeBookmarkActionsMenu(options = {}) {
  if (!activeBookmarkMenu) return;

  activeBookmarkMenu.remove();
  activeBookmarkMenu = null;

  if (activeBookmarkTrigger) {
    activeBookmarkTrigger.setAttribute('aria-expanded', 'false');
    if (options.restoreFocus) activeBookmarkTrigger.focus();
  }
  activeBookmarkTrigger = null;
}

function createBookmarkAction(label, action, icon, danger = false) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = `bookmark-actions-item${danger ? ' is-danger' : ''}`;
  button.dataset.bookmarkAction = action;
  button.setAttribute('role', 'menuitem');

  const iconElement = document.createElement('span');
  iconElement.setAttribute('aria-hidden', 'true');
  iconElement.textContent = icon;

  const labelElement = document.createElement('span');
  labelElement.textContent = label;

  button.append(iconElement, labelElement);
  return button;
}

function positionBookmarkActionsMenu(menu, anchor, point = null) {
  const margin = 10;
  const menuRect = menu.getBoundingClientRect();
  const anchorRect = anchor.getBoundingClientRect();
  let left = point ? point.x : anchorRect.right - menuRect.width;
  let top = point ? point.y : anchorRect.bottom + 5;

  if (left + menuRect.width > window.innerWidth - margin) {
    left = window.innerWidth - menuRect.width - margin;
  }
  if (left < margin) left = margin;

  if (top + menuRect.height > window.innerHeight - margin) {
    top = (point ? point.y : anchorRect.top) - menuRect.height - 5;
  }
  if (top < margin) top = margin;

  menu.style.left = `${left}px`;
  menu.style.top = `${top}px`;
}

function showBookmarkActionsMenu(bookmarkElement, trigger = null, point = null) {
  if (!bookmarkElement) return;
  closeBookmarkActionsMenu();

  const activityButton = bookmarkElement.querySelector('.bookmark-activity-button');
  const anchor = trigger || activityButton || bookmarkElement;
  const menu = document.createElement('div');
  menu.className = 'bookmark-actions-menu';
  menu.setAttribute('role', 'menu');
  menu.setAttribute('aria-label', `Actions for ${bookmarkElement.dataset.title || 'bookmark'}`);

  const summary = document.createElement('div');
  summary.className = 'bookmark-actions-summary';

  const title = document.createElement('div');
  title.className = 'bookmark-actions-title';
  title.textContent = bookmarkElement.dataset.title || 'Bookmark';

  const usage = document.createElement('div');
  usage.className = 'bookmark-actions-usage';
  usage.textContent = formatBookmarkUsage(
    bookmarkElement.dataset.usageState,
    bookmarkElement.dataset.lastClickedAt
  );
  summary.append(title, usage);

  menu.append(
    summary,
    createBookmarkAction('Edit bookmark', 'edit', '✎'),
    createBookmarkAction('Open bookmark', 'open', '↗'),
    createBookmarkAction('Copy URL', 'copy', '⧉')
  );

  const separator = document.createElement('div');
  separator.className = 'bookmark-actions-separator';
  separator.setAttribute('role', 'separator');
  menu.append(separator, createBookmarkAction('Delete bookmark', 'delete', '×', true));

  menu.addEventListener('click', async (event) => {
    const actionButton = event.target.closest('[data-bookmark-action]');
    if (!actionButton) return;

    const action = actionButton.dataset.bookmarkAction;
    const id = bookmarkElement.dataset.id;
    const bookmarkTitle = bookmarkElement.dataset.title || 'Bookmark';
    const url = bookmarkElement.dataset.url || '';

    if (action === 'edit') {
      closeBookmarkActionsMenu();
      window.openBookmarkEditor?.(bookmarkElement);
    } else if (action === 'open') {
      closeBookmarkActionsMenu();
      window.trackBookmarkClick?.(id, bookmarkElement);
      window.open(url, '_blank', 'noopener');
    } else if (action === 'copy') {
      try {
        await navigator.clipboard.writeText(url);
        window.showFlashMessage?.('Bookmark URL copied', 'success');
      } catch (error) {
        window.showFlashMessage?.('Could not copy the bookmark URL', 'error');
      }
      closeBookmarkActionsMenu();
    } else if (action === 'delete') {
      closeBookmarkActionsMenu();
      window.openDeleteModal?.(id, bookmarkTitle, 'bookmark');
    }
  });

  menu.addEventListener('keydown', (event) => {
    const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
    const currentIndex = items.indexOf(document.activeElement);
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const direction = event.key === 'ArrowDown' ? 1 : -1;
      const nextIndex = (currentIndex + direction + items.length) % items.length;
      items[nextIndex].focus();
    } else if (event.key === 'Home' || event.key === 'End') {
      event.preventDefault();
      items[event.key === 'Home' ? 0 : items.length - 1].focus();
    } else if (event.key === 'Escape') {
      event.preventDefault();
      closeBookmarkActionsMenu({ restoreFocus: true });
    }
  });

  document.body.appendChild(menu);
  positionBookmarkActionsMenu(menu, anchor, point);
  activeBookmarkMenu = menu;
  activeBookmarkTrigger = activityButton;
  activityButton?.setAttribute('aria-expanded', 'true');
  menu.querySelector('[role="menuitem"]')?.focus();
}

document.addEventListener('click', (event) => {
  if (Date.now() < suppressBookmarkClickUntil && event.target.closest('.bookmark-item')) {
    event.preventDefault();
    event.stopImmediatePropagation();
    return;
  }

  const activityButton = event.target.closest('.bookmark-activity-button');
  if (activityButton) {
    event.preventDefault();
    event.stopPropagation();
    const bookmark = activityButton.closest('.bookmark-item');
    if (activeBookmarkMenu && activeBookmarkTrigger === activityButton) {
      closeBookmarkActionsMenu({ restoreFocus: true });
    } else {
      showBookmarkActionsMenu(bookmark, activityButton);
    }
    return;
  }

  if (activeBookmarkMenu && !event.target.closest('.bookmark-actions-menu')) {
    closeBookmarkActionsMenu();
  }
});

document.addEventListener('contextmenu', (event) => {
  const bookmark = event.target.closest('.bookmark-item');
  if (!bookmark) return;
  event.preventDefault();
  event.stopImmediatePropagation();
  showBookmarkActionsMenu(bookmark, bookmark.querySelector('.bookmark-activity-button'), {
    x: event.clientX,
    y: event.clientY,
  });
});

document.addEventListener('keydown', (event) => {
  const bookmark = event.target.closest('.bookmark-item');
  if (!bookmark) return;
  if ((event.shiftKey && event.key === 'F10') || event.key === 'ContextMenu') {
    event.preventDefault();
    showBookmarkActionsMenu(bookmark, bookmark.querySelector('.bookmark-activity-button'));
  }
});

document.addEventListener('touchstart', (event) => {
  const bookmark = event.target.closest('.bookmark-item');
  if (!bookmark || event.target.closest('button')) return;
  clearTimeout(bookmarkLongPressTimer);
  bookmarkLongPressTimer = setTimeout(() => {
    suppressBookmarkClickUntil = Date.now() + 700;
    showBookmarkActionsMenu(bookmark, bookmark.querySelector('.bookmark-activity-button'));
  }, 650);
}, { passive: true });

['touchend', 'touchcancel', 'touchmove'].forEach((eventName) => {
  document.addEventListener(eventName, () => {
    clearTimeout(bookmarkLongPressTimer);
    bookmarkLongPressTimer = null;
  }, { passive: true });
});

window.addEventListener('resize', () => closeBookmarkActionsMenu());
window.addEventListener('scroll', () => closeBookmarkActionsMenu(), true);

window.updateBookmarkActivity = updateBookmarkActivity;
window.showBookmarkActionsMenu = showBookmarkActionsMenu;
window.closeBookmarkActionsMenu = closeBookmarkActionsMenu;

document.querySelectorAll('.bookmark-item').forEach((bookmark) => {
  updateBookmarkActivity(bookmark, bookmark.dataset.usageState, bookmark.dataset.lastClickedAt);
});
