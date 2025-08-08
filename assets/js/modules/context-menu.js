// --- Right-click Context Menu ---
document.addEventListener('contextmenu', (e) => {
  // Only show context menu if clicking on the main container or empty space
  const target = e.target;
  const isOnCategory = target.closest('section[data-category-id]');
  const isOnBookmark = target.closest('li[data-id]');
  const isOnForm = target.closest('form');
  const isOnButton = target.closest('button');
  
  // Don't show context menu on categories, bookmarks, forms, or buttons
  if (isOnCategory || isOnBookmark || isOnForm || isOnButton) {
    return;
  }
  
  e.preventDefault();
  showContextMenu(e.clientX, e.clientY);
});

// Hide context menu when clicking elsewhere
document.addEventListener('click', (e) => {
  if (!contextMenu.contains(e.target)) {
    hideContextMenu();
  }
});

// Hide context menu when pressing Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    hideContextMenu();
  }
});
