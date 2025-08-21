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

// Update bookmark display - simplified approach
function updateBookmarkDisplay(bookmarkId, data) {
  const bookmark = document.querySelector(`li[data-id="${bookmarkId}"]`);
  if (!bookmark) return;
  
  // Always update all data attributes
  bookmark.dataset.title = data.title || '';
  bookmark.dataset.url = data.url || '';
  bookmark.dataset.description = data.description || '';
  bookmark.dataset.categoryId = data.category_id || '';
  bookmark.dataset.faviconUrl = data.favicon_url || '';
  bookmark.dataset.backgroundColor = data.background_color || 'none';
  if (typeof data.color !== 'undefined') {
    bookmark.dataset.color = String(parseInt(data.color || 0, 10) || 0);
  }
  
  // Always update the visible elements
  const link = bookmark.querySelector("a");
  if (link) {
    link.textContent = data.title || '';
    link.href = data.url || '';
    
    // Handle description - remove existing and add new if provided
    const existingDesc = link.querySelector("p.text-xs");
    if (existingDesc) {
      existingDesc.remove();
    }
    
    if (data.description) {
      const desc = document.createElement("p");
      desc.className = "text-xs text-gray-500 truncate";
      desc.textContent = data.description;
      link.appendChild(desc);
    }
  }
  
  // Always update favicon
  const faviconImg = bookmark.querySelector("img");
  if (faviconImg && data.favicon_url) {
    let displayUrl = data.favicon_url;
    if (displayUrl.startsWith('cache/')) {
      displayUrl = '../' + displayUrl;
    }
    faviconImg.src = displayUrl;
  }
  
  // Always handle category changes
  if (data.category_id) {
    const newCategory = document.querySelector(`ul[data-category-id="${data.category_id}"]`);
    if (newCategory && bookmark.closest('ul')?.dataset.categoryId !== data.category_id) {
      newCategory.appendChild(bookmark);
    }
    
    // Update bookmark display to respect category settings (show/hide description, favicon)
    const categorySection = document.querySelector(`section[data-category-id="${data.category_id}"]`);
    const categoryTitle = categorySection?.querySelector('h2');
    
    if (categoryTitle) {
      const showFavicon = categoryTitle.dataset.showFavicon === "1";
      const showDescription = categoryTitle.dataset.noDescription === "0";
      
      // Update favicon visibility
      if (faviconImg) {
        faviconImg.style.display = showFavicon ? '' : 'none';
      }
      
      // Update description visibility
      const description = link?.querySelector("p.text-xs");
      if (description) {
        description.style.display = showDescription ? '' : 'none';
      }
    }
  }
  
  // Always update background color if provided (token) or numeric mapped
  if (data.background_color || typeof data.color !== 'undefined') {
    // Remove all existing background color classes dynamically
    const bgClasses = window.bookmarkBgClasses || [];
    bookmark.classList.remove(...bgClasses);
    
    // Add the new background color class
    const intToToken = window.bookmarkColorMapping || {};
    const backgroundColor = data.background_color || intToToken[(parseInt(data.color || 0, 10) || 0)] || 'none';
    bookmark.classList.add(`bookmark-bg-${backgroundColor}`);
    
    // Update the data attribute
    bookmark.dataset.backgroundColor = backgroundColor;

    // Warn in console if CSS class is missing or ineffective
    warnIfBgClassMissing(backgroundColor);
  }
}

// Update bookmark display for category settings
function updateBookmarkDisplayForCategory(bookmark, categoryId) {
  const categorySection = document.querySelector(`section[data-category-id="${categoryId}"]`);
  const categoryTitle = categorySection?.querySelector('h2');
  
  if (categoryTitle) {
    const showFavicon = categoryTitle.dataset.showFavicon === "1";
    const showDescription = categoryTitle.dataset.noDescription === "0";
    
    // Update favicon visibility
    const faviconImg = bookmark.querySelector("img");
    if (faviconImg) {
      faviconImg.style.display = showFavicon ? '' : 'none';
    }
    
    // Update description visibility
    const link = bookmark.querySelector("a");
    const description = link?.querySelector("p.text-xs");
    if (description) {
      description.style.display = showDescription ? '' : 'none';
    }
  }
}

// Update bookmark category - moves bookmark to new category and updates display
function updateBookmarkCategory(bookmark, newCategoryId, originalCategoryId) {
  if (!bookmark || !newCategoryId) return;
  
  // Update the bookmark's data attribute
  bookmark.dataset.categoryId = newCategoryId;
  
  // Update bookmark display to match new category settings
  updateBookmarkDisplayForCategory(bookmark, newCategoryId);
  
  DEBUG.log("DRAG-DROP", `Bookmark moved from category ${originalCategoryId} to ${newCategoryId}`);
}


// Update page display
function updatePageDisplay(pageId, data) {
  const pageButton = document.querySelector(`#pageEditButton[data-page-id="${pageId}"]`);
  if (pageButton && data.name) {
    pageButton.dataset.pageName = data.name;
    pageButton.textContent = data.name;
  }
}

// Warn if a bookmark background token has no matching CSS class
function warnIfBgClassMissing(token) {
  const expectedClass = `bookmark-bg-${token}`;
  // Heuristic: create a temp element, apply the class, and check computed background
  const temp = document.createElement('div');
  temp.style.display = 'none';
  temp.className = expectedClass;
  document.body.appendChild(temp);
  const bgColor = window.getComputedStyle(temp).backgroundColor;
  document.body.removeChild(temp);

  // If token is not 'none' and computed color is transparent or empty, warn
  const isTransparent = !bgColor || bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent';
  if (token !== 'none' && isTransparent) {
    // Provide actionable guidance
    console.warn(
      `⚠️ Bookmark color token "${token}" has no matching CSS. ` +
      `Ensure a CSS class ".${expectedClass}" is defined. ` +
      `If you manage colors in CSS file assets/css/features/bookmark-colors.css, add:
.${expectedClass} { background-color: var(--pastel-${token}); }`
    );
  }
}

// Mobile detection utility - improved to handle touch-enabled laptops correctly
function isMobile() {
  // 1. Check screen width first (most reliable indicator)
  if (window.innerWidth <= 768) {
    console.log('isMobile based on viewport<=768');
    return true;
  }
  
  // 2. Check for browser simulation mode (viewport artificially small)
  if (window.innerWidth < 800 && window.screen.width > 800) {
    console.log('isMobile based on viewport<800 && screen>800');
    return true;
  }
  
  // 3. Check user agent for mobile devices
  const userAgent = navigator.userAgent.toLowerCase();
  const mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'blackberry', 'windows phone'];
  if (mobileKeywords.some(keyword => userAgent.includes(keyword))) {
    console.log('isMobile based on user agent:'+userAgent);
    return true;
  }
  
  // 4. Only use touch capability as a last resort for very small screens
  // This prevents touch-enabled laptops from being incorrectly detected as mobile
  if (window.innerWidth <= 1024 && ('ontouchstart' in window || navigator.maxTouchPoints > 0)) {
    console.log('isMobile based obwindow.innerWidth <= 1024 and touchscreen');
    return true;
  }
  console.log('isMobile FALSE');
  return false;
}

// Force mobile mode for testing (can be called from console)
function forceMobileMode() {
  window.FORCE_MOBILE_MODE = true;
  
  // Trigger a resize event to reinitialize everything
  window.dispatchEvent(new Event('resize'));
  
  return 'Mobile mode forced. Refresh page or resize window to see changes.';
}

// Force desktop mode for testing (can be called from console)
function forceDesktopMode() {
  window.FORCE_MOBILE_MODE = false;
  
  // Trigger a resize event to reinitialize everything
  window.dispatchEvent(new Event('resize'));
  
  return 'Desktop mode forced. Refresh page or resize window to see changes.';
}

// Detect browser simulation mode
function detectSimulationMode() {
  const viewportWidth = window.innerWidth;
  const screenWidth = window.screen.width;
  const isSimulated = viewportWidth < 800 && screenWidth > 800;
  
  return {
    viewportWidth,
    screenWidth,
    isSimulated
  };
}

// Export functions for use in other modules
window.updateCategoryDisplay = updateCategoryDisplay;
window.updateCategoryTitle = updateCategoryTitle;
window.updateCategorySettings = updateCategorySettings;
window.updateBookmarkDisplay = updateBookmarkDisplay;
window.updatePageDisplay = updatePageDisplay;
window.updateBookmarkDisplayForCategory = updateBookmarkDisplayForCategory;
window.updateBookmarkCategory = updateBookmarkCategory;
window.initializeBookmarkBackgroundColors = initializeBookmarkBackgroundColors;

// Export mobile detection functions
window.isMobile = isMobile;
window.forceMobileMode = forceMobileMode;
window.forceDesktopMode = forceDesktopMode;
window.detectSimulationMode = detectSimulationMode;

// Initialize background colors for existing bookmarks
function initializeBookmarkBackgroundColors() {
  const bookmarks = document.querySelectorAll('li[data-id]');
  console.log('🎨 Initializing background colors for', bookmarks.length, 'bookmarks');
  bookmarks.forEach(bookmark => {
    const backgroundColor = bookmark.dataset.backgroundColor || 'none';
    // Remove all existing background color classes dynamically
    const bgClasses = window.bookmarkBgClasses || [];
    bookmark.classList.remove(...bgClasses);
    // Add the appropriate background color class
    bookmark.classList.add(`bookmark-bg-${backgroundColor}`);
  });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Small delay to ensure all modules are loaded
  setTimeout(initializeBookmarkBackgroundColors, 100);
});
