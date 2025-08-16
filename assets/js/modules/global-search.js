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

// Format favicon URL for display (matches PHP logic)
function formatFaviconUrl(faviconUrl) {
  if (!faviconUrl) {
    return window.faviconConfig?.defaultFaviconDataUri || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgcng9IjQiIGZpbGw9IiNmMGYwZjAiLz4KICAgIDx0ZXh0IHg9IjE2IiB5PSIyMiIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjMzMzMzMzIj7wn5KrPC90ZXh0Pgo8L3N2Zz4=';
  }
    
  // If it's a cached favicon, add the relative path
  if (faviconUrl.startsWith('cache/')) {
    return '../' + faviconUrl;
  }
    
  return faviconUrl;
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
            <div class="search-result-item p-3 rounded-lg border border-gray-200 hover:bg-blue-50 cursor-pointer transition-colors" 
                 data-index="${index}" 
                 data-url="${bookmark.url}">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <img src="${formatFaviconUrl(bookmark.favicon_url)}" 
                       alt="" 
                       class="w-6 h-6 rounded border border-black-200"
                       onerror="this.src=window.faviconConfig?.defaultFaviconDataUri || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgcng9IjQiIGZpbGw9IiNmMGYwZjAiLz4KICAgIDx0ZXh0IHg9IjE2IiB5PSIyMiIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjMzMzMzMzIj7wn5KrPC90ZXh0Pgo8L3N2Zz4='">
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
    });
  });
}

// Highlight search terms in results
function highlightSearchTerm(text, query) {
  if (!text) return '';
  const regex = new RegExp(`(${query})`, 'gi');
  return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
}

// Hide search results without clearing input (for short queries)
function hideSearchResultsWithoutClearing() {
  document.getElementById('searchResults').classList.add('hidden');
  currentSearchResults = [];
  selectedResultIndex = -1;
}

// Hide search results
function hideSearchResults() {
  document.getElementById('searchResults').classList.add('hidden');
  currentSearchResults = [];
  selectedResultIndex = -1;
  // Clear the search input when hiding results
  const searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    searchInput.value = '';
  }
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
      item.classList.add('bg-blue-50', 'border-blue-500');
    } else {
      item.classList.remove('bg-blue-50', 'border-blue-500');
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
}

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
