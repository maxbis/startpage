// --- Right-click Context Menu ---
document.addEventListener('contextmenu', (e) => {
  const target = e.target;
  const isOnCategory = target.closest('section[data-category-id]');
  const isOnBookmark = target.closest('li[data-id]');
  const isOnForm = target.closest('form');
  const isOnButton = target.closest('button');
  
  // Don't show context menu on bookmarks, forms, or buttons
  if (isOnBookmark || isOnForm || isOnButton) {
    return;
  }
  
  // Always prevent default context menu for categories and empty space
  e.preventDefault();
  e.stopPropagation();
  
  if (isOnCategory) {
    // Show category-specific context menu
    const categoryId = isOnCategory.getAttribute('data-category-id');
    const categoryName = isOnCategory.querySelector('h2')?.textContent?.trim() || 'Category';
    const categoryData = isOnCategory.querySelector('h2')?.dataset;
    showCategoryContextMenu(e.clientX, e.clientY, categoryId, categoryName, categoryData);
  } else {
    // Show general context menu for empty space
    showContextMenu(e.clientX, e.clientY);
  }
});

// Additional event listener specifically for category sections to ensure it works
document.addEventListener('DOMContentLoaded', () => {
  // Add context menu listeners to all category sections
  const categorySections = document.querySelectorAll('section[data-category-id]');
  
  categorySections.forEach(section => {
    const categoryId = section.getAttribute('data-category-id');
    
    section.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      const categoryName = section.querySelector('h2')?.textContent?.trim() || 'Category';
      const categoryData = section.querySelector('h2')?.dataset;
      
      try {
        showCategoryContextMenu(e.clientX, e.clientY, categoryId, categoryName, categoryData);
      } catch (error) {
        console.error('Error showing category context menu:', error);
      }
    });
  });
  
  // Also set up a global context menu listener as backup
  document.addEventListener('contextmenu', (e) => {
    const target = e.target;
    const isOnCategory = target.closest('section[data-category-id]');
    
    if (isOnCategory) {
      e.preventDefault();
      e.stopPropagation();
      
      const categoryId = isOnCategory.getAttribute('data-category-id');
      const categoryName = isOnCategory.querySelector('h2')?.textContent?.trim() || 'Category';
      const categoryData = isOnCategory.querySelector('h2')?.dataset;
      
      try {
        showCategoryContextMenu(e.clientX, e.clientY, categoryId, categoryName, categoryData);
      } catch (error) {
        console.error('Error showing category context menu from global listener:', error);
      }
    }
  });
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
    
    // Don't show context menu on bookmarks, forms, or buttons
    if (isOnBookmark || isOnForm || isOnButton) {
      return;
    }
    
    longPressTarget = target;
    longPressTimer = setTimeout(() => {
      // Show mobile context menu
      const touch = e.touches[0];
      if (isOnCategory) {
        const categoryId = isOnCategory.getAttribute('data-category-id');
        const categoryName = isOnCategory.querySelector('h2')?.textContent?.trim() || 'Category';
        const categoryData = isOnCategory.querySelector('h2')?.dataset;
        showMobileCategoryContextMenu(touch.clientX, touch.clientY, categoryId, categoryName, categoryData);
      } else {
        showMobileContextMenu(touch.clientX, touch.clientY);
      }
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

// Show category-specific context menu
function showCategoryContextMenu(x, y, categoryId, categoryName, categoryData) {
  // Remove existing context menu if present
  if (window.hideContextMenu) {
    window.hideContextMenu();
  }
  
  // Create category context menu
  const categoryMenu = document.createElement('div');
  categoryMenu.id = 'categoryContextMenu';
  categoryMenu.className = 'fixed z-50 bg-white border border-gray-300 rounded-lg shadow-lg p-2 min-w-48';
  
  // Add to DOM first to get dimensions
  document.body.appendChild(categoryMenu);
  
  // Get menu dimensions
  const rect = categoryMenu.getBoundingClientRect();
  const menuWidth = rect.width;
  const menuHeight = rect.height;
  
  // Get viewport dimensions
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  
  // Determine screen quadrant and position menu accordingly
  let finalX, finalY;
  
  // Check if click is in upper or lower half
  const isUpperHalf = y < viewportHeight / 2;
  // Check if click is in left or right half
  const isLeftHalf = x < viewportWidth / 2;
  
  if (isUpperHalf) {
    // Upper half - align top of menu to click
    finalY = y;
  } else {
    // Lower half - align bottom of menu to click
    finalY = y - menuHeight;
  }
  
  if (isLeftHalf) {
    // Left half - align left of menu to click
    finalX = x;
  } else {
    // Right half - align right of menu to click
    finalX = x - menuWidth;
  }
  
  // Ensure menu stays within viewport bounds
  if (finalX < 0) finalX = 10;
  if (finalY < 0) finalY = 10;
  if (finalX + menuWidth > viewportWidth) finalX = viewportWidth - menuWidth - 10;
  if (finalY + menuHeight > viewportHeight) finalY = viewportHeight - menuHeight - 10;
  
  // Apply positioning
  categoryMenu.style.left = `${finalX}px`;
  categoryMenu.style.top = `${finalY}px`;
  
  categoryMenu.innerHTML = `
    <div class="text-sm font-medium text-gray-700 mb-2 px-2 py-1 border-b border-gray-200">
      📁 ${categoryName}
    </div>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideCategoryContextMenu(); openQuickAddModal(${categoryId})">
      ➕ Add Bookmark
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideCategoryContextMenu(); openCategoryEditModal(${categoryId}, '${categoryName}', '${categoryData?.pageId || ''}', '${categoryData?.width || '3'}', '${categoryData?.noDescription || '0'}', '${categoryData?.showFavicon || '1'}')">
      ✏️ Edit Category
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideCategoryContextMenu(); openAllBookmarksInCategory(${categoryId})">
      🔗 Open All Bookmarks
    </button>
    <div class="border-t border-gray-200 mt-2 pt-2">
      <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded flex items-center gap-2" onclick="hideCategoryContextMenu(); openDeleteModal(${categoryId}, '${categoryName}', 'category')">
        🗑️ Delete Category
      </button>
    </div>
  `;
  
  // Auto-hide after 10 seconds
  setTimeout(() => {
    hideCategoryContextMenu();
  }, 10000);
}

// Hide category context menu
function hideCategoryContextMenu() {
  const categoryMenu = document.getElementById('categoryContextMenu');
  if (categoryMenu) {
    categoryMenu.remove();
  }
}

// Show mobile-friendly category context menu
function showMobileCategoryContextMenu(x, y, categoryId, categoryName, categoryData) {
  // Remove existing mobile menu if present
  hideMobileContextMenu();
  
  // Create a mobile-optimized category context menu
  const mobileMenu = document.createElement('div');
  mobileMenu.id = 'mobileCategoryContextMenu';
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
      📁 ${categoryName}
    </div>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideMobileCategoryContextMenu(); openQuickAddModal(${categoryId})">
      ➕ Add Bookmark
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideMobileCategoryContextMenu(); openCategoryEditModal(${categoryId}, '${categoryName}', '${categoryData?.pageId || ''}', '${categoryData?.width || '3'}', '${categoryData?.noDescription || '0'}', '${categoryData?.showFavicon || '1'}')">
      ✏️ Edit Category
    </button>
    <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 rounded flex items-center gap-2" onclick="hideMobileCategoryContextMenu(); openAllBookmarksInCategory(${categoryId})">
      🔗 Open All Bookmarks
    </button>
    <div class="border-t border-gray-200 mt-2 pt-2">
      <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded flex items-center gap-2" onclick="hideMobileCategoryContextMenu(); openDeleteModal(${categoryId}, '${categoryName}', 'category')">
        🗑️ Delete Category
      </button>
    </div>
    <div class="border-t border-gray-200 mt-2 pt-2">
      <button class="w-full text-left px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 rounded flex items-center gap-2" onclick="hideMobileCategoryContextMenu()">
        ❌ Close
      </button>
    </div>
  `;
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideMobileCategoryContextMenu();
  }, 5000);
}

// Hide mobile category context menu
function hideMobileCategoryContextMenu() {
  const mobileMenu = document.getElementById('mobileCategoryContextMenu');
  if (mobileMenu) {
    mobileMenu.remove();
  }
}

// Hide context menu when clicking elsewhere
document.addEventListener('click', (e) => {
  const contextMenu = document.getElementById('contextMenu');
  const categoryContextMenu = document.getElementById('categoryContextMenu');
  const mobileContextMenu = document.getElementById('mobileContextMenu');
  const mobileCategoryContextMenu = document.getElementById('mobileCategoryContextMenu');
  
  if (contextMenu && !contextMenu.contains(e.target)) {
    // Call hideContextMenu from modal-management.js
    if (window.hideContextMenu) {
      window.hideContextMenu();
    }
  }
  
  if (categoryContextMenu && !categoryContextMenu.contains(e.target)) {
    hideCategoryContextMenu();
  }
  
  // Also hide mobile context menus
  if (mobileContextMenu && !mobileContextMenu.contains(e.target)) {
    hideMobileContextMenu();
  }
  
  if (mobileCategoryContextMenu && !mobileCategoryContextMenu.contains(e.target)) {
    hideMobileCategoryContextMenu();
  }
});

// Hide context menu when pressing Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    // Call hideContextMenu from modal-management.js
    if (window.hideContextMenu) {
      window.hideContextMenu();
    }
    hideCategoryContextMenu();
    hideMobileContextMenu();
    hideMobileCategoryContextMenu();
  }
});

// Export functions for global access
window.showCategoryContextMenu = showCategoryContextMenu;
window.hideCategoryContextMenu = hideCategoryContextMenu;
window.showMobileCategoryContextMenu = showMobileCategoryContextMenu;
window.hideMobileCategoryContextMenu = hideMobileCategoryContextMenu;

// Test function to verify context menu is working
window.testContextMenu = () => {
  console.log('Testing context menu...');
  const testX = 100;
  const testY = 100;
  showCategoryContextMenu(testX, testY, 'test', 'Test Category', {});
};

// Log when the module is loaded
console.log('Context menu module loaded successfully');
