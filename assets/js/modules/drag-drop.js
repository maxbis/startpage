// Mobile detection and drag & drop setup
let categoryDragEnabled = false;

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
  
  // Use the single mobile detection function
  const isMobileDevice = window.isMobile();
  
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
  categoryDragEnabled = false;
  // Remove draggable attributes from bookmark items
  document.querySelectorAll('li[data-id]').forEach(item => {
    item.removeAttribute('draggable');
    item.classList.add('mobile:not-draggable');
  });
  
  // Update cursor styles for mobile
  document.querySelectorAll('.cursor-move').forEach(element => {
    element.classList.add('mobile:cursor-default');
  });

  document.querySelectorAll('.category-column').forEach(column => {
    column._categorySortable?.destroy();
    column._categorySortable = null;
  });
  
  // Show mobile notice if it exists
  const mobileNotice = document.getElementById('mobileDragNotice');
  if (mobileNotice) {
    mobileNotice.style.display = 'block';
  }
}

function getCategoryIdsInColumnOrder() {
  return Array.from(document.querySelectorAll('#categories-container > .category-column'))
    .flatMap(column => Array.from(column.querySelectorAll(':scope > section[data-category-id]')))
    .map(section => section.dataset.categoryId);
}

function saveCategoryOrder() {
  const categoryIds = getCategoryIdsInColumnOrder();
  DEBUG.log('Category order changed:', categoryIds);

  return fetch('../api/reorder-categories.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order: categoryIds })
  })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        DEBUG.log('Category order saved successfully');
      } else {
        console.error('Failed to save category order:', result.message);
      }
    })
    .catch(error => console.error('Error saving category order:', error));
}

function initializeCategorySortables() {
  if (!categoryDragEnabled) return;

  document.querySelectorAll('#categories-container > .category-column').forEach(column => {
    if (column._categorySortable) return;

    column._categorySortable = new Sortable(column, {
      group: 'categories',
      animation: 150,
      ghostClass: 'opacity-50',
      chosenClass: 'shadow-lg',
      filter: '.bookmark-list, button, a',
      preventOnFilter: false,
      onStart: function (evt) {
        window.collapseCategory?.(evt.item);
        window.setCategoryLayoutFrozen?.(true);
      },
      onEnd: function () {
        window.setCategoryLayoutFrozen?.(false);
        saveCategoryOrder();
        window.rebalanceCategoryColumns?.(true);
      }
    });
  });
}

// Function to enable drag and drop
function enableDragAndDrop() {
  categoryDragEnabled = true;
  // Set draggable attributes for bookmark items
  document.querySelectorAll('li[data-id]').forEach(item => {
    item.setAttribute('draggable', 'true');
  });
  
  // Hide mobile notice if it exists
  const mobileNotice = document.getElementById('mobileDragNotice');
  if (mobileNotice) {
    mobileNotice.style.display = 'none';
  }
  
  initializeCategorySortables();

  // Initialize Sortable for bookmarks
  const bookmarkLists = document.querySelectorAll("ul[data-category-id]");
  
  bookmarkLists.forEach((list, index) => {
    if (list._bookmarkSortable) return;
    list._bookmarkSortable = new Sortable(list, {
      group: "bookmarks",
      animation: 150,
      // Only allow dragging when starting from the icon
      filter: ".no-drag",
      onStart: function (evt) {
        // If the drag didn't start from the standardized icon slot, cancel it.
        if (!evt.originalEvent?.target?.closest('.bookmark-icon')) {
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
        
        // Send the reorder request to the API
        fetch("../api/reorder.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            category_id: categoryId,
            order: bookmarkIds,
          }),
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            DEBUG.log("DRAG-DROP", "Bookmark order updated successfully");
          } else {
            console.error("Failed to save bookmark order:", result.message);
            // Optionally revert the drag operation on error
            // For now, we'll just log the error
          }
        })
        .catch(error => {
          console.error("Error saving bookmark order:", error);
          // Optionally revert the drag operation on error
          // For now, we'll just log the error
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
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeDragAndDrop);
} else {
  initializeDragAndDrop();
}

document.addEventListener('category-columns-changed', initializeCategorySortables);

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

  window.syncCategoryExpandControls?.();
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
  
  console.log(`📱 Mobile detection: ${isMobileDevice}`);
  
  const categoryColumns = document.querySelectorAll('#categories-container > .category-column');
  const initializedColumns = Array.from(categoryColumns).filter(column => column._categorySortable).length;
  console.log(`✅ Category sorting initialized for ${initializedColumns}/${categoryColumns.length} columns`);
  
  const bookmarkLists = document.querySelectorAll("ul[data-category-id]");
  
  bookmarkLists.forEach((list, index) => {
    const sortableInstance = list._bookmarkSortable;
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
  document.querySelectorAll('.category-column').forEach(column => {
    column._categorySortable?.destroy();
    column._categorySortable = null;
  });
  
  document.querySelectorAll("ul[data-category-id]").forEach((list) => {
    if (list._bookmarkSortable) {
      list._bookmarkSortable.destroy();
      list._bookmarkSortable = null;
    }
  });
  
  // Wait a moment then reinitialize
  setTimeout(() => {
    setupDragAndDrop();
  }, 100);
};
