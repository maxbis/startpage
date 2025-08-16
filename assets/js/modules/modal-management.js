// --- Modal setup ---
const editModal = document.getElementById("editModal");
const editForm = document.getElementById("editForm");

const editCancel = document.getElementById("editCancel");
const editDelete = document.getElementById("editDelete");

// Quick Add Modal setup
const quickAddModal = document.getElementById("quickAddModal");
const quickAddForm = document.getElementById("quickAddForm");

const quickAddCancel = document.getElementById("quickAddCancel");

// Delete Modal setup
const deleteModal = document.getElementById("deleteModal");
const deleteConfirm = document.getElementById("deleteConfirm");
const deleteCancel = document.getElementById("deleteCancel");

const deleteBookmarkTitle = document.getElementById("deleteBookmarkTitle");

// Category Edit Modal setup
// --- Context Menu ---
const contextMenu = document.getElementById("contextMenu");
const contextAddLink = document.getElementById("contextAddLink");
const contextAddCategory = document.getElementById("contextAddCategory");
const contextAddPage = document.getElementById("contextAddPage");

// --- Category Add Modal ---
const categoryAddModal = document.getElementById("categoryAddModal");
const categoryAddForm = document.getElementById("categoryAddForm");

const categoryAddCancel = document.getElementById("categoryAddCancel");

// --- Page Add Modal ---
const pageAddModal = document.getElementById("pageAddModal");
const pageAddForm = document.getElementById("pageAddForm");

const pageAddCancel = document.getElementById("pageAddCancel");

// --- Page Edit Modal ---
const pageEditModal = document.getElementById("pageEditModal");
const pageEditForm = document.getElementById("pageEditForm");

const pageEditCancel = document.getElementById("pageEditCancel");
const pageEditDelete = document.getElementById("pageEditDelete");

// --- Category Edit Modal ---
const categoryEditModal = document.getElementById("categoryEditModal");
const categoryEditForm = document.getElementById("categoryEditForm");

const categoryEditCancel = document.getElementById("categoryEditCancel");
const categoryEditDelete = document.getElementById("categoryEditDelete");

// Generic modal management functions
function showModal(modalElement, focusElement = null) {
  if (modalElement) {
    modalElement.classList.remove("hidden");
    modalElement.classList.add("flex");
    if (focusElement) {
      focusElement.focus();
    }
  }
}

function hideModal(modalElement, resetFields = []) {
  if (modalElement) {
    modalElement.classList.add("hidden");
    modalElement.classList.remove("flex");
    
    // Reset form fields
    resetFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.value = "";
      }
    });
  }
}

// Specialized modal functions using generic ones
function openEditModal(data) {
  document.getElementById("edit-id").value = data.id;
  document.getElementById("edit-title").value = data.title || "";
  document.getElementById("edit-url").value = data.url || "";
  document.getElementById("edit-description").value = data.description || "";
  document.getElementById("edit-category").value = data.category_id || "";
  
  // Populate favicon display
  const faviconImg = document.getElementById('edit-favicon');
  const faviconUrl = document.getElementById('edit-favicon-url');
  
  if (faviconImg && faviconUrl) {
    // Use the favicon_url from the bookmark data if available
    if (data.favicon_url && data.favicon_url !== 'favicon.png') {
      // Convert database format to display format
      let displayFaviconUrl = data.favicon_url;
      if (displayFaviconUrl.startsWith('cache/')) {
        displayFaviconUrl = '../' + displayFaviconUrl;
      }
      faviconImg.src = displayFaviconUrl;
      faviconUrl.textContent = data.favicon_url;
      faviconUrl.title = data.favicon_url; // Show full URL on hover
    } else {
              faviconImg.src = window.faviconConfig?.defaultFaviconDataUri || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgcng9IjQiIGZpbGw9IiNmMGYwZjAiLz4KICAgIDx0ZXh0IHg9IjE2IiB5PSIyMiIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjMzMzMzMzIj7wn5KrPC90ZXh0Pgo8L3N2Zz4=';
      faviconUrl.textContent = 'No favicon available';
      faviconUrl.title = '';
    }
  }
  
  // Handle long URLs by setting a title attribute for full URL on hover
  const urlInput = document.getElementById('edit-url');
  if (urlInput && data.url) {
    urlInput.title = data.url; // Show full URL on hover
  }
  
  showModal(editModal);
}

function closeEditModal() {
  hideModal(editModal, ["edit-id", "edit-title", "edit-url", "edit-description", "edit-category"]);
}

function openQuickAddModal() {
  DEBUG.log("MODAL", "Opening quick add modal...");
  showModal(quickAddModal, document.getElementById("quick-url"));
  
  // Clear URL parameters
  if (window.history.replaceState) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
  
  // Close popup if this is a popup window
  if (window.opener && !window.opener.closed) {
    window.close();
  }
}

function closeQuickAddModal() {
  hideModal(quickAddModal, ["quick-url", "quick-title", "quick-description", "quick-category"]);
}

function openDeleteModal(itemId, itemTitle, itemType = 'bookmark') {
  DEBUG.log("MODAL", "Opening delete modal for:", itemTitle, "type:", itemType);
  deleteBookmarkTitle.textContent = itemTitle;
  deleteConfirm.dataset.id = itemId;
  deleteConfirm.dataset.type = itemType;
  showModal(deleteModal);
}

function closeDeleteModal() {
  DEBUG.log("MODAL", "Closing delete modal...");
  hideModal(deleteModal);
  deleteBookmarkTitle.textContent = "";
  deleteConfirm.dataset.id = "";
  deleteConfirm.dataset.type = "";
}

function openCategoryEditModal(categoryId, categoryName, pageId, width, noDescription, showFavicon) {
  DEBUG.log("MODAL", "Opening category edit modal for:", categoryName);
  document.getElementById("category-edit-id").value = categoryId;
  document.getElementById("category-edit-name").value = categoryName;
  document.getElementById("category-edit-page").value = pageId || "";
  document.getElementById("category-edit-width").value = width || "3";
  
  // Set the checkboxes
  const showDescCheckbox = document.getElementById('category-edit-show-description');
  showDescCheckbox.checked = noDescription === "0"; // Inverted logic: show when no_description is 0
  
  const showFavCheckbox = document.getElementById('category-edit-show-favicon');
  showFavCheckbox.checked = showFavicon === "1";
  
  showModal(categoryEditModal);
}

function closeCategoryEditModal() {
  DEBUG.log("MODAL", "Closing category edit modal...");
  hideModal(categoryEditModal, ["category-edit-id", "category-edit-name", "category-edit-page", "category-edit-width"]);
  
  // Reset checkboxes to default
  const showDescCheckbox = document.getElementById('category-edit-show-description');
  showDescCheckbox.checked = true; // Default to showing descriptions
  
  const showFavCheckbox = document.getElementById('category-edit-show-favicon');
  showFavCheckbox.checked = true;
}

// --- Category Add Modal Functions ---
function openCategoryAddModal() {
  DEBUG.log("MODAL", "Opening category add modal...");
  showModal(categoryAddModal, document.getElementById("category-add-name"));
}

function closeCategoryAddModal() {
  DEBUG.log("MODAL", "Closing category add modal...");
  hideModal(categoryAddModal, ["category-add-name"]);
}

// --- Page Add Modal Functions ---
function openPageAddModal() {
  DEBUG.log("MODAL", "Opening page add modal...");
  showModal(pageAddModal, document.getElementById("page-add-name"));
}

function closePageAddModal() {
  DEBUG.log("MODAL", "Closing page add modal...");
  hideModal(pageAddModal, ["page-add-name"]);
}

// --- Page Edit Modal Functions ---
function openPageEditModal(pageId, pageName) {
  DEBUG.log("MODAL", "Opening page edit modal for:", pageName);
  document.getElementById("page-edit-id").value = pageId;
  document.getElementById("page-edit-name").value = pageName;
  showModal(pageEditModal);
}

function closePageEditModal() {
  DEBUG.log("MODAL", "Closing page edit modal...");
  hideModal(pageEditModal, ["page-edit-id", "page-edit-name"]);
}

// --- Context Menu Functions ---
function showContextMenu(x, y) {
  contextMenu.style.left = x + 'px';
  contextMenu.style.top = y + 'px';
  contextMenu.classList.remove('hidden');
}

function hideContextMenu() {
  contextMenu.classList.add('hidden');
}

// Modal event listeners
editCancel?.addEventListener("click", closeEditModal);
editDelete?.addEventListener("click", () => {
  const id = document.getElementById("edit-id").value;
  const title = document.getElementById("edit-title").value;
  closeEditModal();
  openDeleteModal(id, title);
});

// Category edit modal event listeners
// --- Context Menu Event Listeners ---
contextAddLink?.addEventListener("click", () => {
  hideContextMenu();
  openQuickAddModal();
});

contextAddCategory?.addEventListener("click", () => {
  hideContextMenu();
  openCategoryAddModal();
});

contextAddPage?.addEventListener("click", () => {
  hideContextMenu();
  openPageAddModal();
});

// --- Category Add Modal Event Listeners ---
categoryAddCancel?.addEventListener("click", closeCategoryAddModal);

// --- Page Add Modal Event Listeners ---
pageAddCancel?.addEventListener("click", closePageAddModal);

// --- Page Edit Modal Event Listeners ---
pageEditCancel?.addEventListener("click", closePageEditModal);

categoryEditCancel?.addEventListener("click", closeCategoryEditModal);

quickAddCancel?.addEventListener("click", closeQuickAddModal);

// Delete modal event listeners
deleteCancel?.addEventListener("click", closeDeleteModal);

// Delete confirmation
deleteConfirm?.addEventListener("click", async () => {
  const id = deleteConfirm.dataset.id;
  const type = deleteConfirm.dataset.type || 'bookmark';
  DEBUG.log("MODAL", "Deleting", type, "with ID:", id);
  
  try {
    let apiEndpoint, successMessage;
    
    if (type === 'category') {
      apiEndpoint = "../api/delete-category.php";
      successMessage = "Category deleted successfully!";
    } else if (type === 'page') {
      apiEndpoint = "../api/delete-page.php";
      successMessage = "Page deleted successfully!";
    } else if (type === 'bookmark') {
      apiEndpoint = "../api/delete-bookmark.php";
      successMessage = "Bookmark deleted successfully!";
    } else {
      DEBUG.log("MODAL", "Delete API but found unknown type:", type);
    }
    
    const res = await fetch(apiEndpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id }),
    });

    const result = await res.json();
    if (result.success) {
      if (type === 'category') {
        // Find and remove the category section
        const categoryElement = document.querySelector(`section[data-category-id='${id}']`);
        if (categoryElement) {
          categoryElement.remove();
          DEBUG.log("MODAL", "Category removed from DOM");
        }
      } else if (type === 'page') {
        // For page deletion, handle the response to determine page redirection
        DEBUG.log("MODAL", "Page deleted, handling page transition...");
        
        // Check if we need to redirect to a different page
        if (result.wasCurrentPage && result.redirectPageId) {
          DEBUG.log("MODAL", `Redirecting to page ${result.redirectPageId} after deletion`);
          // Set cookie for the redirect page
          document.cookie = `current_page_id=${result.redirectPageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
        }
        
        // Delay the reload to allow the flash message to be visible
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else if (type === 'bookmark') {
        // Find and remove the bookmark element
        const bookmarkElement = document.querySelector(`li[data-id='${id}']`);
        if (bookmarkElement) {
          const categoryId = bookmarkElement.closest('ul').dataset.categoryId;
          bookmarkElement.remove();
          DEBUG.log("MODAL", "Bookmark removed from DOM");
          
          // Update empty state for the category
          updateEmptyStates(categoryId);
        }
        // Also close edit modal if it's open
        if (editModal && !editModal.classList.contains("hidden")) {
          closeEditModal();
        }
      } else {
        DEBUG.log("MODAL", "Updating DOM and found unknown type:", type);
      }
      
      // Reset search data to ensure fresh data after deletion
      isDataLoaded = false;
      DEBUG.log("MODAL", `🔄 Search data reset after ${type} deletion`);
      
      closeDeleteModal();
      showFlashMessage(successMessage, 'success');
    } else {
      showFlashMessage("Delete failed: " + (result.message || "Unknown error"), 'error');
    }
  } catch (error) {
    console.error("Error deleting", type + ":", error);
    showFlashMessage("Error deleting " + type + ": " + error.message, 'error');
  }
});

// Export functions for use in other modules
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
window.openQuickAddModal = openQuickAddModal;
window.closeQuickAddModal = closeQuickAddModal;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.openCategoryEditModal = openCategoryEditModal;
window.closeCategoryEditModal = closeCategoryEditModal;
window.openCategoryAddModal = openCategoryAddModal;
window.closeCategoryAddModal = closeCategoryAddModal;
window.openPageAddModal = openPageAddModal;
window.closePageAddModal = closePageAddModal;
window.openPageEditModal = openPageEditModal;
window.closePageEditModal = closePageEditModal;
window.showContextMenu = showContextMenu;
window.hideContextMenu = hideContextMenu;
