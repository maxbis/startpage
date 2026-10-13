// --- Click page edit button: open page edit modal ---
document.querySelectorAll("#pageEditButton").forEach((element) => {
  element.addEventListener("click", () => {
    const pageId = element.dataset.pageId;
    const pageName = element.dataset.pageName;
    openPageEditModal(pageId, pageName);
  });
});

// --- Page Add form submission ---
pageAddForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const pageName = document.getElementById("page-add-name").value;

  // Immediately close modal and show loading state to prevent multiple submissions
  closePageAddModal();
  const loadingMessageId = showFlashMessage("Adding page...", 'info');

  try {
    const res = await fetch("../api/add-page.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name: pageName }),
    });

    const result = await res.json();
    if (!result.success) {
      // Replace loading message with error
      updateFlashMessage(loadingMessageId, result.message || "Failed to add page", 'error');
      return;
    }

    // Replace loading message with success
    updateFlashMessage(loadingMessageId, "Page added successfully!", 'success');
    
    // Delay the reload to allow the flash message to be visible
    setTimeout(() => {
      location.reload();
    }, 1500);
  } catch (error) {
    console.error("Error adding page:", error);
    // Replace loading message with error
    updateFlashMessage(loadingMessageId, "Error adding page: " + error.message, 'error');
  }
});

// --- Page Edit form submission ---
pageEditForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    id: document.getElementById("page-edit-id").value,
    name: document.getElementById("page-edit-name").value,
  };

  // Immediately close modal and show loading state to prevent multiple submissions
  closePageEditModal();
  const loadingMessageId = showFlashMessage("Updating page...", 'info');

  try {
    const res = await fetch("../api/edit-page.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const result = await res.json();
    if (!result.success) {
      // Replace loading message with error
      updateFlashMessage(loadingMessageId, result.message || "Failed to update page", 'error');
      return;
    }

    // Update the page name in the DOM
    updatePageDisplay(payload.id, payload);

    // Reset search data to ensure fresh data after page edit
    isDataLoaded = false;
    DEBUG.log('🔄 Search data reset after page edit');

    // Replace loading message with success
    updateFlashMessage(loadingMessageId, "Page updated successfully!", 'success');
  } catch (error) {
    console.error("Error updating page:", error);
    // Replace loading message with error
    updateFlashMessage(loadingMessageId, "Error updating page: " + error.message, 'error');
  }
});

// --- Page Delete button ---
pageEditDelete?.addEventListener("click", () => {
  const pageId = document.getElementById("page-edit-id").value;
  const pageName = document.getElementById("page-edit-name").value;
  
  // Close page edit modal and open delete confirmation modal
  closePageEditModal();
  openDeleteModal(pageId, pageName, 'page');
});
