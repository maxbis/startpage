// Flash message functionality
let currentMessageId = 0;

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
  
  const classMap = {
    'success': 'wp-alert--success',
    'error': 'wp-alert--error',
    'warning': 'wp-alert--warning',
    'info': 'wp-alert--info'
  };
  
  flashIcon.textContent = iconMap[type] || iconMap['info'];
  flashText.textContent = message;
  
  // Update styling
  const container = flashMessage.querySelector('div');
  container.className = `wp-alert wp-flash flash-panel ${classMap[type] || classMap['info']}`;
  container.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');
  
  // Show the message
  window.wpUiState.showFlashRegion(flashMessage);
  
  // Generate unique message ID
  const messageId = ++currentMessageId;
  
  // Auto-hide after 2 seconds (only for non-loading messages)
  if (type !== 'info') {
    setTimeout(() => {
      // Only hide if this is still the current message
      if (currentMessageId === messageId) {
        hideFlashMessage();
      }
    }, 2000);
  }
  
  return messageId;
}

function updateFlashMessage(messageId, message, type = 'info') {
  // Only update if this is the current message
  if (messageId === currentMessageId) {
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
    
    const classMap = {
      'success': 'wp-alert--success',
      'error': 'wp-alert--error',
      'warning': 'wp-alert--warning',
      'info': 'wp-alert--info'
    };
    
    flashIcon.textContent = iconMap[type] || iconMap['info'];
    flashText.textContent = message;
    
    // Update styling
    const container = flashMessage.querySelector('div');
    container.className = `wp-alert wp-flash flash-panel ${classMap[type] || classMap['info']}`;
    container.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');
    
    // Auto-hide after 2 seconds for non-loading messages
    if (type !== 'info') {
      setTimeout(() => {
        // Only hide if this is still the current message
        if (currentMessageId === messageId) {
          hideFlashMessage();
        }
      }, 2000);
    }
  }
}

function hideFlashMessage() {
  const flashMessage = document.getElementById('flashMessage');
  window.wpUiState.hideFlashRegion(flashMessage);
}

// Add event listener for close button
document.getElementById('flashClose')?.addEventListener('click', hideFlashMessage);

// Export functions for use in other modules
window.showFlashMessage = showFlashMessage;
window.hideFlashMessage = hideFlashMessage;
window.updateFlashMessage = updateFlashMessage;
