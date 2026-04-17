// Add Bookmark
document.querySelectorAll(".add-bookmark-form").forEach((form) => {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const url = form.querySelector("input[name='url']").value;
    const categoryId = form.dataset.category;

    const response = await fetch("../api/add.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ url, category_id: categoryId }),
    });

    const result = await response.json();
    if (result.success) {
      location.reload();
    } else {
      showFlashMessage("Failed to add bookmark: " + result.message, 'error');
    }
  });
});

// Delete Bookmark
document.querySelectorAll("button[data-action='delete']").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.id;
    const li = btn.closest("li");
    const title = li.querySelector("a").textContent;
    openDeleteModal(id, title, 'bookmark');
  });
});

// --- Click pencil: open modal ---
document.querySelectorAll("button[data-action='edit']").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.id;
    const li = document.querySelector(`li[data-id='${id}']`);
    if (!li) return;

    openEditModal({
      id,
      title: li.dataset.title,
      url: li.dataset.url,
      description: li.dataset.description,
      category_id: li.dataset.categoryId,
      color: parseInt(li.dataset.color || '0', 10) || 0,
      background_color: li.dataset.backgroundColor || "none",
      favicon_url: li.dataset.faviconUrl || window.generateFaviconPlaceholderDataUri(li.dataset.url || '')
    });
  });
});

// --- Submit form to edit bookmark ---
editForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const faviconStorage = document.getElementById('edit-favicon-storage');
  const storedFaviconUrl = window.normalizeStoredFaviconUrl(faviconStorage?.value || '');

  const editId = document.getElementById("edit-id");
  const editTitle = document.getElementById("edit-title");
  const editUrl = document.getElementById("edit-url");
  const editDescription = document.getElementById("edit-description");
  const editCategory = document.getElementById("edit-category");
  const editBackgroundColor = document.getElementById("edit-background-color");

  const tokenToInt = window.bookmarkColorTokenToInt || {};
  const selectedToken = editBackgroundColor ? (editBackgroundColor.value || 'none') : 'none';
  const selectedColorInt = tokenToInt[selectedToken] ?? 0;

  if (!editId || !editTitle || !editUrl || !editDescription || !editCategory) {
    showFlashMessage("Edit form elements not found", 'error');
    return;
  }

  const payload = {
    id: editId.value,
    title: editTitle.value,
    url: editUrl.value,
    description: editDescription.value,
    category_id: editCategory.value,
    background_color: selectedToken,
    color: selectedColorInt,
    favicon_url: storedFaviconUrl || window.generateFaviconPlaceholderDataUri(editUrl.value || ''),
  };

  DEBUG.log('BOOKMARK', 'Submitting edit payload:', payload);

  closeEditModal();
  const loadingMessageId = showFlashMessage("Updating bookmark...", 'info');

  try {
    const res = await fetch("../api/edit.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`);
    }

    const result = await res.json();
    DEBUG.log('BOOKMARK', 'Edit API response:', result);

    if (!result.success) {
      updateFlashMessage(loadingMessageId, result.message || "Edit failed", 'error');
      return;
    }

    updateBookmarkDisplay(payload.id, payload);

    const oldCategoryId = document.querySelector(`li[data-id='${payload.id}']`)?.closest('ul')?.dataset.categoryId;
    if (oldCategoryId && oldCategoryId !== payload.category_id) {
      updateEmptyStates(oldCategoryId);
      updateEmptyStates(payload.category_id);
    }

    isDataLoaded = false;
    updateFlashMessage(loadingMessageId, "Bookmark updated successfully!", 'success');
  } catch (error) {
    console.error("Error in edit form submission:", error);
    updateFlashMessage(loadingMessageId, "Error editing bookmark: " + error.message, 'error');
  }
});

// --- Quick Add form submission ---
quickAddForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const urlInput = document.getElementById("quick-url");
  const titleInput = document.getElementById("quick-title");
  const descInput = document.getElementById("quick-description");
  const categoryInput = document.getElementById("quick-category");

  DEBUG.log("BOOKMARK", "Form elements found:", {
    urlInput: urlInput ? "Found" : "NOT FOUND",
    titleInput: titleInput ? "Found" : "NOT FOUND",
    descInput: descInput ? "Found" : "NOT FOUND",
    categoryInput: categoryInput ? "Found" : "NOT FOUND"
  });

  const payload = {
    url: urlInput?.value || "",
    title: titleInput?.value || "",
    description: descInput?.value || "",
    category_id: categoryInput?.value || "",
  };

  closeQuickAddModal();
  const loadingMessageId = showFlashMessage("Adding bookmark, please wait...", 'info');

  try {
    const res = await fetch("../api/add.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const text = await res.text();
    if (!text || text.trim() === '') {
      updateFlashMessage(loadingMessageId, "Error adding bookmark: Server returned an empty response. You may need to log in again.", 'error');
      return;
    }

    let result;
    try {
      result = JSON.parse(text);
    } catch (parseError) {
      console.error("API response was not valid JSON:", text.slice(0, 200));
      updateFlashMessage(loadingMessageId, "Error adding bookmark: Server returned an invalid response. Try logging in again or check your connection.", 'error');
      return;
    }

    if (!result.success) {
      updateFlashMessage(loadingMessageId, result.message || "Failed to add bookmark", 'error');
      return;
    }

    isDataLoaded = false;
    updateFlashMessage(loadingMessageId, "Bookmark added successfully!", 'success');

    setTimeout(() => {
      location.reload();
    }, 1000);

    if (window.opener && !window.opener.closed) {
      window.close();
    }
  } catch (error) {
    console.error("Error adding bookmark:", error);
    updateFlashMessage(loadingMessageId, "Error adding bookmark: " + error.message, 'error');
  }
});

// Open all bookmarks in category functionality
document.querySelectorAll('.open-all-category-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();

    const categoryId = btn.dataset.categoryId;
    openAllBookmarksInCategory(categoryId);
  });
});

// Global function to open all bookmarks in a category
function openAllBookmarksInCategory(categoryId) {
  const categorySection = document.querySelector(`section[data-category-id='${categoryId}']`);
  if (!categorySection) {
    console.warn(`Category section with ID ${categoryId} not found`);
    return;
  }

  const bookmarkLinks = categorySection.querySelectorAll('a.bookmark-title[href]');

  if (bookmarkLinks.length > 0) {
    bookmarkLinks.forEach(link => {
      if (link.href && link.href !== window.location.href) {
        window.open(link.href, '_blank');
      }
    });

    DEBUG.log("BOOKMARK", `Opened ${bookmarkLinks.length} bookmarks from category in new tabs`);
  }
}

window.openAllBookmarksInCategory = openAllBookmarksInCategory;
