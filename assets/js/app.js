document.addEventListener("DOMContentLoaded", () => {
  
  // Flash message functionality
  function showFlashMessage(message, type = 'info') {
    const flashMessage = document.getElementById('flashMessage');
    const flashIcon = document.getElementById('flashIcon');
    const flashText = document.getElementById('flashText');
    
    // Set icon and styling based on type
    const iconMap = {
      'success': '✅',
      'error': '❌',
      'warning': '⚠️',
      'info': 'ℹ️'
    };
    
    const colorMap = {
      'success': 'border-green-200 bg-green-50 text-green-800',
      'error': 'border-red-200 bg-red-50 text-red-800',
      'warning': 'border-yellow-200 bg-yellow-50 text-yellow-800',
      'info': 'border-blue-200 bg-blue-50 text-blue-800'
    };
    
    flashIcon.textContent = iconMap[type] || iconMap['info'];
    flashText.textContent = message;
    
    // Update styling
    const container = flashMessage.querySelector('div');
    container.className = `border rounded-lg shadow-lg px-6 py-4 flex items-center gap-3 ${colorMap[type] || colorMap['info']}`;
    
    // Show the message
    flashMessage.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      hideFlashMessage();
    }, 5000);
  }
  
  function hideFlashMessage() {
    const flashMessage = document.getElementById('flashMessage');
    flashMessage.classList.add('hidden');
  }
  
  // Add event listener for close button
  document.getElementById('flashClose')?.addEventListener('click', hideFlashMessage);
  
  // Global search functionality
  let allBookmarks = [];
  let searchTimeout = null;
  let currentSearchResults = [];
  let selectedResultIndex = -1;
  let isDataLoaded = false; // Track if data has been loaded
  
  // Initialize search functionality (EAGER LOADING - current approach)
  async function initializeSearch() {
    try {
      console.log('🔄 EAGER LOADING: Fetching all bookmarks on page load...');
      const response = await fetch('api/get-all-bookmarks.php');
      const data = await response.json();
      
      if (data.success) {
        allBookmarks = data.bookmarks;
        isDataLoaded = true;
        console.log(`✅ EAGER LOADING: Successfully loaded ${allBookmarks.length} bookmarks for search`);
      } else {
        console.error('❌ EAGER LOADING: Failed to load bookmarks for search:', data.message);
      }
    } catch (error) {
      console.error('❌ EAGER LOADING: Error loading bookmarks for search:', error);
    }
  }
  
  // Lazy loading version - load data only when user starts typing
  async function loadSearchDataIfNeeded() {
    if (isDataLoaded) {
      return; // Data already loaded
    }
    
    try {
      console.log('🔄 LAZY LOADING: Fetching all bookmarks on first search...');
      const response = await fetch('api/get-all-bookmarks.php');
      const data = await response.json();
      
      if (data.success) {
        allBookmarks = data.bookmarks;
        isDataLoaded = true;
        console.log(`✅ LAZY LOADING: Successfully loaded ${allBookmarks.length} bookmarks for search`);
      } else {
        console.error('❌ LAZY LOADING: Failed to load bookmarks for search:', data.message);
      }
    } catch (error) {
      console.error('❌ LAZY LOADING: Error loading bookmarks for search:', error);
    }
  }
  
  // Search function
  function performSearch(query) {
    if (query.length < 3) {
      hideSearchResults();
      return;
    }
    
    const searchTerm = query.toLowerCase();
    const results = allBookmarks.filter(bookmark => {
      const title = (bookmark.title || '').toLowerCase();
      const description = (bookmark.description || '').toLowerCase();
      const url = (bookmark.url || '').toLowerCase();
      const category = (bookmark.category_name || '').toLowerCase();
      const page = (bookmark.page_name || '').toLowerCase();
      
      return title.includes(searchTerm) || 
             description.includes(searchTerm) || 
             url.includes(searchTerm) ||
             category.includes(searchTerm) ||
             page.includes(searchTerm);
    });
    
    currentSearchResults = results;
    selectedResultIndex = -1;
    displaySearchResults(results, query);
  }
  
  // Display search results
  function displaySearchResults(results, query) {
    const container = document.getElementById('searchResultsContent');
    const overlay = document.getElementById('searchResults');
    
    if (results.length === 0) {
      container.innerHTML = `
        <div class="p-6 text-center text-gray-500">
          <div class="text-4xl mb-2">🔍</div>
          <p class="text-lg font-medium">No results found</p>
          <p class="text-sm">Try different keywords or check your spelling</p>
        </div>
      `;
    } else {
      container.innerHTML = `
        <div class="p-4">
          <div class="text-sm text-gray-500 mb-4">
            Found ${results.length} result${results.length === 1 ? '' : 's'} for "${query}"
          </div>
          <div class="space-y-2">
            ${results.map((bookmark, index) => `
              <div class="search-result-item p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors" 
                   data-index="${index}" 
                   data-url="${bookmark.url}">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0">
                    <img src="${bookmark.favicon_url || 'favicon.png'}" 
                         alt="" 
                         class="w-6 h-6 rounded border border-black-200"
                         onerror="this.src='favicon.png'">
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-900 bookmark-title mt-0">${highlightSearchTerm(bookmark.title, query)}</div>
                    ${bookmark.description ? `<div class="text-sm text-gray-600 mt-1">${highlightSearchTerm(bookmark.description, query)}</div>` : ''}
                    <div class="text-xs text-gray-400 mt-1">
                      <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">${bookmark.category_name}</span>
                      <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs ml-1">${bookmark.page_name}</span>
                    </div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }
    
    overlay.classList.remove('hidden');
    
    // Add click handlers to search results
    document.querySelectorAll('.search-result-item').forEach(item => {
      item.addEventListener('click', () => {
        const url = item.dataset.url;
        window.open(url, '_blank');
        hideSearchResults();
        document.getElementById('globalSearch').value = '';
      });
    });
  }
  
  // Highlight search terms in results
  function highlightSearchTerm(text, query) {
    if (!text) return '';
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
  }
  
  // Hide search results
  function hideSearchResults() {
    document.getElementById('searchResults').classList.add('hidden');
    currentSearchResults = [];
    selectedResultIndex = -1;
  }
  
  // Handle keyboard navigation
  function handleSearchKeyboard(e) {
    if (!currentSearchResults.length) return;
    
    switch(e.key) {
      case 'ArrowDown':
        e.preventDefault();
        selectedResultIndex = Math.min(selectedResultIndex + 1, currentSearchResults.length - 1);
        updateSelectedResult();
        break;
      case 'ArrowUp':
        e.preventDefault();
        selectedResultIndex = Math.max(selectedResultIndex - 1, -1);
        updateSelectedResult();
        break;
      case 'Enter':
        e.preventDefault();
        if (currentSearchResults.length > 0) {
          // If no result is selected but there are results, select the first one
          const resultIndex = selectedResultIndex >= 0 ? selectedResultIndex : 0;
          if (currentSearchResults[resultIndex]) {
            window.open(currentSearchResults[resultIndex].url, '_blank');
            hideSearchResults();
            document.getElementById('globalSearch').value = '';
          }
        }
        break;
      case 'Escape':
        hideSearchResults();
        document.getElementById('globalSearch').blur();
        break;
    }
  }
  
  // Update selected result styling
  function updateSelectedResult() {
    document.querySelectorAll('.search-result-item').forEach((item, index) => {
      if (index === selectedResultIndex) {
        item.classList.add('bg-blue-50', 'border-blue-300');
      } else {
        item.classList.remove('bg-blue-50', 'border-blue-300');
      }
    });
  }
  
  // Initialize search (EAGER LOADING - current approach)
  // initializeSearch(); // ← Comment this out to test lazy loading
  
  // Search input event listeners
  const searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    searchInput.addEventListener('input', async (e) => {
      const query = e.target.value.trim();
      
      // Clear previous timeout
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
      
      // Set new timeout for debounced search
      searchTimeout = setTimeout(async () => {
        // LAZY LOADING: Load data if not already loaded
        if (!isDataLoaded) {
          await loadSearchDataIfNeeded();
        }
        
        performSearch(query);
      }, 300);
    });
    
    searchInput.addEventListener('keydown', handleSearchKeyboard);
    
    // Close search on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#searchResults') && !e.target.closest('#globalSearch')) {
        hideSearchResults();
      }
    });
  }
  
  // Close search button
  const closeSearchBtn = document.getElementById('closeSearch');
  if (closeSearchBtn) {
    closeSearchBtn.addEventListener('click', hideSearchResults);
  }
  
  // Page Dropdown functionality
  const pageDropdown = document.getElementById("pageDropdown");
  const pageDropdownMenu = document.getElementById("pageDropdownMenu");
  
  if (pageDropdown && pageDropdownMenu) {
    // Toggle dropdown on click
    pageDropdown.addEventListener("click", (e) => {
      e.stopPropagation();
      const isHidden = pageDropdownMenu.classList.contains("hidden");
      
      if (isHidden) {
        // Opening dropdown - rotate icon
        pageDropdownMenu.classList.remove("hidden");
        document.getElementById('pageDropdownIcon').style.transform = 'rotate(-45deg) translate(0, 10px)';
      } else {
        // Closing dropdown - reset icon
        pageDropdownMenu.classList.add("hidden");
        document.getElementById('pageDropdownIcon').style.transform = 'rotate(0deg) translate(0, 0)';
      }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!pageDropdown.contains(e.target) && !pageDropdownMenu.contains(e.target)) {
        pageDropdownMenu.classList.add("hidden");
        // Reset icon rotation when closing via outside click
        document.getElementById('pageDropdownIcon').style.transform = 'rotate(0deg) translate(0, 0)';
      }
    });
    
    // Handle page selection
    document.querySelectorAll(".page-option").forEach(option => {
      option.addEventListener("click", (e) => {
        e.preventDefault();
        const pageId = option.dataset.pageId;
        
        // Set cookie for the selected page
        document.cookie = `current_page_id=${pageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
        
        // Reload the page to show the new page's content
        window.location.reload();
      });
    });
  }
  
  // Section Expand/Collapse functionality
  const expandIndicators = document.querySelectorAll('.expand-indicator');

  expandIndicators.forEach(indicator => {
    console.log('Adding click listener to indicator:', indicator.dataset.sectionId);
    indicator.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent triggering drag events
      
      const sectionId = indicator.dataset.sectionId;    
      const section = document.querySelector(`section[data-category-id="${sectionId}"]`);    
      const content = section.querySelector('.section-content');
      
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
        }
        
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
  const contextAddPage = document.getElementById("contextAddPage");

  // --- Category Add Modal ---
  const categoryAddModal = document.getElementById("categoryAddModal");
  const categoryAddForm = document.getElementById("categoryAddForm");
  const categoryAddClose = document.getElementById("categoryAddClose");
  const categoryAddCancel = document.getElementById("categoryAddCancel");

  // --- Page Add Modal ---
  const pageAddModal = document.getElementById("pageAddModal");
  const pageAddForm = document.getElementById("pageAddForm");
  const pageAddClose = document.getElementById("pageAddClose");
  const pageAddCancel = document.getElementById("pageAddCancel");

  // --- Page Edit Modal ---
  const pageEditModal = document.getElementById("pageEditModal");
  const pageEditForm = document.getElementById("pageEditForm");
  const pageEditClose = document.getElementById("pageEditClose");
  const pageEditCancel = document.getElementById("pageEditCancel");
  const pageEditDelete = document.getElementById("pageEditDelete");

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
    document.getElementById("edit-category").value = data.category_id || "";
    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
  }

  function closeEditModal() {
    if (editModal) {
      editModal.classList.add("hidden");
      editModal.classList.remove("flex");
    }
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
    
    // Close popup if this is a popup window
    if (window.opener && !window.opener.closed) {
      window.close();
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

  function openCategoryEditModal(categoryId, categoryName, pageId) {
    console.log("Opening category edit modal for:", categoryName, "on page:", pageId);
    document.getElementById("category-edit-id").value = categoryId;
    document.getElementById("category-edit-name").value = categoryName;
    document.getElementById("category-edit-page").value = pageId || "";
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

  // --- Page Add Modal Functions ---
  function openPageAddModal() {
    console.log("Opening page add modal...");
    pageAddModal.classList.remove("hidden");
    pageAddModal.classList.add("flex");
    document.getElementById("page-add-name").focus();
  }

  function closePageAddModal() {
    console.log("Closing page add modal...");
    pageAddModal.classList.add("hidden");
    pageAddModal.classList.remove("flex");
    document.getElementById("page-add-name").value = "";
  }

  // --- Page Edit Modal Functions ---
  function openPageEditModal(pageId, pageName) {
    console.log("Opening page edit modal for:", pageName);
    document.getElementById("page-edit-id").value = pageId;
    document.getElementById("page-edit-name").value = pageName;
    pageEditModal.classList.remove("hidden");
    pageEditModal.classList.add("flex");
  }

  function closePageEditModal() {
    console.log("Closing page edit modal...");
    pageEditModal.classList.add("hidden");
    pageEditModal.classList.remove("flex");
    document.getElementById("page-edit-id").value = "";
    document.getElementById("page-edit-name").value = "";
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

  contextAddPage?.addEventListener("click", () => {
    hideContextMenu();
    openPageAddModal();
  });

  // --- Category Add Modal Event Listeners ---
  categoryAddClose?.addEventListener("click", closeCategoryAddModal);
  categoryAddCancel?.addEventListener("click", closeCategoryAddModal);

  // --- Page Add Modal Event Listeners ---
  pageAddClose?.addEventListener("click", closePageAddModal);
  pageAddCancel?.addEventListener("click", closePageAddModal);

  // --- Page Edit Modal Event Listeners ---
  pageEditClose?.addEventListener("click", closePageEditModal);
  pageEditCancel?.addEventListener("click", closePageEditModal);

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
      } else if (type === 'page') {
        apiEndpoint = "api/delete-page.php";
        successMessage = "Page deleted successfully!";
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
        } else if (type === 'page') {
          // For page deletion, handle the response to determine page redirection
          console.log("Page deleted, handling page transition...");
          
          // Check if we need to redirect to a different page
          if (result.wasCurrentPage && result.redirectPageId) {
            console.log(`Redirecting to page ${result.redirectPageId} after deletion`);
            // Set cookie for the redirect page
            document.cookie = `current_page_id=${result.redirectPageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
          }
          
          // Delay the reload to allow the flash message to be visible
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          // Find and remove the bookmark element
          const bookmarkElement = document.querySelector(`li[data-id='${id}']`);
          if (bookmarkElement) {
            const categoryId = bookmarkElement.closest('ul').dataset.categoryId;
            bookmarkElement.remove();
            console.log("Bookmark removed from DOM");
            
            // Update empty state for the category
            updateEmptyStates(categoryId);
          }
          // Also close edit modal if it's open
          if (editModal && !editModal.classList.contains("hidden")) {
            closeEditModal();
          }
        }
        
        // Reset search data to ensure fresh data after deletion
        isDataLoaded = false;
        console.log(`🔄 Search data reset after ${type} deletion`);
        
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
      });
    });
  });

  // --- Click category edit pencil or title: open category modal ---
  document.querySelectorAll("[data-action='edit-category']").forEach((element) => {
    element.addEventListener("click", () => {
      const id = element.dataset.id;
      const name = element.dataset.name;
      const pageId = element.dataset.pageId;
      openCategoryEditModal(id, name, pageId);
    });
  });

  // --- Click page edit button: open page edit modal ---
  document.querySelectorAll("#pageEditButton").forEach((element) => {
    element.addEventListener("click", () => {
      const pageId = element.dataset.pageId;
      const pageName = element.dataset.pageName;
      openPageEditModal(pageId, pageName);
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
      category_id: document.getElementById("edit-category").value,
    };

    try {
      const res = await fetch("api/edit.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await res.json();
      if (!result.success) {
        showFlashMessage(result.message || "Edit failed", 'error');
        return;
      }

      const li = document.querySelector(`li[data-id='${payload.id}']`);
      if (li) {
        li.dataset.title = payload.title;
        li.dataset.url = payload.url;
        li.dataset.description = payload.description;
        li.dataset.categoryId = payload.category_id;

        const link = li.querySelector("a");
        if (link) {
          link.textContent = payload.title;
          link.href = payload.url;
        }

        const desc = li.querySelector("p.text-xs");
        if (desc) {
          desc.textContent = payload.description;
        } else if (payload.description) {
          // Find the anchor tag to append the description to
          const link = li.querySelector("a");
          if (link) {
            const p = document.createElement("p");
            p.className = "text-xs text-gray-500 truncate";
            p.textContent = payload.description;
            link.appendChild(p);
          }
        }



        // If category changed, move the bookmark to the new category
        const oldCategoryId = li.closest('ul')?.dataset.categoryId;
        if (oldCategoryId && oldCategoryId !== payload.category_id) {
          // Find the target category list
          const targetList = document.querySelector(`ul[data-category-id='${payload.category_id}']`);
          if (targetList) {
            // Move the bookmark to the new category (same page)
            targetList.appendChild(li);
            
            // Update empty states for both categories
            updateEmptyStates(oldCategoryId);
            updateEmptyStates(payload.category_id);
          } else {
            // Target category not found on current page - bookmark moved to different page
            console.log('Bookmark moved to different page, reloading...');
            location.reload();
            return;
          }
        }
      }

      // Reset search data to ensure fresh data after edit
      isDataLoaded = false;
      console.log('🔄 Search data reset after bookmark edit');

      closeEditModal();
    } catch (error) {
      console.error("Error in edit form submission:", error);
      showFlashMessage("Error editing bookmark: " + error.message, 'error');
    }
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
        showFlashMessage(result.message || "Failed to add category", 'error');
        return;
      }

      closeCategoryAddModal();
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

  // --- Page Add form submission ---
  pageAddForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const pageName = document.getElementById("page-add-name").value;

    try {
      const res = await fetch("api/add-page.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: pageName }),
      });

      const result = await res.json();
      if (!result.success) {
        showFlashMessage(result.message || "Failed to add page", 'error');
        return;
      }

      closePageAddModal();
      showFlashMessage("Page added successfully!", 'success');
      
      // Delay the reload to allow the flash message to be visible
      setTimeout(() => {
        location.reload();
      }, 1500);
    } catch (error) {
      console.error("Error adding page:", error);
      showFlashMessage("Error adding page: " + error.message, 'error');
    }
  });

  // --- Page Edit form submission ---
  pageEditForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
      id: document.getElementById("page-edit-id").value,
      name: document.getElementById("page-edit-name").value,
    };

    try {
      const res = await fetch("api/edit-page.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await res.json();
      if (!result.success) {
        showFlashMessage(result.message || "Failed to update page", 'error');
        return;
      }

      // Update the page name in the DOM
      const pageEditButton = document.getElementById("pageEditButton");
      if (pageEditButton) {
        pageEditButton.textContent = payload.name;
        pageEditButton.dataset.pageName = payload.name;
      }

      // Reset search data to ensure fresh data after page edit
      isDataLoaded = false;
      console.log('🔄 Search data reset after page edit');

      closePageEditModal();
      showFlashMessage("Page updated successfully!", 'success');
    } catch (error) {
      console.error("Error updating page:", error);
      showFlashMessage("Error updating page: " + error.message, 'error');
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
      page_id: document.getElementById("category-edit-page").value,
    };

    try {
      const res = await fetch("api/edit-category.php", {
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
        // Category stayed on the same page - update the DOM
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
        showFlashMessage("Category updated successfully!", 'success');
      }

      // Reset search data to ensure fresh data after category edit
      isDataLoaded = false;
      console.log('🔄 Search data reset after category edit');

      closeCategoryEditModal();
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
        showFlashMessage(result.message || "Failed to add bookmark", 'error');
        return;
      }

      console.log("API call successful, showing success message...");
      
      // Reset search data to ensure fresh data after adding bookmark
      isDataLoaded = false;
      console.log('🔄 Search data reset after adding bookmark');
      
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

  // Password Change functionality
  const changePasswordLink = document.getElementById("changePasswordLink");
  const passwordChangeModal = document.getElementById("passwordChangeModal");
  const passwordChangeForm = document.getElementById("passwordChangeForm");
  const passwordChangeCancel = document.getElementById("passwordChangeCancel");
  const passwordChangeClose = document.getElementById("passwordChangeClose");

  function openPasswordChangeModal() {
    passwordChangeModal.classList.remove("hidden");
    passwordChangeModal.classList.add("flex");
    document.getElementById("current-password").focus();
  }

  function closePasswordChangeModal() {
    passwordChangeModal.classList.add("hidden");
    passwordChangeModal.classList.remove("flex");
    passwordChangeForm.reset();
  }

  if (changePasswordLink) {
    changePasswordLink.addEventListener("click", (e) => {
      e.preventDefault();
      openPasswordChangeModal();
    });
  }

  if (passwordChangeCancel) {
    passwordChangeCancel.addEventListener("click", closePasswordChangeModal);
  }

  if (passwordChangeClose) {
    passwordChangeClose.addEventListener("click", closePasswordChangeModal);
  }

  if (passwordChangeForm) {
    passwordChangeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      
      const currentPassword = document.getElementById("current-password").value;
      const newPassword = document.getElementById("new-password").value;
      const confirmPassword = document.getElementById("confirm-password").value;
      
      if (newPassword !== confirmPassword) {
        showFlashMessage("New passwords do not match!", 'error');
        return;
      }
      
      if (newPassword.length < 6) {
        showFlashMessage("New password must be at least 6 characters long!", 'error');
        return;
      }
      
      fetch("api/change-password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
          confirm_password: confirmPassword
        }),
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          showFlashMessage(result.message, 'success');
          closePasswordChangeModal();
          // Redirect to logout to force re-login
          window.location.href = "logout.php";
        } else {
          showFlashMessage("Error: " + result.message, 'error');
        }
      })
      .catch(error => {
        console.error("Error:", error);
        showFlashMessage("An error occurred while changing the password.", 'error');
      });
    });
  }
  
  // Open all bookmarks in category functionality
  document.querySelectorAll('.open-all-category-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      const categoryId = btn.dataset.categoryId;
      const categorySection = btn.closest('section[data-category-id]');
      const bookmarkLinks = categorySection.querySelectorAll('a[href]');
      
      if (bookmarkLinks.length > 0) {
        // Open all bookmarks in new tabs in current window
        bookmarkLinks.forEach(link => {
          if (link.href && link.href !== window.location.href) {
            window.open(link.href, '_blank');
          }
        });
        
        // Show feedback
        console.log(`Opened ${bookmarkLinks.length} bookmarks from category in new tabs`);
      }
    });
  });
});