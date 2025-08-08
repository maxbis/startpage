// Category Drag & Drop
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

// Bookmark Drag & Drop
document.querySelectorAll("ul[data-category-id]").forEach((list) => {
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
