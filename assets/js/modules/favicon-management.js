// --- Favicon refresh functionality ---
const editRefreshFaviconBtn = document.getElementById('edit-refresh-favicon');

if (editRefreshFaviconBtn) {
  editRefreshFaviconBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    
    const url = document.getElementById('edit-url').value;
    if (!url) {
      showFlashMessage('Please enter a URL first', 'error');
      return;
    }
    
    // Show loading state
    const originalText = editRefreshFaviconBtn.innerHTML;
    editRefreshFaviconBtn.innerHTML = `
      <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
      </svg>
      Refreshing...
    `;
    editRefreshFaviconBtn.disabled = true;
    
    try {
      const response = await fetch('../api/refresh-favicon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url })
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Update the favicon display
        const faviconImg = document.getElementById('edit-favicon');
        const faviconUrl = document.getElementById('edit-favicon-url');
        
        if (faviconImg) {
          // Handle cached favicon URLs correctly for display
          let faviconSrc = result.favicon_url;
          if (faviconSrc && faviconSrc.startsWith('cache/')) {
            faviconSrc = '../' + faviconSrc;
          }
          faviconImg.src = faviconSrc;
          
          // Log the favicon source for debugging
          DEBUG.log('Updated favicon src:', faviconSrc);
        }
        if (faviconUrl) {
          faviconUrl.textContent = result.original_url;
          faviconUrl.title = result.original_url; // Show full URL on hover
        }
        
        showFlashMessage('Favicon refreshed successfully!', 'success');
      } else {
        showFlashMessage(result.message || 'Failed to refresh favicon', 'error');
      }
    } catch (error) {
      console.error('Error refreshing favicon:', error);
      showFlashMessage('Error refreshing favicon: ' + error.message, 'error');
    } finally {
      // Restore button state
      editRefreshFaviconBtn.innerHTML = originalText;
      editRefreshFaviconBtn.disabled = false;
    }
  });
}
