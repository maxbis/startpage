// --- Click category edit pencil or title: open category modal ---
document.querySelectorAll("[data-action='edit-category']").forEach((element) => {
  element.addEventListener("click", () => {
    const id = element.dataset.id;
    const name = element.dataset.name;
    const pageId = element.dataset.pageId;
    const width = element.dataset.width || "3";
    const noDescription = element.dataset.noDescription || "0";
    const showFavicon = element.dataset.showFavicon || "1";
    openCategoryEditModal(id, name, pageId, width, noDescription, showFavicon);
  });
});

// --- Category Add form submission ---
categoryAddForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const categoryName = document.getElementById("category-add-name").value;

  // Immediately close modal and show loading state to prevent multiple submissions
  closeCategoryAddModal();
  showFlashMessage("Adding category...", 'info');

  try {
    const res = await fetch("../api/add-category.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name: categoryName }),
    });

    const result = await res.json();
    if (!result.success) {
      showFlashMessage(result.message || "Failed to add category", 'error');
      return;
    }

    showFlashMessage("Category added successfully!", 'success');
    
    // Delay the reload to allow the flash message to be visible
    setTimeout(() => {
      location.reload();
    }, 1500);
  } catch (error) {
    console.error("Error adding category:", error);
    showFlashMessage("Error adding category: " + error.message, 'error');
  }
});

// --- Category Edit form submission ---
categoryEditForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    id: document.getElementById("category-edit-id").value,
    name: document.getElementById("category-edit-name").value,
    page_id: document.getElementById("category-edit-page").value,
    width: document.getElementById("category-edit-width").value,
    no_description: document.getElementById('category-edit-show-description').checked ? "0" : "1", // Inverted logic: unchecked = hide descriptions
    show_favicon: document.getElementById('category-edit-show-favicon').checked ? "1" : "0",
  };

  // Immediately close modal and show loading state to prevent multiple submissions
  closeCategoryEditModal();
  showFlashMessage("Updating category...", 'info');

  try {
    const res = await fetch("../api/edit-category.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const result = await res.json();
    if (!result.success) {
      showFlashMessage(result.message || "Failed to update category", 'error');
      return;
    }

    // Check if the category was moved to a different page
    const currentPageId = document.querySelector('#pageEditButton').dataset.pageId;
    if (payload.page_id !== currentPageId) {
      // Category was moved to a different page - reload to update the view
      showFlashMessage("Category moved to different page successfully!", 'success');
      // Delay the reload to allow the flash message to be visible
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      // Check if width, description, or favicon setting changed - if so, reload the page to apply changes
      const originalWidth = document.querySelector(`section[data-category-id='${payload.id}'] h2`).dataset.width;
      const originalNoDescription = document.querySelector(`section[data-category-id='${payload.id}'] h2`).dataset.noDescription;
      const originalShowFavicon = document.querySelector(`section[data-category-id='${payload.id}'] h2`).dataset.showFavicon;
      if (originalWidth !== payload.width || originalNoDescription !== payload.no_description || originalShowFavicon !== payload.show_favicon) {
        showFlashMessage("Category updated successfully! Reloading to apply changes...", 'success');
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        // Category stayed on the same page - update the DOM
        updateCategoryDisplay(payload.id, payload);
        showFlashMessage("Category updated successfully!", 'success');
      }
    }

    // Reset search data to ensure fresh data after category edit
    isDataLoaded = false;
    DEBUG.log('🔄 Search data reset after category edit');
  } catch (error) {
    console.error("Error updating category:", error);
    showFlashMessage("Error updating category: " + error.message, 'error');
  }
});

// --- Category Delete button ---
categoryEditDelete?.addEventListener("click", () => {
  const categoryId = document.getElementById("category-edit-id").value;
  const categoryName = document.getElementById("category-edit-name").value;
  
  // Close category edit modal and open delete confirmation modal
  closeCategoryEditModal();
  openDeleteModal(categoryId, categoryName, 'category');
});
