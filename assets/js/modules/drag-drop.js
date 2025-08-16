// Mobile detection and drag & drop setup
function setupDragAndDrop() {
  // Wait for isMobile function to be available
  if (typeof window.isMobile !== 'function') {
    setTimeout(setupDragAndDrop, 100);
    return;
  }
  
  // Check for forced mobile mode first
  if (window.FORCE_MOBILE_MODE === true) {
    disableDragAndDrop();
    return;
  }
  
  // Check for forced desktop mode
  if (window.FORCE_MOBILE_MODE === false) {
    enableDragAndDrop();
    return;
  }
  
  // Use enhanced mobile detection
  const isMobileDevice = window.isMobileEnhanced ? window.isMobileEnhanced() : window.isMobile();
  
  if (isMobileDevice) {
    // Disable drag and drop on mobile
    disableDragAndDrop();
    return;
  }
  
  // Enable drag and drop on desktop
  enableDragAndDrop();
}

// Function to disable drag and drop
function disableDragAndDrop() {
  // Remove draggable attributes from bookmark items
  document.querySelectorAll('li[data-id]').forEach(item => {
    item.removeAttribute('draggable');
    item.classList.add('mobile:not-draggable');
  });
  
  // Update cursor styles for mobile
  document.querySelectorAll('.cursor-move').forEach(element => {
    element.classList.add('mobile:cursor-default');
  });
  
  // Show mobile notice if it exists
  const mobileNotice = document.getElementById('mobileDragNotice');
  if (mobileNotice) {
    mobileNotice.style.display = 'block';
  }
}

// Function to enable drag and drop
function enableDragAndDrop() {
  // Set draggable attributes for bookmark items
  document.querySelectorAll('li[data-id]').forEach(item => {
    item.setAttribute('draggable', 'true');
  });
  
  // Hide mobile notice if it exists
  const mobileNotice = document.getElementById('mobileDragNotice');
  if (mobileNotice) {
    mobileNotice.style.display = 'none';
  }
  
  // Initialize Sortable for categories
  const categoriesContainer = document.getElementById("categories-container");
  if (categoriesContainer) {
    new Sortable(categoriesContainer, {
      animation: 150,
      ghostClass: "opacity-50",
      chosenClass: "shadow-lg",
      onEnd: function (evt) {
        const categoryIds = Array.from(categoriesContainer.querySelectorAll("section[data-category-id]")).map(
          (el) => el.dataset.categoryId
        );
        DEBUG.log("Category order changed:", categoryIds);
        
        fetch("../api/reorder-categories.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            order: categoryIds,
          }),
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            DEBUG.log("Category order saved successfully");
          } else {
            console.error("Failed to save category order:", result.message);
          }
        })
        .catch(error => {
          console.error("Error saving category order:", error);
        });
      },
    });
  }

  // Initialize Sortable for bookmarks
  const bookmarkLists = document.querySelectorAll("ul[data-category-id]");
  
  bookmarkLists.forEach((list, index) => {
    new Sortable(list, {
      group: "bookmarks",
      animation: 150,
      // Only allow dragging when starting from the icon
      filter: ".no-drag",
      onStart: function (evt) {
        // Check if the drag started from the icon
        const draggedElement = evt.item;
        const icon = draggedElement.querySelector('img');
        
        // If the drag didn't start from the icon, cancel it
        if (!evt.originalEvent.target.closest('img')) {
          evt.preventDefault();
          return false;
        }
      },
      onEnd: function (evt) {
        const categoryId = evt.to.dataset.categoryId;
        const bookmarkIds = Array.from(evt.to.querySelectorAll("li")).map(
          (el) => el.dataset.id
        );
        
        // Update empty states for both source and target categories
        const fromCategoryId = evt.from.dataset.categoryId;
        const toCategoryId = evt.to.dataset.categoryId;
        
        if (fromCategoryId !== toCategoryId) {
          updateEmptyStates(fromCategoryId);
          updateEmptyStates(toCategoryId);
          
          // Update bookmark display for the moved bookmark to match new category settings
          const movedBookmark = evt.item; // The specific bookmark that was moved
          const originalCategoryId = movedBookmark.dataset.categoryId; // Get original category before it was updated
          updateBookmarkCategory(movedBookmark, toCategoryId, originalCategoryId);
        }
        
        fetch("../api/reorder.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            category_id: categoryId,
            order: bookmarkIds,
          }),
        });
      },
    });
  });
}

// Initialize drag and drop when DOM is ready AND after a short delay to ensure all modules are loaded
function initializeDragAndDrop() {
  // Wait a bit longer to ensure all modules are loaded
  setTimeout(() => {
    setupDragAndDrop();
  }, 200);
}

// Initialize drag and drop when DOM is ready
document.addEventListener('DOMContentLoaded', initializeDragAndDrop);

// Also try to initialize when window loads (fallback)
window.addEventListener('load', () => {
  // If setupDragAndDrop hasn't been called yet, call it now
  if (typeof window.isMobile === 'function') {
    setupDragAndDrop();
  }
});

// Handle window resize events to update drag and drop behavior
window.addEventListener('resize', () => {
  // Debounce resize events
  clearTimeout(window.resizeTimeout);
  window.resizeTimeout = setTimeout(setupDragAndDrop, 250);
});

// Update empty states for categories
function updateEmptyStates(categoryId) {
  const list = document.querySelector(`ul[data-category-id='${categoryId}']`);
  if (!list) return;
  
  const bookmarkItems = list.querySelectorAll('li[data-id]'); // Only actual bookmarks, not empty state
  const emptyStateItem = list.querySelector('li:not([data-id])'); // Empty state item
  
  if (bookmarkItems.length === 0) {
    // Category is empty - show empty state if not already present
    if (!emptyStateItem) {
      const emptyState = document.createElement('li');
      emptyState.className = 'text-gray-400 text-sm italic py-3 px-2 text-center border border-dashed border-gray-200 rounded-lg bg-gray-50';
      emptyState.innerHTML = '<span class="opacity-60">📭 No bookmarks yet</span>';
      list.appendChild(emptyState);
    }
  } else {
    // Category has bookmarks - remove empty state if present
    if (emptyStateItem) {
      emptyStateItem.remove();
    }
  }
}

// Export functions for use in other modules
window.updateEmptyStates = updateEmptyStates;

// Test function to check drag and drop status
window.testDragAndDropStatus = function() {
  if (typeof window.isMobile !== 'function') {
    return;
  }
  
  // Check forced mode
  if (window.FORCE_MOBILE_MODE === true) {
    console.log('🔧 FORCED MOBILE MODE - drag and drop disabled');
  } else if (window.FORCE_MOBILE_MODE === false) {
    console.log('🔧 FORCED DESKTOP MODE - drag and drop enabled');
  } else {
    console.log('🔧 AUTO DETECTION MODE - using device detection');
  }
  
  const isMobileDevice = window.isMobile();
  const isMobileEnhanced = window.isMobileEnhanced ? window.isMobileEnhanced() : 'Not available';
  
  console.log(`📱 Basic mobile detection: ${isMobileDevice}`);
  console.log(`📱 Enhanced mobile detection: ${isMobileEnhanced}`);
  
  const categoriesContainer = document.getElementById("categories-container");
  if (categoriesContainer) {
    // Check if Sortable is initialized
    const sortableInstance = categoriesContainer.sortable;
    if (sortableInstance) {
      console.log('✅ Sortable is initialized for categories');
    } else {
      console.log('❌ Sortable is NOT initialized for categories');
    }
  }
  
  const bookmarkLists = document.querySelectorAll("ul[data-category-id]");
  
  bookmarkLists.forEach((list, index) => {
    const sortableInstance = list.sortable;
    if (sortableInstance) {
      console.log(`✅ Bookmark list ${index + 1} has Sortable initialized`);
    } else {
      console.log(`❌ Bookmark list ${index + 1} does NOT have Sortable initialized`);
    }
  });
  
  // Check draggable attributes
  const draggableItems = document.querySelectorAll('li[draggable="true"]');
  
  if (window.FORCE_MOBILE_MODE === true) {
    console.log('📱 FORCED MOBILE MODE: Drag & drop is DISABLED');
  } else if (window.FORCE_MOBILE_MODE === false) {
    console.log('🖥️ FORCED DESKTOP MODE: Drag & drop is ENABLED');
  } else if (isMobileDevice) {
    console.log('📱 AUTO DETECTION - Mobile mode: Drag & drop is DISABLED');
  } else {
    console.log('🖥️ AUTO DETECTION - Desktop mode: Drag & drop is ENABLED');
  }
  
  // Show viewport information
  console.log(`📏 Viewport: ${window.innerWidth}x${window.innerHeight}`);
  console.log(`🖥️ Screen: ${window.screen.width}x${window.screen.height}`);
  console.log(`📱 Touch support: ${('ontouchstart' in window || navigator.maxTouchPoints > 0) ? 'Yes' : 'No'}`);
};

// Manual reinitialization function
window.reinitializeDragAndDrop = function() {
  // Clear any existing Sortable instances
  const categoriesContainer = document.getElementById("categories-container");
  if (categoriesContainer && categoriesContainer.sortable) {
    categoriesContainer.sortable.destroy();
  }
  
  document.querySelectorAll("ul[data-category-id]").forEach((list) => {
    if (list.sortable) {
      list.sortable.destroy();
    }
  });
  
  // Wait a moment then reinitialize
  setTimeout(() => {
    setupDragAndDrop();
  }, 100);
};
