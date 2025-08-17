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

// --- Mobile-friendly Long-press Context Menu ---
let longPressTimer;
let longPressTarget;

// Handle touch start for long-press detection
document.addEventListener('touchstart', (e) => {
  if (window.isMobile && window.isMobile()) {
    const target = e.target;
    const isOnCategory = target.closest('section[data-category-id]');
    const isOnBookmark = target.closest('li[data-id]');
    const isOnForm = target.closest('form');
    const isOnButton = target.closest('button');
    
    // Don't show context menu on categories, bookmarks, forms, or buttons
    if (isOnCategory || isOnBookmark || isOnForm || isOnButton) {
      return;
    }
    
    longPressTarget = target;
    longPressTimer = setTimeout(() => {
      // Show mobile context menu
      const touch = e.touches[0];
      showMobileContextMenu(touch.clientX, touch.clientY);
    }, 800); // 800ms long press
  }
});

// Handle touch end to cancel long press
document.addEventListener('touchend', (e) => {
  if (longPressTimer) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
  }
});

// Handle touch move to cancel long press
document.addEventListener('touchmove', (e) => {
  if (longPressTimer) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
  }
});

// Show mobile-friendly context menu
function showMobileContextMenu(x, y) {
  // Remove existing mobile menu if present
  hideMobileContextMenu();
  
  // Create a mobile-optimized context menu
  const mobileMenu = document.createElement('div');
  mobileMenu.id = 'mobileContextMenu';
  mobileMenu.className = 'fixed z-50 bg-white border border-gray-300 rounded-lg shadow-lg p-2 min-w-48';
  
  // Add to DOM first to get dimensions
  document.body.appendChild(mobileMenu);
  
  // Get menu dimensions
  const rect = mobileMenu.getBoundingClientRect();
  const menuWidth = rect.width;
  const menuHeight = rect.height;
  
  // Get viewport dimensions
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  
  // Determine screen quadrant and position menu accordingly
  let finalX, finalY;
  
  // Check if tap is in upper or lower half
  const isUpperHalf = y < viewportHeight / 2;
  // Check if tap is in left or right half
  const isLeftHalf = x < viewportWidth / 2;
  
  if (isUpperHalf) {
    // Upper half - align top of menu to tap
    finalY = y;
  } else {
    // Lower half - align bottom of menu to tap
    finalY = y - menuHeight;
  }
  
  if (isLeftHalf) {
    // Left half - align left of menu to tap
    finalX = x;
  } else {
    // Right half - align right of menu to tap
    finalX = x - menuWidth;
  }
  
  // Ensure menu stays within viewport bounds
  if (finalX < 0) finalX = 10;
  if (finalY < 0) finalY = 10;
  if (finalX + menuWidth > viewportWidth) finalX = viewportWidth - menuWidth - 10;
  if (finalY + menuHeight > viewportHeight) finalY = viewportHeight - menuHeight - 10;
  
  // Apply positioning
  mobileMenu.style.left = `${finalX}px`;
  mobileMenu.style.top = `${finalY}px`;
  
  mobileMenu.innerHTML = `
    <div class="text-sm font-medium text-gray-700 mb-2 px-2 py-1 border-b border-gray-200">
      📱 Quick Actions
    </div>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="showAddBookmarkModal()">
      ➕ Add Bookmark
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="showAddCategoryModal()">
      📁 Add Category
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="showAddPageModal()">
      📄 Add Page
    </button>
    <div class="border-t border-gray-200 mt-2 pt-2">
      <button class="w-full text-left px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 rounded flex items-center gap-2" onclick="hideMobileContextMenu()">
        ❌ Close
      </button>
    </div>
  `;
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideMobileContextMenu();
  }, 5000);
}

// Hide mobile context menu
function hideMobileContextMenu() {
  const mobileMenu = document.getElementById('mobileContextMenu');
  if (mobileMenu) {
    mobileMenu.remove();
  }
}

// Hide context menu when clicking elsewhere
document.addEventListener('click', (e) => {
  const contextMenu = document.getElementById('contextMenu');
  if (contextMenu && !contextMenu.contains(e.target)) {
    // Call hideContextMenu from modal-management.js
    if (window.hideContextMenu) {
      window.hideContextMenu();
    }
  }
  // Also hide mobile context menu
  hideMobileContextMenu();
});

// Hide context menu when pressing Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    // Call hideContextMenu from modal-management.js
    if (window.hideContextMenu) {
      window.hideContextMenu();
    }
    hideMobileContextMenu();
  }
});

// Export mobile context menu functions
window.showMobileContextMenu = showMobileContextMenu;
window.hideMobileContextMenu = hideMobileContextMenu;
