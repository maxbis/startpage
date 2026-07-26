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

// Add a function to check if mobile functions are ready.
window.checkMobileFunctionsReady = function () {
  return typeof window.forceMobileMode === 'function';
};

// Add a function that waits for mobile functions and then executes a command.
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
