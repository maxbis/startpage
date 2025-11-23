/**
 * Click Tracking Module
 * Tracks clicks on bookmark links
 */

// Track clicks on bookmarks
function initializeClickTracking() {
  document.addEventListener('click', function(e) {
    // Find the closest anchor tag with class 'bookmark-title' or inside a bookmark item
    const link = e.target.closest('a.bookmark-title');
    
    if (link) {
      // Find the parent li to get the ID
      const li = link.closest('li[data-id]');
      if (li) {
        const bookmarkId = li.dataset.id;
        trackClick(bookmarkId);
      }
    }
  });
}

function trackClick(bookmarkId) {
  if (!bookmarkId) return;
  
  fetch('../api/track_click.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ id: bookmarkId })
  })
  .then(response => response.json())
  .then(data => {
    if (window.DEBUG && window.DEBUG.isEnabledFor('CLICK')) {
      console.log(`[CLICK] Tracked click for bookmark ${bookmarkId}`, data);
    }
  })
  .catch(error => {
    console.error('Error tracking click:', error);
  });
}

// Initialize immediately
initializeClickTracking();

if (window.DEBUG) {
    window.DEBUG.log('CLICK', 'Click tracking initialized');
}
