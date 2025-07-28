document.addEventListener("DOMContentLoaded", () => {
  
  // Section Expand/Collapse functionality
  console.log('Setting up expand/collapse functionality...');
  const expandIndicators = document.querySelectorAll('.expand-indicator');
  console.log('Found expand indicators:', expandIndicators.length);
  
  expandIndicators.forEach(indicator => {
    console.log('Adding click listener to indicator:', indicator.dataset.sectionId);
    indicator.addEventListener('click', (e) => {
      console.log('Expand indicator clicked!');
      e.stopPropagation(); // Prevent triggering drag events
      
      const sectionId = indicator.dataset.sectionId;
      console.log('Section ID:', sectionId);
      
      const section = document.querySelector(`section[data-category-id="${sectionId}"]`);
      console.log('Found section:', section);
      
      const content = section.querySelector('.section-content');
      console.log('Found content:', content);
      
      if (content.classList.contains('expanded')) {
        console.log('Collapsing section...');
        // Collapse
        content.classList.remove('expanded');
        indicator.classList.remove('expanded');
      } else {
        console.log('Expanding section...');
        // Expand
        content.classList.add('expanded');
        indicator.classList.add('expanded');
      }
    });
  });
  
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
        console.log("Category order changed:", categoryIds);
        
        fetch("api/reorder-categories.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            order: categoryIds,
          }),
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            console.log("Category order saved successfully");
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
      onEnd: function (evt) {
        const categoryId = evt.to.dataset.categoryId;
        const bookmarkIds = Array.from(evt.to.querySelectorAll("li")).map(
          (el) => el.dataset.id
        );
        fetch("api/reorder.php", {
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

  // Add Bookmark
  document.querySelectorAll(".add-bookmark-form").forEach((form) => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const url = form.querySelector("input[name='url']").value;
      const categoryId = form.dataset.category;

      const response = await fetch("api/add.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ url, category_id: categoryId }),
      });

      const result = await response.json();
      if (result.success) {
        location.reload();
      } else {
        alert("Failed to add bookmark: " + result.message);
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

  // --- Modal setup ---
  const editModal = document.getElementById("editModal");
  const editForm = document.getElementById("editForm");
  const editClose = document.getElementById("editClose");
  const editCancel = document.getElementById("editCancel");
  const editDelete = document.getElementById("editDelete");
  
  // Quick Add Modal setup
  const quickAddModal = document.getElementById("quickAddModal");
  const quickAddForm = document.getElementById("quickAddForm");
  const quickAddClose = document.getElementById("quickAddClose");
  const quickAddCancel = document.getElementById("quickAddCancel");
  
  // Delete Modal setup
  const deleteModal = document.getElementById("deleteModal");
  const deleteConfirm = document.getElementById("deleteConfirm");
  const deleteCancel = document.getElementById("deleteCancel");
  const deleteClose = document.getElementById("deleteClose");
  const deleteBookmarkTitle = document.getElementById("deleteBookmarkTitle");
  
  // Category Edit Modal setup
  // --- Context Menu ---
  const contextMenu = document.getElementById("contextMenu");
  const contextAddLink = document.getElementById("contextAddLink");
  const contextAddCategory = document.getElementById("contextAddCategory");

  // --- Category Add Modal ---
  const categoryAddModal = document.getElementById("categoryAddModal");
  const categoryAddForm = document.getElementById("categoryAddForm");
  const categoryAddClose = document.getElementById("categoryAddClose");
  const categoryAddCancel = document.getElementById("categoryAddCancel");

  // --- Category Edit Modal ---
  const categoryEditModal = document.getElementById("categoryEditModal");
  const categoryEditForm = document.getElementById("categoryEditForm");
  const categoryEditClose = document.getElementById("categoryEditClose");
  const categoryEditCancel = document.getElementById("categoryEditCancel");
  const categoryEditDelete = document.getElementById("categoryEditDelete");

  function openEditModal(data) {
    document.getElementById("edit-id").value = data.id;
    document.getElementById("edit-title").value = data.title || "";
    document.getElementById("edit-url").value = data.url || "";
    document.getElementById("edit-description").value = data.description || "";
    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
  }

  function closeEditModal() {
    editModal.classList.add("hidden");
    editModal.classList.remove("flex");
  }

  function openQuickAddModal() {
    console.log("Opening quick add modal...");
    quickAddModal.classList.remove("hidden");
    quickAddModal.classList.add("flex");
    document.getElementById("quick-url").focus();
  }

  function closeQuickAddModal() {
    quickAddModal.classList.add("hidden");
    quickAddModal.classList.remove("flex");
    // Clear URL parameters
    if (window.history.replaceState) {
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  function openDeleteModal(itemId, itemTitle, itemType = 'bookmark') {
    console.log("Opening delete modal for:", itemTitle, "type:", itemType);
    deleteBookmarkTitle.textContent = itemTitle;
    deleteConfirm.dataset.id = itemId;
    deleteConfirm.dataset.type = itemType;
    deleteModal.classList.remove("hidden");
    deleteModal.classList.add("flex");
  }

  function closeDeleteModal() {
    console.log("Closing delete modal...");
    deleteModal.classList.add("hidden");
    deleteModal.classList.remove("flex");
    deleteBookmarkTitle.textContent = "";
    deleteConfirm.dataset.id = "";
    deleteConfirm.dataset.type = "";
  }

  function openCategoryEditModal(categoryId, categoryName) {
    console.log("Opening category edit modal for:", categoryName);
    document.getElementById("category-edit-id").value = categoryId;
    document.getElementById("category-edit-name").value = categoryName;
    categoryEditModal.classList.remove("hidden");
    categoryEditModal.classList.add("flex");
  }

  function closeCategoryEditModal() {
    console.log("Closing category edit modal...");
    categoryEditModal.classList.add("hidden");
    categoryEditModal.classList.remove("flex");
    document.getElementById("category-edit-id").value = "";
    document.getElementById("category-edit-name").value = "";
  }

  // --- Category Add Modal Functions ---
  function openCategoryAddModal() {
    console.log("Opening category add modal...");
    categoryAddModal.classList.remove("hidden");
    categoryAddModal.classList.add("flex");
    document.getElementById("category-add-name").focus();
  }

  function closeCategoryAddModal() {
    console.log("Closing category add modal...");
    categoryAddModal.classList.add("hidden");
    categoryAddModal.classList.remove("flex");
    document.getElementById("category-add-name").value = "";
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

  editClose?.addEventListener("click", closeEditModal);
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

  // --- Category Add Modal Event Listeners ---
  categoryAddClose?.addEventListener("click", closeCategoryAddModal);
  categoryAddCancel?.addEventListener("click", closeCategoryAddModal);

  categoryEditClose?.addEventListener("click", closeCategoryEditModal);
  categoryEditCancel?.addEventListener("click", closeCategoryEditModal);
  quickAddClose?.addEventListener("click", closeQuickAddModal);
  quickAddCancel?.addEventListener("click", closeQuickAddModal);
  
  // Delete modal event listeners
  deleteClose?.addEventListener("click", closeDeleteModal);
  deleteCancel?.addEventListener("click", closeDeleteModal);
  
  // Delete confirmation
  deleteConfirm?.addEventListener("click", async () => {
    const id = deleteConfirm.dataset.id;
    const type = deleteConfirm.dataset.type || 'bookmark';
    console.log("Deleting", type, "with ID:", id);
    
    try {
      let apiEndpoint, successMessage;
      
      if (type === 'category') {
        apiEndpoint = "api/delete-category.php";
        successMessage = "Category deleted successfully!";
      } else {
        apiEndpoint = "api/delete.php";
        successMessage = "Bookmark deleted successfully!";
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
            console.log("Category removed from DOM");
          }
        } else {
          // Find and remove the bookmark element
          const bookmarkElement = document.querySelector(`li[data-id='${id}']`);
          if (bookmarkElement) {
            bookmarkElement.remove();
            console.log("Bookmark removed from DOM");
          }
          // Also close edit modal if it's open
          if (editModal && !editModal.classList.contains("hidden")) {
            closeEditModal();
          }
        }
        
        closeDeleteModal();
        console.log(successMessage);
      } else {
        alert("Delete failed: " + (result.message || "Unknown error"));
      }
    } catch (error) {
      console.error("Error deleting", type + ":", error);
      alert("Error deleting " + type + ": " + error.message);
    }
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
      });
    });
  });

  // --- Click category edit pencil or title: open category modal ---
  document.querySelectorAll("[data-action='edit-category']").forEach((element) => {
    element.addEventListener("click", () => {
      const id = element.dataset.id;
      const name = element.dataset.name;
      openCategoryEditModal(id, name);
    });
  });

  // --- Submit form to edit bookmark ---
  editForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
      id: document.getElementById("edit-id").value,
      title: document.getElementById("edit-title").value,
      url: document.getElementById("edit-url").value,
      description: document.getElementById("edit-description").value,
    };

    const res = await fetch("api/edit.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const result = await res.json();
    if (!result.success) {
      alert(result.message || "Edit failed");
      return;
    }

    const li = document.querySelector(`li[data-id='${payload.id}']`);
    if (li) {
      li.dataset.title = payload.title;
      li.dataset.url = payload.url;
      li.dataset.description = payload.description;

      const link = li.querySelector("a");
      if (link) {
        link.textContent = payload.title;
        link.href = payload.url;
      }

      const desc = li.querySelector("p.text-xs");
      if (desc) {
        desc.textContent = payload.description;
      } else if (payload.description) {
        const block = li.querySelector("div > div");
        const p = document.createElement("p");
        p.className = "text-xs text-gray-500";
        p.textContent = payload.description;
        block.appendChild(p);
      }
    }

    closeEditModal();
  });

  // --- Category Add form submission ---
  categoryAddForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const categoryName = document.getElementById("category-add-name").value;

    try {
      const res = await fetch("api/add-category.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: categoryName }),
      });

      const result = await res.json();
      if (!result.success) {
        alert(result.message || "Failed to add category");
        return;
      }

      closeCategoryAddModal();
      alert("Category added successfully!");
      
      // Reload the page to show the new category with proper event listeners
      location.reload();
    } catch (error) {
      console.error("Error adding category:", error);
      alert("Error adding category: " + error.message);
    }
  });

  // --- Right-click Context Menu ---
  document.addEventListener('contextmenu', (e) => {
    // Only show context menu if clicking on the main container or empty space
    const target = e.target;
    const isOnCategory = target.closest('section[data-category-id]');
    const isOnBookmark = target.closest('li[data-id]');
    const isOnForm = target.closest('form');
    const isOnButton = target.closest('button');
    
    // Don't show context menu on categories, bookmarks, forms, or buttons
    if (isOnCategory || isOnBookmark || isOnForm || isOnButton) {
      return;
    }
    
    e.preventDefault();
    showContextMenu(e.clientX, e.clientY);
  });

  // Hide context menu when clicking elsewhere
  document.addEventListener('click', (e) => {
    if (!contextMenu.contains(e.target)) {
      hideContextMenu();
    }
  });

  // Hide context menu when pressing Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      hideContextMenu();
    }
  });

  // --- Category Edit form submission ---
  categoryEditForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
      id: document.getElementById("category-edit-id").value,
      name: document.getElementById("category-edit-name").value,
    };

    try {
      const res = await fetch("api/edit-category.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await res.json();
      if (!result.success) {
        alert(result.message || "Failed to update category");
        return;
      }

      // Update the category name in the DOM
      const categorySection = document.querySelector(`section[data-category-id='${payload.id}']`);
      if (categorySection) {
        const titleElement = categorySection.querySelector("h2");
        if (titleElement) {
          titleElement.textContent = payload.name;
        }
        // Update the button data attribute
        const editButton = categorySection.querySelector("button[data-action='edit-category']");
        if (editButton) {
          editButton.dataset.name = payload.name;
        }
      }

      closeCategoryEditModal();
      alert("Category updated successfully!");
    } catch (error) {
      console.error("Error updating category:", error);
      alert("Error updating category: " + error.message);
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

  // --- Quick Add form submission ---
  quickAddForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Get form elements
    const urlInput = document.getElementById("quick-url");
    const titleInput = document.getElementById("quick-title");
    const descInput = document.getElementById("quick-description");
    const categoryInput = document.getElementById("quick-category");

    console.log("Form elements found:", {
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

    console.log("Payload constructed:", payload);
    console.log("Payload JSON:", JSON.stringify(payload));

    try {
      console.log("Making fetch request to: api/add.php");
      console.log("Request details:", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const res = await fetch("api/add.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      console.log("Fetch response received:", {
        status: res.status,
        statusText: res.statusText,
        ok: res.ok,
        headers: Object.fromEntries(res.headers.entries())
      });

      const result = await res.json();
      console.log("API response parsed:", result);
      
      if (!result.success) {
        console.error("API returned error:", result.message);
        alert(result.message || "Failed to add bookmark");
        return;
      }

      console.log("API call successful, showing success message...");
      // Show success message
      alert("Bookmark added successfully! ID: " + result.id + "\n\nPlease refresh the main startpage to see your new bookmark.");
      
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
      alert("Error adding bookmark: " + error.message);
    }
    
  });
});