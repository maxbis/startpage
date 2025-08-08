# JavaScript Modules Technical Documentation

## Overview

This document provides comprehensive technical documentation for all JavaScript modules in the `assets/js/modules/` directory. These modules implement the client-side functionality for the startpage application, providing a modular architecture for maintainability and scalability.

## Module Architecture

### Loading Order
Modules are loaded sequentially in `app.js` with the following order to ensure proper dependencies:

```javascript
const modules = [
  'flash-messages.js',        // Core utility - must be first
  'global-search.js',         // Independent functionality
  'page-navigation.js',       // Independent functionality
  'drag-drop.js',            // Independent functionality
  'section-management.js',    // Independent functionality
  'utils.js',                // Core utility functions
  'modal-management.js',      // Modal system
  'bookmark-management.js',   // Bookmark operations
  'category-management.js',   // Category operations
  'page-management.js',       // Page operations
  'context-menu.js',         // Context menu
  'password-management.js',   // Password operations
  'favicon-management.js'     // Favicon operations
];
```

### Dependencies
- **Critical Dependencies**: `flash-messages.js`, `utils.js`, `modal-management.js`
- **Independent Modules**: `global-search.js`, `page-navigation.js`, `drag-drop.js`, `section-management.js`, `context-menu.js`

## Module Documentation

---

## 1. flash-messages.js (49 lines)

**Purpose**: Provides user feedback through flash messages with different types and auto-hide functionality.

### Key Functions

#### `showFlashMessage(message, type = 'info')`
- **Purpose**: Displays a flash message to the user
- **Parameters**:
  - `message` (string): The message text to display
  - `type` (string): Message type - 'success', 'error', 'warning', 'info'
- **Features**:
  - Auto-hides after 2 seconds
  - Different styling based on message type
  - Icon mapping for each message type

#### `hideFlashMessage()`
- **Purpose**: Manually hides the flash message
- **Usage**: Called automatically or by user interaction

### Message Types
- **Success**: Green styling with ✅ icon
- **Error**: Red styling with ❌ icon  
- **Warning**: Yellow styling with ⚠️ icon
- **Info**: Blue styling with ℹ️ icon

### Global Exports
```javascript
window.showFlashMessage = showFlashMessage;
window.hideFlashMessage = hideFlashMessage;
```

---

## 2. global-search.js (287 lines)

**Purpose**: Implements comprehensive global search functionality across all bookmarks with lazy loading and keyboard navigation.

### Key Variables
- `allBookmarks[]`: Array of all bookmarks for search
- `searchTimeout`: Debounce timer for search input
- `currentSearchResults[]`: Current search results
- `selectedResultIndex`: Currently selected result for keyboard navigation
- `isDataLoaded`: Tracks if search data has been loaded

### Key Functions

#### `initializeSearch()`
- **Purpose**: Eager loading - fetches all bookmarks on page load
- **API**: Calls `../api/get-all-bookmarks.php`

#### `loadSearchDataIfNeeded()`
- **Purpose**: Lazy loading - fetches bookmarks only when user starts typing
- **Usage**: Called on first search input

#### `performSearch(query)`
- **Purpose**: Performs search across bookmark data
- **Search Fields**: title, description, url, category_name, page_name
- **Minimum Query**: 3 characters required
- **Features**: Case-insensitive search with highlighting

#### `displaySearchResults(results, query)`
- **Purpose**: Renders search results with favicon and metadata
- **Features**:
  - Shows category and page tags
  - Handles empty results gracefully
  - Click to open bookmarks in new tab

#### `handleSearchKeyboard(e)`
- **Purpose**: Keyboard navigation for search results
- **Keys**:
  - Arrow Up/Down: Navigate results
  - Enter: Open selected result
  - Escape: Close search

### Search Features
- **Debounced Input**: 300ms delay to prevent excessive API calls
- **Lazy Loading**: Data loaded only when needed
- **Keyboard Navigation**: Full keyboard support
- **Result Highlighting**: Search terms highlighted in results
- **Favicon Support**: Displays favicons in search results

### Global Exports
```javascript
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
```

---

## 3. page-navigation.js (188 lines)

**Purpose**: Handles page navigation, page switching, and page management functionality.

### Key Variables
- `allPages[]`: Array of all available pages
- `currentPageIndex`: Index of currently active page

### Key Functions

#### `initializePageNavigation()`
- **Purpose**: Initializes page navigation system
- **Features**:
  - Detects current page from DOM or cookies
  - Updates page counter display
  - Handles single-page scenarios

#### `navigateToNextPage()`
- **Purpose**: Navigate to next page (circular)
- **Features**: Wraps around to first page

#### `navigateToPreviousPage()`
- **Purpose**: Navigate to previous page (circular)
- **Features**: Wraps around to last page

#### `navigateToPageByIndex(index)`
- **Purpose**: Navigate to specific page by index
- **Features**: Sets cookie and reloads page

#### `updatePageCounter()`
- **Purpose**: Updates page counter display
- **Features**: Hides navigation for single-page setups

### Keyboard Navigation
- **Arrow Left**: Previous page
- **Arrow Right**: Next page
- **Conditions**: Only when not in input fields or modals

### Page Dropdown
- **Toggle**: Click to open/close dropdown
- **Icon Rotation**: Visual feedback for dropdown state
- **Outside Click**: Closes dropdown
- **Page Selection**: Sets cookie and reloads

### Cookie Management
- **Cookie Name**: `current_page_id`
- **Duration**: 1 year
- **Path**: Root path

### Global Exports
```javascript
window.allPages = allPages;
window.currentPageIndex = currentPageIndex;
window.initializePageNavigation = initializePageNavigation;
window.updatePageCounter = updatePageCounter;
window.navigateToNextPage = navigateToNextPage;
window.navigateToPreviousPage = navigateToPreviousPage;
window.navigateToPageByIndex = navigateToPageByIndex;
```

---

## 4. drag-drop.js (112 lines)

**Purpose**: Implements drag-and-drop functionality for categories and bookmarks using Sortable.js library.

### Category Drag & Drop
- **Container**: `#categories-container`
- **API Endpoint**: `../api/reorder-categories.php`
- **Features**:
  - Visual feedback during drag
  - Animation: 150ms
  - Ghost class: `opacity-50`
  - Chosen class: `shadow-lg`

### Bookmark Drag & Drop
- **Container**: All `ul[data-category-id]` elements
- **API Endpoint**: `../api/reorder.php`
- **Features**:
  - Group: "bookmarks" (allows cross-category dragging)
  - Filter: `.no-drag` (prevents dragging from certain elements)
  - Icon-only dragging: Must start drag from favicon
  - Cross-category support
  - Empty state management

#### `updateEmptyStates(categoryId)`
- **Purpose**: Manages empty state display for categories
- **Features**:
  - Shows "📭 No bookmarks yet" when empty
  - Removes empty state when bookmarks exist
  - Handles both source and target categories during drag

### Drag Restrictions
- **Bookmarks**: Must start drag from favicon image
- **Categories**: No restrictions
- **Visual Feedback**: Opacity and shadow effects

### Global Exports
```javascript
window.updateEmptyStates = updateEmptyStates;
```

---

## 5. section-management.js (26 lines)

**Purpose**: Handles expand/collapse functionality for category sections.

### Key Features
- **Expand Indicators**: Click to expand/collapse sections
- **Visual Feedback**: Icon rotation and state changes
- **Event Handling**: Prevents drag events from triggering
- **State Management**: Tracks expanded/collapsed state

### Event Listeners
- **Target**: All `.expand-indicator` elements
- **Action**: Toggle `expanded` class on section content
- **Prevention**: Stops event propagation to prevent drag conflicts

### CSS Classes
- **Expanded**: `.expanded` class on content and indicator
- **Collapsed**: Default state (no `.expanded` class)

---

## 6. utils.js (172 lines)

**Purpose**: Provides utility functions for DOM updates and data manipulation across the application.

### Category Update Functions

#### `updateCategoryDisplay(categoryId, data)`
- **Purpose**: Updates category display with new data
- **Calls**: `updateCategoryTitle()` and `updateCategorySettings()`

#### `updateCategoryTitle(category, newName)`
- **Purpose**: Updates category title in DOM

#### `updateCategorySettings(category, data)`
- **Purpose**: Updates category settings (width, description, favicon preferences)
- **Data Attributes**: `data-width`, `data-noDescription`, `data-showFavicon`

### Bookmark Update Functions

#### `updateBookmarkDisplay(bookmarkId, data)`
- **Purpose**: Comprehensive bookmark update
- **Calls**: All individual bookmark update functions
- **Features**: Updates display based on category settings

#### `updateBookmarkTitle(bookmark, newTitle)`
- **Purpose**: Updates bookmark title and link text

#### `updateBookmarkUrl(bookmark, newUrl)`
- **Purpose**: Updates bookmark URL

#### `updateBookmarkDescription(bookmark, newDescription)`
- **Purpose**: Updates bookmark description
- **Features**: Removes existing description before adding new one

#### `updateBookmarkFavicon(bookmark, newFaviconUrl)`
- **Purpose**: Updates bookmark favicon
- **Features**: Handles cached favicon URLs

#### `updateBookmarkDisplayForCategory(bookmark, categoryId)`
- **Purpose**: Updates bookmark display based on category settings
- **Features**:
  - Shows/hides favicon based on category setting
  - Shows/hides description based on category setting

#### `updateBookmarkCategory(bookmark, newCategoryId, originalCategoryId)`
- **Purpose**: Moves bookmark to new category
- **Features**: Updates display for new category settings

### Page Update Functions

#### `updatePageDisplay(pageId, data)`
- **Purpose**: Updates page display in DOM

### Global Exports
```javascript
window.updateCategoryDisplay = updateCategoryDisplay;
window.updateCategoryTitle = updateCategoryTitle;
window.updateCategorySettings = updateCategorySettings;
window.updateBookmarkDisplay = updateBookmarkDisplay;
window.updateBookmarkTitle = updateBookmarkTitle;
window.updateBookmarkUrl = updateBookmarkUrl;
window.updateBookmarkDescription = updateBookmarkDescription;
window.updateBookmarkFavicon = updateBookmarkFavicon;
window.updateBookmarkDisplayForCategory = updateBookmarkDisplayForCategory;
window.updateBookmarkCategory = updateBookmarkCategory;
window.updatePageDisplay = updatePageDisplay;
```

---

## 7. modal-management.js (378 lines)

**Purpose**: Comprehensive modal management system for all application modals.

### Modal Elements
- **Edit Modal**: Bookmark editing
- **Quick Add Modal**: Quick bookmark addition
- **Delete Modal**: Confirmation dialogs
- **Category Add/Edit Modals**: Category management
- **Page Add/Edit Modals**: Page management
- **Context Menu**: Right-click context menu

### Generic Modal Functions

#### `showModal(modalElement, focusElement = null)`
- **Purpose**: Generic modal display function
- **Features**: Focus management, flex display

#### `hideModal(modalElement, resetFields = [])`
- **Purpose**: Generic modal hiding function
- **Features**: Field reset, hidden display

### Specialized Modal Functions

#### `openEditModal(data)`
- **Purpose**: Opens bookmark edit modal
- **Features**:
  - Populates form fields
  - Handles favicon display
  - URL hover tooltips
  - Favicon URL management

#### `openQuickAddModal()`
- **Purpose**: Opens quick add modal
- **Features**: URL parameter clearing, popup handling

#### `openDeleteModal(itemId, itemTitle, itemType)`
- **Purpose**: Opens delete confirmation modal
- **Types**: 'bookmark', 'category', 'page'

#### `openCategoryEditModal(categoryId, categoryName, pageId, width, noDescription, showFavicon)`
- **Purpose**: Opens category edit modal
- **Features**: Checkbox state management

#### `openPageEditModal(pageId, pageName)`
- **Purpose**: Opens page edit modal

### Context Menu Functions

#### `showContextMenu(x, y)`
- **Purpose**: Shows context menu at coordinates

#### `hideContextMenu()`
- **Purpose**: Hides context menu

### Delete Confirmation Handler
- **API Endpoints**:
  - Bookmarks: `../api/delete-bookmark.php`
  - Categories: `../api/delete-category.php`
  - Pages: `../api/delete-page.php`
- **Features**:
  - DOM element removal
  - Empty state updates
  - Page redirection handling
  - Search data reset

### Global Exports
```javascript
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
window.openQuickAddModal = openQuickAddModal;
window.closeQuickAddModal = closeQuickAddModal;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.openCategoryEditModal = openCategoryEditModal;
window.closeCategoryEditModal = closeCategoryEditModal;
window.openCategoryAddModal = openCategoryAddModal;
window.closeCategoryAddModal = closeCategoryAddModal;
window.openPageAddModal = openPageAddModal;
window.closePageAddModal = closePageAddModal;
window.openPageEditModal = openPageEditModal;
window.closePageEditModal = closePageEditModal;
window.showContextMenu = showContextMenu;
window.hideContextMenu = hideContextMenu;
```

---

## 8. bookmark-management.js (220 lines)

**Purpose**: Handles all bookmark-related operations including CRUD operations and quick add functionality.

### Add Bookmark
- **Form**: `.add-bookmark-form`
- **API**: `../api/add.php`
- **Features**: Category-specific form handling

### Delete Bookmark
- **Trigger**: `button[data-action='delete']`
- **Flow**: Opens delete confirmation modal
- **API**: `../api/delete-bookmark.php`

### Edit Bookmark
- **Trigger**: `button[data-action='edit']`
- **Flow**: Opens edit modal with bookmark data
- **API**: `../api/edit.php`
- **Features**:
  - Favicon URL handling
  - Category change detection
  - Empty state updates
  - Search data reset

### Quick Add Form
- **Form**: `#quickAddForm`
- **API**: `../api/add.php`
- **Features**:
  - Comprehensive error handling
  - Popup window support
  - Search data reset
  - Success feedback

### Open All Bookmarks
- **Trigger**: `.open-all-category-btn`
- **Features**:
  - Opens all bookmarks in category in new tabs
  - Prevents event propagation
  - Console logging for feedback

### Form Validation
- **URL Validation**: Required field
- **Favicon Handling**: Default favicon fallback
- **Category Detection**: Automatic category assignment

### Error Handling
- **Network Errors**: Comprehensive error messages
- **API Errors**: Server response handling
- **Validation Errors**: Client-side validation

---

## 9. category-management.js (117 lines)

**Purpose**: Handles category CRUD operations and settings management.

### Edit Category
- **Trigger**: `[data-action='edit-category']`
- **Modal**: Category edit modal
- **API**: `../api/edit-category.php`
- **Features**:
  - Page assignment
  - Width settings
  - Description visibility
  - Favicon visibility

### Add Category
- **Form**: `#categoryAddForm`
- **API**: `../api/add-category.php`
- **Features**: Automatic page reload after creation

### Category Settings
- **Width**: Column width (1-12 grid system)
- **Description**: Show/hide descriptions
- **Favicon**: Show/hide favicons
- **Page Assignment**: Move categories between pages

### Page Movement Detection
- **Current Page**: Detected from DOM
- **Page Change**: Triggers page reload
- **Settings Change**: Triggers page reload for visual updates

### Delete Category
- **Trigger**: Category edit modal delete button
- **Flow**: Opens delete confirmation modal
- **API**: `../api/delete-category.php`

### Global Exports
- No direct exports (uses modal functions)

---

## 10. page-management.js (87 lines)

**Purpose**: Handles page CRUD operations and navigation.

### Edit Page
- **Trigger**: `#pageEditButton`
- **Modal**: Page edit modal
- **API**: `../api/edit-page.php`
- **Features**: DOM updates without reload

### Add Page
- **Form**: `#pageAddForm`
- **API**: `../api/add-page.php`
- **Features**: Automatic page reload after creation

### Delete Page
- **Trigger**: Page edit modal delete button
- **Flow**: Opens delete confirmation modal
- **API**: `../api/delete-page.php`
- **Features**: Page redirection handling

### Page Updates
- **DOM Updates**: Uses `updatePageDisplay()` utility
- **Search Reset**: Resets search data after changes
- **Success Feedback**: Flash messages for user feedback

---

## 11. context-menu.js (32 lines)

**Purpose**: Implements right-click context menu functionality.

### Context Menu Triggers
- **Target**: Main container or empty space
- **Exclusions**:
  - Categories (`section[data-category-id]`)
  - Bookmarks (`li[data-id]`)
  - Forms (`form`)
  - Buttons (`button`)

### Context Menu Actions
- **Add Link**: Opens quick add modal
- **Add Category**: Opens category add modal
- **Add Page**: Opens page add modal

### Event Handling
- **Right Click**: Shows context menu
- **Outside Click**: Hides context menu
- **Escape Key**: Hides context menu
- **Prevention**: Prevents default context menu

### Position Management
- **Coordinates**: Uses `clientX` and `clientY`
- **Dynamic Positioning**: Menu appears at click location

---

## 12. password-management.js (78 lines)

**Purpose**: Handles password change functionality with validation and security.

### Password Change Modal
- **Trigger**: `#changePasswordLink`
- **Form**: `#passwordChangeForm`
- **API**: `../api/change-password.php`

### Validation Rules
- **Password Match**: New password must match confirmation
- **Minimum Length**: 6 characters minimum
- **Current Password**: Required for verification

### Security Features
- **Server Validation**: All validation on server side
- **Logout Redirect**: Forces re-login after password change
- **Error Handling**: Comprehensive error messages

### Modal Management
- **Open**: `openPasswordChangeModal()`
- **Close**: `closePasswordChangeModal()`
- **Reset**: Form reset on close

### Global Exports
```javascript
window.openPasswordChangeModal = openPasswordChangeModal;
window.closePasswordChangeModal = closePasswordChangeModal;
```

---

## 13. favicon-management.js (68 lines)

**Purpose**: Handles favicon refresh functionality for bookmarks.

### Favicon Refresh
- **Trigger**: `#edit-refresh-favicon`
- **API**: `../api/refresh-favicon.php`
- **Features**:
  - URL validation
  - Loading state management
  - Error handling
  - Success feedback

### Loading State
- **Visual Feedback**: Spinning icon during refresh
- **Button State**: Disabled during operation
- **Text Change**: "Refreshing..." message

### URL Validation
- **Requirement**: URL must be entered before refresh
- **Error Message**: "Please enter a URL first"

### Favicon Display
- **Cached URLs**: Handles `cache/` prefix
- **Display URL**: Shows original URL for reference
- **Hover Tooltip**: Full URL on hover

### Error Handling
- **Network Errors**: Comprehensive error messages
- **API Errors**: Server response handling
- **State Restoration**: Button state reset on error

---

## Module Dependencies

### Critical Dependencies
1. **flash-messages.js** → Used by most modules for user feedback
2. **utils.js** → Used by bookmark and category management for DOM updates
3. **modal-management.js** → Used by bookmark and category management for modal operations

### Independent Modules
- `global-search.js`: Self-contained search functionality
- `page-navigation.js`: Page navigation system
- `drag-drop.js`: Drag and drop functionality
- `section-management.js`: Section expand/collapse
- `context-menu.js`: Context menu system
- `password-management.js`: Password change functionality
- `favicon-management.js`: Favicon refresh functionality

### API Dependencies
All modules depend on corresponding PHP API endpoints:
- `../api/add.php` - Bookmark creation
- `../api/edit.php` - Bookmark editing
- `../api/delete-bookmark.php` - Bookmark deletion
- `../api/add-category.php` - Category creation
- `../api/edit-category.php` - Category editing
- `../api/delete-category.php` - Category deletion
- `../api/add-page.php` - Page creation
- `../api/edit-page.php` - Page editing
- `../api/delete-page.php` - Page deletion
- `../api/get-all-bookmarks.php` - Search data
- `../api/refresh-favicon.php` - Favicon refresh
- `../api/change-password.php` - Password change
- `../api/reorder.php` - Bookmark reordering
- `../api/reorder-categories.php` - Category reordering

## Best Practices

### Error Handling
- All modules implement comprehensive error handling
- User-friendly error messages via flash messages
- Console logging for debugging

### Performance
- Lazy loading for search data
- Debounced search input
- Efficient DOM updates
- Minimal page reloads

### Security
- Server-side validation for all operations
- CSRF protection via PHP
- Input sanitization
- Secure password handling

### User Experience
- Loading states for async operations
- Keyboard navigation support
- Visual feedback for all actions
- Responsive design considerations

## Maintenance Notes

### Adding New Modules
1. Add module to `app.js` modules array
2. Consider dependency order
3. Export necessary functions to `window` object
4. Update this documentation

### Debugging
- All modules include console logging
- Error messages are user-friendly
- Network requests are logged
- State changes are tracked

### Testing
- Manual testing for all CRUD operations
- Keyboard navigation testing
- Cross-browser compatibility
- Mobile responsiveness testing
