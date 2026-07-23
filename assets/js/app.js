// Global Debugging System
window.DEBUG = {
  enabled: false,

  log: function (module, ...args) {
    if (this.enabled) {
      console.log(`[${module}]`, ...args);
    }
  },

  toggle: function () {
    this.enabled = !this.enabled;
    console.log(`Global debug logging ${this.enabled ? 'enabled' : 'disabled'}`);
    return this.enabled;
  },

  // Enable debug for specific modules
  enableFor: function (modules) {
    if (typeof modules === 'string') {
      modules = [modules];
    }
    this.enabledModules = this.enabledModules || [];
    this.enabledModules.push(...modules);
    console.log(`Debug enabled for modules: ${modules.join(', ')}`);
  },

  // Check if debug is enabled for a specific module
  isEnabledFor: function (module) {
    return this.enabled && (!this.enabledModules || this.enabledModules.includes(module));
  },

  // Help function to show usage instructions
  help: function () {
    console.log(`
🔧 DEBUG SYSTEM HELP
===================

📋 Current Status:
- Debug enabled: ${this.enabled}
- Enabled modules: ${this.enabledModules ? this.enabledModules.join(', ') : 'none'}

📱Functions for testing mobile/desktop mode:
- forceMobileMode()        // Force mobile mode (disable drag & drop)
- forceDesktopMode()       // Force desktop mode (enable drag & drop)
- detectSimulationMode()   // Check if browser is simulating mobile
- testDragAndDropStatus()  // Test current drag & drop status
- checkMobileFunctionsReady() // Check if functions are loaded
- waitForMobileFunctions("command") // Wait and execute a command

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
    'utils.js',           // Load utils.js first - contains isMobile function
    'tooltips.js',
    'global-search.js',
    'page-navigation.js',
    'section-management.js',
    'drag-drop.js',       // Uses the category columns created by section-management.js
    'modal-management.js',
    'bookmark-management.js',
    'bookmark-actions.js',
    'category-management.js',
    'trash-management.js',
    'page-management.js',
    'context-menu.js',
    'password-management.js',
    'account-menu.js',
    'favicon-management.js',
    'click-tracking.js'
  ];

  // Function to load a module
  function loadModule(moduleName) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      const version = encodeURIComponent(window.moduleAssetVersion || '1');
      script.src = `../assets/js/modules/${moduleName}?v=${version}`;
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

      // Initialize search functionality (EAGER LOADING - current approach)
      // initializeSearch(); // ← Comment this out to test lazy loading

    } catch (error) {
      console.error('❌ Error loading modules:', error);
    }
  }

  // Start loading modules
  loadAllModules();

  // Add a function to check if mobile functions are ready
  window.checkMobileFunctionsReady = function () {
    if (typeof window.forceMobileMode === 'function') {
      return true;
    } else {
      return false;
    }
  };

  // Add a function that waits for mobile functions and then executes a command
  window.waitForMobileFunctions = function (command) {
    if (typeof window.forceMobileMode === 'function') {
      // Functions are ready, execute the command
      return eval(command);
    } else {
      // Functions not ready, wait and retry
      setTimeout(() => {
        if (typeof window.forceMobileMode === 'function') {
          return eval(command);
        }
      }, 1000);
    }
  };
});
