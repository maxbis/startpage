// ===== DOM UPDATE FUNCTIONS =====

// Update category display (name and settings)
function updateCategoryDisplay(categoryId, data) {
  const category = document.querySelector(`section[data-category-id="${categoryId}"]`);
  if (!category) return;
  
  updateCategoryTitle(category, data.name);
  updateCategorySettings(category, data);
}

// Update category title
function updateCategoryTitle(category, newName) {
  const titleElement = category.querySelector("h2");
  if (titleElement) {
    titleElement.textContent = newName;
  }
}

// Update category settings (width, description, favicon preferences)
function updateCategorySettings(category, data) {
  const titleElement = category.querySelector("h2");
  if (titleElement) {
    if (data.width) titleElement.dataset.width = data.width;
    if (data.no_description !== undefined) titleElement.dataset.noDescription = data.no_description;
    if (data.show_favicon !== undefined) titleElement.dataset.showFavicon = data.show_favicon;
  }
  
  // Update edit button data attributes
  const editButton = category.querySelector("button[data-action='edit-category']");
  if (editButton) {
    if (data.name) editButton.dataset.name = data.name;
    if (data.width) editButton.dataset.width = data.width;
    if (data.no_description !== undefined) editButton.dataset.noDescription = data.no_description;
    if (data.show_favicon !== undefined) editButton.dataset.showFavicon = data.show_favicon;
  }
}

// Update bookmark display
function updateBookmarkDisplay(bookmarkId, data) {
  const bookmark = document.querySelector(`li[data-id="${bookmarkId}"]`);
  if (!bookmark) return;
  
  updateBookmarkTitle(bookmark, data.title);
  updateBookmarkUrl(bookmark, data.url);
  updateBookmarkDescription(bookmark, data.description);
  updateBookmarkFavicon(bookmark, data.favicon_url);
  updateBookmarkCategory(bookmark, data.category_id);
  
  // Update bookmark display to respect category settings (show/hide description, favicon)
  updateBookmarkDisplayForCategory(bookmark, data.category_id);
}

// Update bookmark title
function updateBookmarkTitle(bookmark, newTitle) {
  const link = bookmark.querySelector("a");
  if (link) {
    link.textContent = newTitle;
    link.href = newTitle; // Update title attribute too
  }
  bookmark.dataset.title = newTitle;
}

// Update bookmark URL
function updateBookmarkUrl(bookmark, newUrl) {
  const link = bookmark.querySelector("a");
  if (link) {
    link.href = newUrl;
  }
  bookmark.dataset.url = newUrl;
}

// Update bookmark description
function updateBookmarkDescription(bookmark, newDescription) {
  bookmark.dataset.description = newDescription;
  
  const link = bookmark.querySelector("a");
  if (link) {
    // Remove existing description
    const existingDesc = link.querySelector("p.text-xs");
    if (existingDesc) {
      existingDesc.remove();
    }
    
    // Add new description if provided
    if (newDescription) {
      const desc = document.createElement("p");
      desc.className = "text-xs text-gray-500 truncate";
      desc.textContent = newDescription;
      link.appendChild(desc);
    }
  }
}

// Update bookmark favicon
function updateBookmarkFavicon(bookmark, newFaviconUrl) {
  bookmark.dataset.faviconUrl = newFaviconUrl;
  
  const faviconImg = bookmark.querySelector('img');
  if (faviconImg && newFaviconUrl) {
    let displayFaviconUrl = newFaviconUrl;
    if (displayFaviconUrl.startsWith('cache/')) {
      displayFaviconUrl = '../' + displayFaviconUrl;
    }
    faviconImg.src = displayFaviconUrl;
  }
}

// Update bookmark display based on category settings
function updateBookmarkDisplayForCategory(bookmark, categoryId) {
  const categorySection = document.querySelector(`section[data-category-id="${categoryId}"]`);
  const categoryTitle = categorySection?.querySelector('h2');
  
  if (categoryTitle) {
    const showFavicon = categoryTitle.dataset.showFavicon === "1";
    const showDescription = categoryTitle.dataset.noDescription === "0";
    
    // Update favicon visibility
    const faviconImg = bookmark.querySelector('img');
    if (faviconImg) {
      faviconImg.style.display = showFavicon ? '' : 'none';
    }
    
    // Update description visibility
    const description = bookmark.querySelector('p.text-xs');
    if (description) {
      description.style.display = showDescription ? '' : 'none';
    }
  }
}

// Update bookmark category
function updateBookmarkCategory(bookmark, newCategoryId, originalCategoryId = null) {
  const oldCategoryId = originalCategoryId || bookmark.closest('ul')?.dataset.categoryId;
  DEBUG.log('Updating bookmark category:', oldCategoryId, '->', newCategoryId);
  if (oldCategoryId && oldCategoryId !== newCategoryId) {
    // Move bookmark to new category (only if not already moved by Sortable.js)
    if (!originalCategoryId) {
      const newCategory = document.querySelector(`ul[data-category-id="${newCategoryId}"]`);
      if (newCategory) {
        newCategory.appendChild(bookmark);
      }
    }
    
    // Update bookmark display to match new category settings
    updateBookmarkDisplayForCategory(bookmark, newCategoryId);
  }
  bookmark.dataset.categoryId = newCategoryId;
}

// Update page display
function updatePageDisplay(pageId, data) {
  const pageButton = document.querySelector(`#pageEditButton[data-page-id="${pageId}"]`);
  if (pageButton && data.name) {
    pageButton.dataset.pageName = data.name;
    pageButton.textContent = data.name;
  }
}

// Export functions for use in other modules
window.updateCategoryDisplay = updateCategoryDisplay;
window.updateCategoryTitle = updateCategoryTitle;
window.updateCategorySettings = updateCategorySettings;
window.updateBookmarkDisplay = updateBookmarkDisplay;
window.updateBookmarkTitle = updateBookmarkTitle;
window.updateBookmarkUrl = updateBookmarkUrl;
window.updateBookmarkDescription = updateBookmarkDescription;
window.updateBookmarkFavicon = updateBookmarkFavicon;
window.updateBookmarkDisplayForCategory = updateBookmarkDisplayForCategory;
window.updateBookmarkCategory = updateBookmarkCategory;
window.updatePageDisplay = updatePageDisplay;
