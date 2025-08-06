# Technical Documentation: app.js

## Overview
This document provides a comprehensive description of all functions and event handlers in `assets/js/app.js`, which manages the frontend interactivity for the bookmark management system.

## Table of Contents
1. [Flash Message Functions](#flash-message-functions)
2. [Search Functions](#search-functions)
3. [DOM Update Functions](#dom-update-functions)
4. [Modal Functions](#modal-functions)
5. [Utility Functions](#utility-functions)
6. [Event Handlers](#event-handlers)

---

## Flash Message Functions

### `showFlashMessage(message, type)`
**Purpose**: Displays a flash message to the user with different styling based on type.

**Inputs**:
- `message` (string): The text to display
- `type` (string, optional): Message type - 'success', 'error', 'warning', 'info' (default: 'info')

**Output**: None (side effect: updates DOM)

**Description**: Sets appropriate icon and color styling, shows the message, and auto-hides after 5 seconds.

### `hideFlashMessage()`
**Purpose**: Hides the currently displayed flash message.

**Inputs**: None

**Output**: None (side effect: updates DOM)

---

## Search Functions

### `initializeSearch()`
**Purpose**: Eager loading approach - fetches all bookmarks on page load for search functionality.

**Inputs**: None

**Output**: Promise (async function)

**Description**: Makes API call to `../api/get-all-bookmarks.php`, stores results in `allBookmarks` array, and sets `isDataLoaded` flag.

### `loadSearchDataIfNeeded()`
**Purpose**: Lazy loading approach - fetches bookmarks only when user starts typing.

**Inputs**: None

**Output**: Promise (async function)

**Description**: Checks if data is already loaded, if not, fetches bookmarks from API and stores them.

### `performSearch(query)`
**Purpose**: Filters bookmarks based on search query across multiple fields.

**Inputs**:
- `query` (string): Search term

**Output**: None (side effect: updates search results display)

**Description**: Filters bookmarks by title, description, URL, category, and page name. Requires minimum 3 characters.

### `formatFaviconUrl(faviconUrl)`
**Purpose**: Formats favicon URLs for proper display, handling cached favicons and fallbacks.

**Inputs**:
- `faviconUrl` (string): Raw favicon URL from database

**Output**: string - Formatted favicon URL for display

**Description**: Adds relative path prefix for cached favicons, returns default SVG for empty URLs.

### `displaySearchResults(results, query)`
**Purpose**: Renders search results in the UI with proper formatting and click handlers.

**Inputs**:
- `results` (array): Array of bookmark objects
- `query` (string): Original search query for highlighting

**Output**: None (side effect: updates DOM)

**Description**: Creates HTML for search results, highlights matching terms, adds click handlers to open bookmarks.

### `highlightSearchTerm(text, query)`
**Purpose**: Highlights search terms in text using HTML mark tags.

**Inputs**:
- `text` (string): Text to highlight
- `query` (string): Search term to highlight

**Output**: string - HTML with highlighted terms

### `hideSearchResults()`
**Purpose**: Hides the search results overlay and resets search state.

**Inputs**: None

**Output**: None (side effect: updates DOM)

### `handleSearchKeyboard(e)`
**Purpose**: Handles keyboard navigation in search results (arrow keys, enter, escape).

**Inputs**:
- `e` (Event): Keyboard event

**Output**: None (side effect: updates selection or opens bookmarks)

### `updateSelectedResult()`
**Purpose**: Updates visual styling of selected search result.

**Inputs**: None

**Output**: None (side effect: updates DOM styling)

---

## DOM Update Functions

### `updateCategoryDisplay(categoryId, data)`
**Purpose**: Updates category display including title and settings.

**Inputs**:
- `categoryId` (string): ID of category to update
- `data` (object): New category data

**Output**: None (side effect: updates DOM)

### `updateCategoryTitle(category, newName)`
**Purpose**: Updates the title of a category element.

**Inputs**:
- `category` (Element): Category DOM element
- `newName` (string): New category name

**Output**: None (side effect: updates DOM)

### `updateCategorySettings(category, data)`
**Purpose**: Updates category settings (width, description, favicon preferences).

**Inputs**:
- `category` (Element): Category DOM element
- `data` (object): Settings data

**Output**: None (side effect: updates DOM data attributes)

### `updateBookmarkDisplay(bookmarkId, data)`
**Purpose**: Updates all aspects of a bookmark display.

**Inputs**:
- `bookmarkId` (string): ID of bookmark to update
- `data` (object): New bookmark data

**Output**: None (side effect: updates DOM)

### `updateBookmarkTitle(bookmark, newTitle)`
**Purpose**: Updates bookmark title and link text.

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `newTitle` (string): New bookmark title

**Output**: None (side effect: updates DOM)

### `updateBookmarkUrl(bookmark, newUrl)`
**Purpose**: Updates bookmark URL link.

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `newUrl` (string): New bookmark URL

**Output**: None (side effect: updates DOM)

### `updateBookmarkDescription(bookmark, newDescription)`
**Purpose**: Updates bookmark description text.

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `newDescription` (string): New bookmark description

**Output**: None (side effect: updates DOM)

### `updateBookmarkFavicon(bookmark, newFaviconUrl)`
**Purpose**: Updates bookmark favicon image.

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `newFaviconUrl` (string): New favicon URL

**Output**: None (side effect: updates DOM)

### `updateBookmarkDisplayForCategory(bookmark, categoryId)`
**Purpose**: Updates bookmark display to match category settings (favicon/description visibility).

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `categoryId` (string): Category ID to match settings

**Output**: None (side effect: updates DOM visibility)

### `updateBookmarkCategory(bookmark, newCategoryId, originalCategoryId)`
**Purpose**: Moves bookmark to new category and updates display settings.

**Inputs**:
- `bookmark` (Element): Bookmark DOM element
- `newCategoryId` (string): Target category ID
- `originalCategoryId` (string, optional): Original category ID before move

**Output**: None (side effect: moves DOM element and updates settings)

### `updatePageDisplay(pageId, data)`
**Purpose**: Updates page name display in the UI.

**Inputs**:
- `pageId` (string): Page ID to update
- `data` (object): New page data

**Output**: None (side effect: updates DOM)

### `updateEmptyStates(categoryId)`
**Purpose**: Shows/hides empty state message for categories based on bookmark count.

**Inputs**:
- `categoryId` (string): Category ID to check

**Output**: None (side effect: updates DOM)

---

## Modal Functions

### `openEditModal(data)`
**Purpose**: Opens the bookmark edit modal with pre-filled data.

**Inputs**:
- `data` (object): Bookmark data to populate form

**Output**: None (side effect: shows modal)

### `closeEditModal()`
**Purpose**: Closes the bookmark edit modal.

**Inputs**: None

**Output**: None (side effect: hides modal)

### `openQuickAddModal()`
**Purpose**: Opens the quick add bookmark modal.

**Inputs**: None

**Output**: None (side effect: shows modal)

### `closeQuickAddModal()`
**Purpose**: Closes the quick add modal and handles popup window closure.

**Inputs**: None

**Output**: None (side effect: hides modal, closes popup)

### `openDeleteModal(itemId, itemTitle, itemType)`
**Purpose**: Opens the delete confirmation modal.

**Inputs**:
- `itemId` (string): ID of item to delete
- `itemTitle` (string): Title to display
- `itemType` (string, optional): Type of item ('bookmark', 'category', 'page')

**Output**: None (side effect: shows modal)

### `closeDeleteModal()`
**Purpose**: Closes the delete confirmation modal.

**Inputs**: None

**Output**: None (side effect: hides modal)

### `openCategoryEditModal(categoryId, categoryName, pageId, width, noDescription, showFavicon)`
**Purpose**: Opens the category edit modal with pre-filled data.

**Inputs**:
- `categoryId` (string): Category ID
- `categoryName` (string): Category name
- `pageId` (string): Page ID
- `width` (string): Category width setting
- `noDescription` (string): Description visibility setting
- `showFavicon` (string): Favicon visibility setting

**Output**: None (side effect: shows modal)

### `closeCategoryEditModal()`
**Purpose**: Closes the category edit modal and resets form.

**Inputs**: None

**Output**: None (side effect: hides modal, resets form)

### `openCategoryAddModal()`
**Purpose**: Opens the category add modal.

**Inputs**: None

**Output**: None (side effect: shows modal)

### `closeCategoryAddModal()`
**Purpose**: Closes the category add modal and resets form.

**Inputs**: None

**Output**: None (side effect: hides modal, resets form)

### `openPageAddModal()`
**Purpose**: Opens the page add modal.

**Inputs**: None

**Output**: None (side effect: shows modal)

### `closePageAddModal()`
**Purpose**: Closes the page add modal and resets form.

**Inputs**: None

**Output**: None (side effect: hides modal, resets form)

### `openPageEditModal(pageId, pageName)`
**Purpose**: Opens the page edit modal with pre-filled data.

**Inputs**:
- `pageId` (string): Page ID
- `pageName` (string): Page name

**Output**: None (side effect: shows modal)

### `closePageEditModal()`
**Purpose**: Closes the page edit modal and resets form.

**Inputs**: None

**Output**: None (side effect: hides modal, resets form)

### `openPasswordChangeModal()`
**Purpose**: Opens the password change modal.

**Inputs**: None

**Output**: None (side effect: shows modal)

### `closePasswordChangeModal()`
**Purpose**: Closes the password change modal and resets form.

**Inputs**: None

**Output**: None (side effect: hides modal, resets form)

---

## Utility Functions

### `showContextMenu(x, y)`
**Purpose**: Shows the context menu at specified coordinates.

**Inputs**:
- `x` (number): X coordinate
- `y` (number): Y coordinate

**Output**: None (side effect: shows context menu)

### `hideContextMenu()`
**Purpose**: Hides the context menu.

**Inputs**: None

**Output**: None (side effect: hides context menu)

---

## Event Handlers

### Search Event Handlers

#### Search Input Handler
**Element**: `#globalSearch`
**Event**: `input`
**Purpose**: Debounced search functionality with lazy loading
**Actions**:
- Clears previous timeout
- Sets new timeout for debounced search
- Loads data if needed
- Performs search

#### Search Keyboard Handler
**Element**: `#globalSearch`
**Event**: `keydown`
**Purpose**: Keyboard navigation in search results
**Actions**:
- Arrow keys: Navigate results
- Enter: Open selected result
- Escape: Close search

#### Search Close Handler
**Element**: `#closeSearch`
**Event**: `click`
**Purpose**: Closes search results overlay

### Page Dropdown Handlers

#### Page Dropdown Toggle
**Element**: `#pageDropdown`
**Event**: `click`
**Purpose**: Toggles page dropdown menu
**Actions**:
- Shows/hides dropdown menu
- Rotates dropdown icon

#### Page Selection
**Element**: `.page-option`
**Event**: `click`
**Purpose**: Handles page selection
**Actions**:
- Sets cookie for selected page
- Reloads page to show new content

### Section Expand/Collapse Handlers

#### Expand Indicator
**Element**: `.expand-indicator`
**Event**: `click`
**Purpose**: Toggles section expansion
**Actions**:
- Expands/collapses section content
- Updates indicator styling

### Drag & Drop Handlers

#### Category Drag & Drop
**Element**: `#categories-container`
**Library**: Sortable.js
**Purpose**: Reorders categories
**Actions**:
- Updates category order in database
- Handles drag animation

#### Bookmark Drag & Drop
**Element**: `ul[data-category-id]`
**Library**: Sortable.js
**Purpose**: Moves bookmarks between categories
**Actions**:
- Updates bookmark order in database
- Updates empty states
- Updates bookmark display for new category settings

### Form Submission Handlers

#### Add Bookmark Form
**Element**: `.add-bookmark-form`
**Event**: `submit`
**Purpose**: Adds new bookmarks
**Actions**:
- Submits form data to API
- Reloads page on success
- Shows error message on failure

#### Edit Bookmark Form
**Element**: `#editForm`
**Event**: `submit`
**Purpose**: Updates existing bookmarks
**Actions**:
- Submits form data to API
- Updates DOM with new data
- Updates empty states if category changed
- Resets search data

#### Category Add Form
**Element**: `#categoryAddForm`
**Event**: `submit`
**Purpose**: Adds new categories
**Actions**:
- Submits form data to API
- Shows success message
- Reloads page after delay

#### Category Edit Form
**Element**: `#categoryEditForm`
**Event**: `submit`
**Purpose**: Updates existing categories
**Actions**:
- Submits form data to API
- Handles page transitions
- Updates DOM or reloads page based on changes

#### Page Add Form
**Element**: `#pageAddForm`
**Event**: `submit`
**Purpose**: Adds new pages
**Actions**:
- Submits form data to API
- Shows success message
- Reloads page after delay

#### Page Edit Form
**Element**: `#pageEditForm`
**Event**: `submit`
**Purpose**: Updates existing pages
**Actions**:
- Submits form data to API
- Updates DOM with new data
- Resets search data

#### Quick Add Form
**Element**: `#quickAddForm`
**Event**: `submit`
**Purpose**: Quick bookmark addition
**Actions**:
- Submits form data to API
- Shows success message
- Closes modal and popup

#### Password Change Form
**Element**: `#passwordChangeForm`
**Event**: `submit`
**Purpose**: Changes user password
**Actions**:
- Validates password requirements
- Submits form data to API
- Redirects to logout on success

### Button Click Handlers

#### Delete Bookmark Buttons
**Element**: `button[data-action='delete']`
**Event**: `click`
**Purpose**: Opens delete confirmation for bookmarks
**Actions**:
- Gets bookmark data
- Opens delete modal

#### Edit Bookmark Buttons
**Element**: `button[data-action='edit']`
**Event**: `click`
**Purpose**: Opens edit modal for bookmarks
**Actions**:
- Gets bookmark data
- Opens edit modal with pre-filled data

#### Category Edit Buttons
**Element**: `[data-action='edit-category']`
**Event**: `click`
**Purpose**: Opens edit modal for categories
**Actions**:
- Gets category data
- Opens category edit modal

#### Page Edit Buttons
**Element**: `#pageEditButton`
**Event**: `click`
**Purpose**: Opens edit modal for pages
**Actions**:
- Gets page data
- Opens page edit modal

#### Open All Category Buttons
**Element**: `.open-all-category-btn`
**Event**: `click`
**Purpose**: Opens all bookmarks in a category
**Actions**:
- Finds all bookmark links in category
- Opens each link in new tab

#### Favicon Refresh Button
**Element**: `#edit-refresh-favicon`
**Event**: `click`
**Purpose**: Refreshes favicon for current URL
**Actions**:
- Shows loading state
- Calls favicon refresh API
- Updates favicon display
- Restores button state

### Modal Close Handlers

#### Edit Modal Close
**Element**: `#editCancel`
**Event**: `click`
**Purpose**: Closes edit modal

#### Edit Modal Delete
**Element**: `#editDelete`
**Event**: `click`
**Purpose**: Opens delete modal from edit modal

#### Quick Add Modal Close
**Element**: `#quickAddCancel`
**Event**: `click`
**Purpose**: Closes quick add modal

#### Delete Modal Close
**Element**: `#deleteCancel`
**Event**: `click`
**Purpose**: Closes delete modal

#### Delete Modal Confirm
**Element**: `#deleteConfirm`
**Event**: `click`
**Purpose**: Confirms deletion
**Actions**:
- Calls appropriate delete API
- Updates DOM or reloads page
- Shows success/error message

#### Category Add Modal Close
**Element**: `#categoryAddCancel`
**Event**: `click`
**Purpose**: Closes category add modal

#### Category Edit Modal Close
**Element**: `#categoryEditCancel`
**Event**: `click`
**Purpose**: Closes category edit modal

#### Category Edit Modal Delete
**Element**: `#categoryEditDelete`
**Event**: `click`
**Purpose**: Opens delete modal from category edit modal

#### Page Add Modal Close
**Element**: `#pageAddCancel`
**Event**: `click`
**Purpose**: Closes page add modal

#### Page Edit Modal Close
**Element**: `#pageEditCancel`
**Event**: `click`
**Purpose**: Closes page edit modal

#### Page Edit Modal Delete
**Element**: `#pageEditDelete`
**Event**: `click`
**Purpose**: Opens delete modal from page edit modal

#### Password Change Modal Close
**Element**: `#passwordChangeCancel`
**Event**: `click`
**Purpose**: Closes password change modal

### Context Menu Handlers

#### Context Menu Add Link
**Element**: `#contextAddLink`
**Event**: `click`
**Purpose**: Opens quick add modal from context menu

#### Context Menu Add Category
**Element**: `#contextAddCategory`
**Event**: `click`
**Purpose**: Opens category add modal from context menu

#### Context Menu Add Page
**Element**: `#contextAddPage`
**Event**: `click`
**Purpose**: Opens page add modal from context menu

#### Context Menu Show
**Element**: `document`
**Event**: `contextmenu`
**Purpose**: Shows context menu on right-click
**Actions**:
- Checks if right-click is on valid area
- Shows context menu at cursor position

#### Context Menu Hide
**Element**: `document`
**Event**: `click`
**Purpose**: Hides context menu when clicking elsewhere

#### Context Menu Hide on Escape
**Element**: `document`
**Event**: `keydown`
**Purpose**: Hides context menu when pressing Escape

### Flash Message Handlers

#### Flash Message Close
**Element**: `#flashClose`
**Event**: `click`
**Purpose**: Closes flash message

### Password Change Handlers

#### Password Change Link
**Element**: `#changePasswordLink`
**Event**: `click`
**Purpose**: Opens password change modal

---

## Global Variables

- `allBookmarks` (array): Stores all bookmarks for search functionality
- `searchTimeout` (number): Timeout ID for debounced search
- `currentSearchResults` (array): Currently displayed search results
- `selectedResultIndex` (number): Index of selected search result
- `isDataLoaded` (boolean): Flag indicating if search data has been loaded

---

## Dependencies

- **Sortable.js**: For drag-and-drop functionality
- **Tailwind CSS**: For styling classes
- **Fetch API**: For HTTP requests
- **DOM APIs**: For element manipulation

---

## Notes

- All functions are wrapped in a `DOMContentLoaded` event listener
- Error handling includes flash messages for user feedback
- Search functionality supports both eager and lazy loading approaches
- Drag-and-drop operations update both UI and database
- Modal management includes proper cleanup and state reset
- All API calls include proper error handling and user feedback 