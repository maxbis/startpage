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
      favicon_url: li.dataset.faviconUrl || window.faviconConfig.defaultFaviconDataUri
    });
  });
});

// --- Submit form to edit bookmark ---
editForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  // Get the current favicon URL from the display
  const faviconImg = document.getElementById('edit-favicon');
  const faviconUrl = faviconImg ? faviconImg.src : null;
  
  // Check if favicon is not the default data URI and is a valid favicon URL
  const isDefaultFavicon = faviconUrl && faviconUrl === window.faviconConfig.defaultFaviconDataUri;
  const isValidFaviconUrl = faviconUrl && !isDefaultFavicon && (faviconUrl.startsWith('http') || faviconUrl.startsWith('cache/') || faviconUrl.startsWith('../cache/'));
  
  const payload = {
    id: document.getElementById("edit-id").value,
    title: document.getElementById("edit-title").value,
    url: document.getElementById("edit-url").value,
    description: document.getElementById("edit-description").value,
    category_id: document.getElementById("edit-category").value,
  };
  
  // Add favicon_url if it's a valid favicon URL
  if (isValidFaviconUrl) {
    payload.favicon_url = faviconUrl;
    DEBUG.log('📌 Including favicon URL in edit payload:', faviconUrl);
  } else {
    console.log('📌 No valid favicon URL to save:', faviconUrl);
  }

  try {
    const res = await fetch("../api/edit.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const result = await res.json();
    if (!result.success) {
      showFlashMessage(result.message || "Edit failed", 'error');
      return;
    }

    // Use structured function to update bookmark display
    updateBookmarkDisplay(payload.id, payload);
    
    // Update empty states if category changed
    const oldCategoryId = document.querySelector(`li[data-id='${payload.id}']`)?.closest('ul')?.dataset.categoryId;
    if (oldCategoryId && oldCategoryId !== payload.category_id) {
      updateEmptyStates(oldCategoryId);
      updateEmptyStates(payload.category_id);
    }

    // Reset search data to ensure fresh data after edit
    isDataLoaded = false;
    DEBUG.log('🔄 Search data reset after bookmark edit');

    closeEditModal();
  } catch (error) {
    console.error("Error in edit form submission:", error);
    showFlashMessage("Error editing bookmark: " + error.message, 'error');
  }
});

// --- Quick Add form submission ---
quickAddForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  // Get form elements
  const urlInput = document.getElementById("quick-url");
  const titleInput = document.getElementById("quick-title");
  const descInput = document.getElementById("quick-description");
  const categoryInput = document.getElementById("quick-category");

  DEBUG.log("Form elements found:", {
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

  DEBUG.log("Payload constructed:", payload);
  DEBUG.log("Payload JSON:", JSON.stringify(payload));

  try {
    DEBUG.log("Making fetch request to: ../api/add.php");
    DEBUG.log("Request details:", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const res = await fetch("../api/add.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    DEBUG.log("Fetch response received:", {
      status: res.status,
      statusText: res.statusText,
      ok: res.ok,
      headers: Object.fromEntries(res.headers.entries())
    });

    const result = await res.json();
    DEBUG.log("API response parsed:", result);
    
    if (!result.success) {
      console.error("API returned error:", result.message);
      showFlashMessage(result.message || "Failed to add bookmark", 'error');
      return;
    }

    DEBUG.log("API call successful, showing success message...");
    
    // Reset search data to ensure fresh data after adding bookmark
    isDataLoaded = false;
    DEBUG.log('🔄 Search data reset after adding bookmark');
    
    // Show success message
    showFlashMessage("Bookmark added successfully!", 'success');
    
    // Close modal
    closeQuickAddModal();
    
    // Close popup if this is a popup window
    if (window.opener && !window.opener.closed) {
      window.close();
    }

  } catch (error) {
    console.error("=== ERROR IN QUICK ADD FORM ===");
    console.error("Error details:", error);
    console.error("Error message:", error.message);
    console.error("Error stack:", error.stack);
    showFlashMessage("Error adding bookmark: " + error.message, 'error');
  }
  
});

// Open all bookmarks in category functionality
document.querySelectorAll('.open-all-category-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    const categoryId = btn.dataset.categoryId;
    const categorySection = btn.closest('section[data-category-id]');
    const bookmarkLinks = categorySection.querySelectorAll('a.bookmark-title[href]');
    
    if (bookmarkLinks.length > 0) {
      // Open all bookmarks in new tabs in current window
      bookmarkLinks.forEach(link => {
        if (link.href && link.href !== window.location.href) {
          window.open(link.href, '_blank');
        }
      });
      
      // Show feedback
      DEBUG.log(`Opened ${bookmarkLinks.length} bookmarks from category in new tabs`);
    }
  });
});
