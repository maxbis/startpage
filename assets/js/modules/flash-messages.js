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
  
  // Auto-hide after 2 seconds
  setTimeout(() => {
    hideFlashMessage();
  }, 2000);
}

function hideFlashMessage() {
  const flashMessage = document.getElementById('flashMessage');
  flashMessage.classList.add('hidden');
}

// Add event listener for close button
document.getElementById('flashClose')?.addEventListener('click', hideFlashMessage);

// Export functions for use in other modules
window.showFlashMessage = showFlashMessage;
window.hideFlashMessage = hideFlashMessage;
