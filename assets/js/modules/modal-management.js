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
const deleteModalTitle = document.getElementById("deleteModalTitle");
const deletePrompt = document.getElementById("deletePrompt");
const deleteNote = document.getElementById("deleteNote");

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

const dialogReturnFocus = new WeakMap();
const dialogFocusableSelector = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])'
].join(',');

function getVisibleDialogs() {
  return Array.from(document.querySelectorAll('.modal-backdrop:not(.hidden)')).sort((first, second) => {
    const firstZIndex = Number.parseInt(getComputedStyle(first).zIndex, 10) || 0;
    const secondZIndex = Number.parseInt(getComputedStyle(second).zIndex, 10) || 0;
    return firstZIndex - secondZIndex;
  });
}

function getDialogFocusableElements(dialog) {
  if (!dialog) return [];
  return Array.from(dialog.querySelectorAll(dialogFocusableSelector)).filter(element =>
    element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0
  );
}

function restoreDialogFocus(modalElement) {
  const returnFocus = dialogReturnFocus.get(modalElement);
  dialogReturnFocus.delete(modalElement);
  if (!returnFocus?.isConnected || returnFocus.closest('.hidden, [hidden]')) return;
  returnFocus.focus();
}

// Generic modal management functions
function showModal(modalElement, focusElement = null, returnFocus = document.activeElement) {
  if (modalElement) {
    if (!modalElement.contains(returnFocus)) {
      dialogReturnFocus.set(modalElement, returnFocus);
    }
    modalElement.classList.remove("hidden");
    modalElement.classList.add("flex");
    const initialFocus = focusElement || getDialogFocusableElements(modalElement)[0];
    initialFocus?.focus();
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
    restoreDialogFocus(modalElement);
  }
}

function dismissDialogByControlId(controlId) {
  if (!controlId) return;
  const control = document.getElementById(controlId);
  if (control?.closest('.modal-backdrop')?.getAttribute('aria-busy') === 'true') return;
  control?.click();
}

// Shared close buttons and backdrop dismissal delegate to each dialog's
// existing Cancel/Close control so reset and focus behavior stays centralized.
document.addEventListener("click", (event) => {
  const dismissControl = event.target.closest("[data-dialog-dismiss]");
  if (!dismissControl) return;
  if (dismissControl.classList.contains("modal-backdrop")) {
    if (event.target !== dismissControl) return;
    if (dismissControl.dataset.dialogBackdropDismiss !== "true") return;
  }
  dismissDialogByControlId(dismissControl.dataset.dialogDismiss);
});

document.addEventListener("keydown", (event) => {
  const activeDialog = getVisibleDialogs().at(-1);
  if (!activeDialog) return;

  if (event.key === "Escape") {
    if (!activeDialog.dataset.dialogDismiss) return;
    event.preventDefault();
    dismissDialogByControlId(activeDialog.dataset.dialogDismiss);
    return;
  }

  if (event.key !== "Tab") return;
  const focusableElements = getDialogFocusableElements(activeDialog);
  if (!focusableElements.length) {
    event.preventDefault();
    activeDialog.setAttribute('tabindex', '-1');
    activeDialog.focus();
    return;
  }

  const firstElement = focusableElements[0];
  const lastElement = focusableElements.at(-1);
  if (event.shiftKey && document.activeElement === firstElement) {
    event.preventDefault();
    lastElement.focus();
  } else if (!event.shiftKey && document.activeElement === lastElement) {
    event.preventDefault();
    firstElement.focus();
  } else if (!activeDialog.contains(document.activeElement)) {
    event.preventDefault();
    firstElement.focus();
  }
});

// Specialized modal functions using generic ones
function openEditModal(data) {
  document.getElementById("edit-id").value = data.id;
  document.getElementById("edit-title").value = data.title || "";
  document.getElementById("edit-url").value = data.url || "";
  document.getElementById("edit-description").value = data.description || "";
  document.getElementById("edit-category").value = data.category_id || "";
  
  // Set background color
  const backgroundColorSelect = document.getElementById("edit-background-color");
  if (backgroundColorSelect) {
    // If numeric color is provided, map it to token; else prefer background_color token
    const mapping = window.bookmarkColorMapping || {};
    const backgroundColor = (data.color && mapping[data.color]) ? mapping[data.color] : (data.background_color || "none");
    backgroundColorSelect.value = backgroundColor;
    
    // Update color preview
    updateColorPreview(backgroundColor);

    // Live update preview on selection changes
    backgroundColorSelect.onchange = (e) => {
      updateColorPreview(e.target.value);
    };
    backgroundColorSelect.oninput = (e) => {
      updateColorPreview(e.target.value);
    };
  }
  
  // Populate favicon display
  const faviconImg = document.getElementById('edit-favicon');
  const faviconUrl = document.getElementById('edit-favicon-url');
  const faviconStorage = document.getElementById('edit-favicon-storage');
  
  if (faviconImg && faviconUrl && faviconStorage) {
    // Keep client-side fallback placeholders out of storage;
    // applyBookmarkFavicon creates one when there is no persisted favicon.
    const storedFavicon = window.normalizeStoredFaviconUrl(data.favicon_url);

    faviconStorage.value = storedFavicon;
    window.applyBookmarkFavicon(faviconImg, storedFavicon, data.url || '');
    faviconUrl.textContent = window.describeStoredFavicon(storedFavicon, data.url || '');
    faviconUrl.title = faviconUrl.textContent;
  }
  
  // Handle long URLs by setting a title attribute for full URL on hover
  const urlInput = document.getElementById('edit-url');
  if (urlInput && data.url) {
    urlInput.title = data.url; // Show full URL on hover
  }
  
  showModal(editModal, document.getElementById("edit-title"));
}

function closeEditModal() {
  hideModal(editModal, ["edit-id", "edit-title", "edit-url", "edit-description", "edit-category", "edit-background-color", "edit-favicon-storage"]);
}

function openQuickAddModal(categoryId = null) {
  DEBUG.log("MODAL", "Opening quick add modal...");
  showModal(quickAddModal, document.getElementById("quick-url"));
  
  // Pre-select category if provided
  if (categoryId) {
    const categorySelect = document.getElementById("quick-category");
    if (categorySelect) {
      categorySelect.value = categoryId;
    }
  }
  
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

function openDeleteModal(itemId, itemTitle, itemType = 'bookmark', options = {}) {
  DEBUG.log("MODAL", "Opening delete modal for:", itemTitle, "type:", itemType);
  deleteBookmarkTitle.textContent = itemTitle;
  deleteConfirm.dataset.id = itemId;
  deleteConfirm.dataset.type = itemType;

  const isCategory = itemType === 'category';
  deleteModalTitle.textContent = options.title || (isCategory ? 'Move category to Trash?' : 'Delete item?');
  deletePrompt.textContent = options.prompt || (isCategory
    ? 'The category and all its links will be hidden.'
    : 'Are you sure you want to delete?');
  deleteNote.textContent = options.note || (isCategory
    ? 'You can restore it later from Trash.'
    : 'This action cannot be undone.');
  deleteConfirm.textContent = options.confirmLabel || (isCategory ? 'Move to Trash' : 'Delete item');
  deleteModal.dataset.dialogBackdropDismiss = "true";

  showModal(deleteModal, deleteCancel);
}

function closeDeleteModal(options = {}) {
  if (deleteModal.getAttribute("aria-busy") === "true" && !options.force) return;
  DEBUG.log("MODAL", "Closing delete modal...");
  hideModal(deleteModal);
  deleteModal.dataset.dialogBackdropDismiss = "true";
  deleteModal.removeAttribute("aria-busy");
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
  
  showModal(categoryEditModal, document.getElementById("category-edit-name"));
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
  showModal(pageEditModal, document.getElementById("page-edit-name"));
}

function closePageEditModal() {
  DEBUG.log("MODAL", "Closing page edit modal...");
  hideModal(pageEditModal, ["page-edit-id", "page-edit-name"]);
}

// --- Context Menu Functions ---
function showContextMenu(x, y) {
  const contextMenu = document.getElementById('contextMenu');
  if (!contextMenu) return;
  
  // Get menu dimensions
  contextMenu.classList.remove('hidden');
  const rect = contextMenu.getBoundingClientRect();
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
  contextMenu.style.left = finalX + 'px';
  contextMenu.style.top = finalY + 'px';
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
  deleteModal.dataset.dialogBackdropDismiss = "false";
  deleteModal.setAttribute("aria-busy", "true");
  deleteConfirm.disabled = true;

  try {
    let apiEndpoint, successMessage;
    
    if (type === 'category') {
      apiEndpoint = "../api/delete-category.php";
      successMessage = "Category moved to Trash.";
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
        document.querySelectorAll(`select option[value='${CSS.escape(String(id))}']`).forEach(option => option.remove());
        window.invalidateSearchData?.();
      } else if (type === 'page') {
        // For page deletion, handle the response to determine page redirection
        DEBUG.log("MODAL", "Page deleted, handling page transition...");
        
        // Check if we need to redirect to a different page
        if (result.wasCurrentPage && result.redirectPageId) {
          DEBUG.log("MODAL", `Redirecting to page ${result.redirectPageId} after deletion`);
          // Set cookie for the redirect page
          document.cookie = `startpage_current_page_id=${result.redirectPageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
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
          document.dispatchEvent(new CustomEvent('bookmark-deleted', {
            detail: { id: String(id), categoryId: String(categoryId) }
          }));

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
      if (typeof isDataLoaded !== 'undefined') isDataLoaded = false;
      DEBUG.log("MODAL", `🔄 Search data reset after ${type} deletion`);
      
      closeDeleteModal({ force: true });
      showFlashMessage(successMessage, 'success');
    } else {
      showFlashMessage("Delete failed: " + (result.message || "Unknown error"), 'error');
      deleteModal.dataset.dialogBackdropDismiss = "true";
    }
  } catch (error) {
    console.error("Error deleting", type + ":", error);
    showFlashMessage("Error deleting " + type + ": " + error.message, 'error');
    deleteModal.dataset.dialogBackdropDismiss = "true";
  } finally {
    deleteModal.removeAttribute("aria-busy");
    deleteConfirm.disabled = false;
  }
});

// Color preview functionality
function updateColorPreview(backgroundColor) {
  const colorPreview = document.getElementById('edit-color-preview');
  const colorLabel = document.getElementById('edit-color-label');
  if (!colorPreview || !colorLabel) return;
  
  // Remove all existing background color classes dynamically
  const bgClasses = ['bg-gray-50'];
  const colorLabels = window.bookmarkColorLabels || {};
  
  // Build dynamic classes and labels from PHP mappings
  Object.keys(colorLabels).forEach(token => {
    if (token !== 'none') {
      bgClasses.push(`bg-${token}-100`);
    }
  });
  colorPreview.classList.remove(...bgClasses);
  
  // Add the appropriate background color class and update label
  if (backgroundColor && backgroundColor !== 'none' && colorLabels[backgroundColor]) {
    colorPreview.classList.add(`bg-${backgroundColor}-100`);
    colorLabel.textContent = colorLabels[backgroundColor];
  } else {
    colorPreview.classList.add('bg-gray-50');
    colorLabel.textContent = colorLabels['none'] || 'None';
  }
}

// Add event listener for background color dropdown
document.addEventListener('DOMContentLoaded', () => {
  const backgroundColorSelect = document.getElementById('edit-background-color');
  if (backgroundColorSelect) {
    backgroundColorSelect.addEventListener('change', (e) => {
      updateColorPreview(e.target.value);
    });
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
window.showManagedDialog = showModal;
window.hideManagedDialog = hideModal;
window.showContextMenu = showContextMenu;
window.hideContextMenu = hideContextMenu;
window.updateColorPreview = updateColorPreview;
