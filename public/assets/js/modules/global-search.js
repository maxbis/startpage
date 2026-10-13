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
    const response = await fetch('../api/get-all-bookmarks.php');
    const data = await response.json();
    
    if (data.success) {
      allBookmarks = data.bookmarks;
      isDataLoaded = true;
      DEBUG.log(`✅ EAGER LOADING: Successfully loaded ${allBookmarks.length} bookmarks for search`);
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
    const response = await fetch('../api/get-all-bookmarks.php');
    const data = await response.json();
    
    if (data.success) {
      allBookmarks = data.bookmarks;
      isDataLoaded = true;
      DEBUG.log(`✅ LAZY LOADING: Successfully loaded ${allBookmarks.length} bookmarks for search`);
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
    hideSearchResultsWithoutClearing();
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

// Format favicon URL for display using the shared helper.
function formatFaviconUrl(faviconUrl, bookmarkUrl = '') {
  return window.formatBookmarkFaviconUrl
    ? window.formatBookmarkFaviconUrl(faviconUrl, bookmarkUrl)
    : faviconUrl;
}

// Display search results
function displaySearchResults(results, query) {
  const container = document.getElementById('searchResultsContent');
  const overlay = document.getElementById('searchResults');
  
  if (results.length === 0) {
    container.innerHTML = `
      <div class="search-empty-state">
        <div class="search-empty-state__icon">🔍</div>
        <p class="search-empty-state__title">No results found</p>
        <p class="search-empty-state__message">Try different keywords or check your spelling</p>
      </div>
    `;
  } else {
    container.innerHTML = `
      <div class="search-results-list">
        <div class="search-results-summary">
          Found ${results.length} result${results.length === 1 ? '' : 's'} for "${query}"
        </div>
        <div class="search-results-items">
          ${results.map((bookmark, index) => `
            <div class="search-result-item"
                 data-index="${index}"
                 data-bookmark-id="${bookmark.id}"
                 data-url="${bookmark.url}">
              <div class="search-result-row">
                <div class="search-result-icon">
                  <img src="${formatFaviconUrl(bookmark.favicon_url, bookmark.url)}" 
                       alt="" 
                       data-bookmark-url="${bookmark.url}"
                       class="search-result-favicon"
                       onerror="return window.handleFaviconImageError(this)">
                </div>
                <div class="search-result-content">
                  <div class="search-result-title bookmark-title">${highlightSearchTerm(bookmark.title, query)}</div>
                  ${bookmark.description ? `<div class="search-result-description">${highlightSearchTerm(bookmark.description, query)}</div>` : ''}
                  <div class="search-result-meta">
                    <span class="search-result-chip search-result-chip--category">${bookmark.category_name}</span>
                    <span class="search-result-chip">${bookmark.page_name}</span>
                  </div>
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }
  
  window.wpUiState.openDialog(overlay);
  
  // Add click handlers to search results
  document.querySelectorAll('.search-result-item').forEach(item => {
    item.addEventListener('click', () => {
      const url = item.dataset.url;
      window.trackBookmarkClick?.(item.dataset.bookmarkId);
      window.open(url, '_blank');
      hideSearchResults();
    });
  });
}

// Highlight search terms in results
function highlightSearchTerm(text, query) {
  if (!text) return '';
  const regex = new RegExp(`(${query})`, 'gi');
  return text.replace(regex, '<mark class="search-highlight">$1</mark>');
}

// Hide search results without clearing input (for short queries)
function hideSearchResultsWithoutClearing() {
  window.wpUiState.closeDialog(document.getElementById('searchResults'));
  currentSearchResults = [];
  selectedResultIndex = -1;
}

// Hide search results
function hideSearchResults() {
  const searchResults = document.getElementById('searchResults');
  const wasVisible = window.wpUiState.isDialogOpen(searchResults);
  window.wpUiState.closeDialog(searchResults);
  currentSearchResults = [];
  selectedResultIndex = -1;
  // Clear the search input when hiding results
  const searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    searchInput.value = '';
    if (wasVisible) searchInput.focus();
  }
}

// Handle keyboard navigation
function handleSearchKeyboard(e) {
  if (e.key === 'Escape') {
    hideSearchResults();
    return;
  }

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
          window.trackBookmarkClick?.(currentSearchResults[resultIndex].id);
          window.open(currentSearchResults[resultIndex].url, '_blank');
          hideSearchResults();
        }
      }
      break;
  }
}

function invalidateSearchData() {
  allBookmarks = [];
  currentSearchResults = [];
  selectedResultIndex = -1;
  isDataLoaded = false;
  hideSearchResults();
  window.allBookmarks = allBookmarks;
  window.isDataLoaded = false;
}

// Update selected result styling
function updateSelectedResult() {
  document.querySelectorAll('.search-result-item').forEach((item, index) => {
    if (index === selectedResultIndex) {
      item.classList.add('is-selected');
    } else {
      item.classList.remove('is-selected');
    }
  });
}

// Initialize search (EAGER LOADING - current approach)
// initializeSearch(); // ← Comment this out to test lazy loading

// Search input event listeners
const searchInput = document.getElementById('globalSearch');
if (searchInput) {
  const shortcutHint = document.getElementById('searchShortcutHint');
  const isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
  if (shortcutHint) shortcutHint.textContent = isMac ? '⌘K' : 'Ctrl K';

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
}

document.addEventListener('keydown', (event) => {
  const target = event.target;
  const isEditable = target instanceof HTMLElement && (
    target.matches('input, textarea, select') || target.isContentEditable
  );
  const isSearchShortcut = (event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey))
    || (event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey);

  if (!isSearchShortcut || isEditable) return;
  event.preventDefault();

  const mobileToggle = document.getElementById('mobileSearchToggle');
  const searchBox = document.querySelector('.header-search');
  if (searchBox && getComputedStyle(searchBox).display === 'none' && mobileToggle) {
    mobileToggle.click();
  }
  requestAnimationFrame(() => searchInput?.focus());
});

// Close search button
const closeSearchBtn = document.getElementById('closeSearch');
if (closeSearchBtn) {
  closeSearchBtn.addEventListener('click', hideSearchResults);
}

// Export functions and variables for use in other modules
window.allBookmarks = allBookmarks;
window.isDataLoaded = isDataLoaded;
window.initializeSearch = initializeSearch;
window.loadSearchDataIfNeeded = loadSearchDataIfNeeded;
window.performSearch = performSearch;
window.formatFaviconUrl = formatFaviconUrl;
window.displaySearchResults = displaySearchResults;
window.highlightSearchTerm = highlightSearchTerm;
window.hideSearchResultsWithoutClearing = hideSearchResultsWithoutClearing;
window.hideSearchResults = hideSearchResults;
window.handleSearchKeyboard = handleSearchKeyboard;
window.updateSelectedResult = updateSelectedResult;
window.invalidateSearchData = invalidateSearchData;
