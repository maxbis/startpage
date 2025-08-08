// Global Debugging System
window.DEBUG = {
  enabled: false,
  
  log: function(module, ...args) {
    if (this.enabled) {
      console.log(`[${module}]`, ...args);
    }
  },
  
  toggle: function() {
    this.enabled = !this.enabled;
    console.log(`Global debug logging ${this.enabled ? 'enabled' : 'disabled'}`);
    return this.enabled;
  },
  
  // Enable debug for specific modules
  enableFor: function(modules) {
    if (typeof modules === 'string') {
      modules = [modules];
    }
    this.enabledModules = this.enabledModules || [];
    this.enabledModules.push(...modules);
    console.log(`Debug enabled for modules: ${modules.join(', ')}`);
  },
  
  // Check if debug is enabled for a specific module
  isEnabledFor: function(module) {
    return this.enabled && (!this.enabledModules || this.enabledModules.includes(module));
  },
  
  // Help function to show usage instructions
  help: function() {
    console.log(`
🔧 DEBUG SYSTEM HELP
===================

📋 Current Status:
- Debug enabled: ${this.enabled}
- Enabled modules: ${this.enabledModules ? this.enabledModules.join(', ') : 'none'}

🚀 Quick Commands:
• DEBUG.enabled = true          - Enable global debugging
• DEBUG.enabled = false         - Disable global debugging  
• DEBUG.toggle()                - Toggle debugging on/off
• DEBUG.help()                  - Show this help message

🎯 Module-Specific Debugging:
• DEBUG.enableFor('MODAL')      - Enable debug for modal operations
• DEBUG.enableFor(['MODAL', 'BOOKMARK']) - Enable for multiple modules
• DEBUG.isEnabledFor('MODAL')   - Check if MODAL debugging is enabled

📝 Available Modules:
• MODAL     - Modal management operations
• BOOKMARK  - Bookmark CRUD operations  
• CATEGORY  - Category management
• PAGE      - Page management
• SEARCH    - Global search operations
• NAVIGATION - Page navigation
• DRAGDROP  - Drag and drop operations

💡 Example Usage:
1. Enable debugging: DEBUG.enabled = true
2. Open a modal (edit bookmark, add category, etc.)
3. Watch console for [MODAL] prefixed logs
4. Disable when done: DEBUG.enabled = false

🔍 What You'll See:
[MODAL] Opening category edit modal for: My Category
[MODAL] Deleting bookmark with ID: 123
[MODAL] Bookmark removed from DOM
    `);
  }
};

document.addEventListener("DOMContentLoaded", () => {
  
  // List of all modules to load - in order of dependency
  const modules = [
    'flash-messages.js',
    'global-search.js', 
    'page-navigation.js',
    'drag-drop.js',
    'section-management.js',
    'utils.js',
    'modal-management.js',
    'bookmark-management.js',
    'category-management.js',
    'page-management.js',
    'context-menu.js',
    'password-management.js',
    'favicon-management.js'
  ];

  // Function to load a module
  function loadModule(moduleName) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = `../assets/js/modules/${moduleName}`;
      script.onload = () => {
        // console.log(`✅ Loaded module: ${moduleName}`);
        resolve();
      };
      script.onerror = () => {
        console.error(`❌ Failed to load module: ${moduleName}`);
        reject(new Error(`Failed to load module: ${moduleName}`));
      };
      document.head.appendChild(script);
    });
  }

  // Load all modules sequentially
  async function loadAllModules() {
    DEBUG.log('🚀 Starting to load modules...');
    
    try {
      for (const module of modules) {
        await loadModule(module);
      }
    
      DEBUG.log('✅ All modules loaded successfully!');
      console.log('type DEBUG.help() to see debug options');
      
      // Initialize search functionality (EAGER LOADING - current approach)
      // initializeSearch(); // ← Comment this out to test lazy loading
      
    } catch (error) {
      console.error('❌ Error loading modules:', error);
    }
  }

  // Start loading modules
  loadAllModules();
});