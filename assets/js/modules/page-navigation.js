// Page Navigation functionality
let allPages = [];
let currentPageIndex = -1;

// Get all pages and current page index
function initializePageNavigation() {
  const pageOptions = document.querySelectorAll(".page-option");
  allPages = Array.from(pageOptions).map(option => ({
    id: option.dataset.pageId,
    name: option.textContent.trim()
  }));
  
  // Find current page index by looking for the checkmark
  const currentPageOption = document.querySelector('.page-option span:first-child');
  if (currentPageOption && currentPageOption.textContent === '✓') {
    const currentPageId = currentPageOption.closest('.page-option').dataset.pageId;
    currentPageIndex = allPages.findIndex(page => page.id === currentPageId);
  }
  
  // If not found, try to get from cookie
  if (currentPageIndex === -1) {
    const cookies = document.cookie.split(';').reduce((acc, cookie) => {
      const [key, value] = cookie.trim().split('=');
      acc[key] = value;
      return acc;
    }, {});
    
    const currentPageId = cookies.startpage_current_page_id;
    if (currentPageId) {
      currentPageIndex = allPages.findIndex(page => page.id === currentPageId);
    }
  }
  
  // Fallback to first page
  if (currentPageIndex === -1) {
    currentPageIndex = 0;
  }
  
  // Update page counter display
  updatePageCounter();
}

// Update page counter display
function updatePageCounter() {
  const currentPageNum = document.getElementById('currentPageNum');
  const totalPages = document.getElementById('totalPages');
  const prevPageBtn = document.getElementById('prevPageBtn');
  const nextPageBtn = document.getElementById('nextPageBtn');
  const pageCounter = document.getElementById('pageCounter');
  
  if (currentPageNum && totalPages) {
    currentPageNum.textContent = currentPageIndex + 1;
    totalPages.textContent = allPages.length;
  }
  
  // Hide navigation elements if there's only one page
  if (allPages.length <= 1) {
    if (prevPageBtn) prevPageBtn.style.display = 'none';
    if (nextPageBtn) nextPageBtn.style.display = 'none';
    if (pageCounter) pageCounter.style.display = 'none';
  } else {
    if (prevPageBtn) prevPageBtn.style.display = 'block';
    if (nextPageBtn) nextPageBtn.style.display = 'block';
    if (pageCounter) pageCounter.style.display = 'block';
  }
}

// Navigate to next page
function navigateToNextPage() {
  if (allPages.length <= 1) return;
  
  const nextIndex = (currentPageIndex + 1) % allPages.length;
  navigateToPageByIndex(nextIndex);
}

// Navigate to previous page
function navigateToPreviousPage() {
  if (allPages.length <= 1) return;
  
  const prevIndex = currentPageIndex === 0 ? allPages.length - 1 : currentPageIndex - 1;
  navigateToPageByIndex(prevIndex);
}

// Navigate to page by index
function navigateToPageByIndex(index) {
  if (index < 0 || index >= allPages.length) return;
  
  const pageId = allPages[index].id;
  
  // Set cookie for the selected page
  document.cookie = `startpage_current_page_id=${pageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
  
  // Reload the page to show the new page's content
  window.location.reload();
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
  // Only handle if not in input fields or modals
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || 
      e.target.tagName === 'SELECT' || document.querySelector('.modal:not(.hidden)')) {
    return;
  }
  
  if (e.key === 'ArrowLeft') {
    e.preventDefault();
    navigateToPreviousPage();
  } else if (e.key === 'ArrowRight') {
    e.preventDefault();
    navigateToNextPage();
  }
});

// Initialize page navigation
initializePageNavigation();

// Add click handlers for navigation buttons
const prevPageBtn = document.getElementById('prevPageBtn');
const nextPageBtn = document.getElementById('nextPageBtn');

if (prevPageBtn) {
  prevPageBtn.addEventListener('click', (e) => {
    e.preventDefault();
    navigateToPreviousPage();
  });
}

if (nextPageBtn) {
  nextPageBtn.addEventListener('click', (e) => {
    e.preventDefault();
    navigateToNextPage();
  });
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
  
  // Handle page selection - use event delegation to handle clicks on nested elements
  pageDropdownMenu.addEventListener("click", (e) => {
    // Find the closest page-option button (handles clicks on nested spans)
    const pageOption = e.target.closest(".page-option");
    if (!pageOption) return;
    
    e.preventDefault();
    e.stopPropagation(); // Prevent document click handler from closing dropdown
    
    const pageId = pageOption.dataset.pageId;
    if (!pageId) return;
    
    // Set cookie for the selected page
    document.cookie = `startpage_current_page_id=${pageId}; path=/; max-age=${365 * 24 * 60 * 60}`;
    
    // Reload the page to show the new page's content
    window.location.reload();
  });
}

// Export functions for use in other modules
window.allPages = allPages;
window.currentPageIndex = currentPageIndex;
window.initializePageNavigation = initializePageNavigation;
window.updatePageCounter = updatePageCounter;
window.navigateToNextPage = navigateToNextPage;
window.navigateToPreviousPage = navigateToPreviousPage;
window.navigateToPageByIndex = navigateToPageByIndex;
