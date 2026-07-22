# StartPage

A customizable, multi-user startpage application with bookmark management, search functionality, favicon support, and comprehensive debugging tools.

## 🆕 Recent Updates

### Category Context Menu
- ✅ **Right-click on categories** for quick access to category-specific actions
- ✅ **Mobile long-press support** for touch devices
- ✅ **Category actions**: Add Bookmark, Edit Category, Open All Bookmarks, Delete Category
- ✅ **Smart context menu** that hides immediately when actions are selected

### CSS Organization
- ✅ **External CSS files** for better maintainability
- ✅ **Modular CSS structure** with separate files for different components
- ✅ **Clean separation** of styles from PHP templates

### Debug System
- ✅ **Global debugging system** with module-specific control
- ✅ **Console-based debugging** with easy toggle commands
- ✅ **Production-safe** logging with default disabled state
- ✅ **Comprehensive help system** (`DEBUG.help()`)

### JavaScript Architecture
- ✅ **Modular JavaScript** with specialized modules
- ✅ **Sequential loading** with dependency management
- ✅ **Global search** with lazy loading and keyboard navigation
- ✅ **Drag & drop** functionality for bookmarks and categories
- ✅ **Modal management** system for all UI interactions

## 📁 Project Structure

```
startpage/
├── 📁 app/                    # Main application files
│   ├── index.php             # Main startpage interface
│   ├── login.php             # User login
│   ├── logout.php            # User logout
│   ├── register.php          # User registration
│   ├── admin.php             # Admin panel (user management)
│   └── verify.php            # Email verification
├── 📁 api/                   # API endpoints
│   ├── add.php               # Add bookmarks
│   ├── edit.php              # Edit bookmarks
│   ├── delete-bookmark.php   # Delete bookmarks
│   ├── add-category.php      # Add categories
│   ├── add-page.php          # Add pages
│   └── ...                   # Other CRUD operations
├── 📁 includes/              # Shared libraries
│   ├── db.php                # Database connection
│   ├── auth_functions.php    # Authentication functions
│   ├── rate_limiter.php      # Rate limiting
│   ├── email_verification.php # Email verification
│   └── favicon/              # Favicon utilities
│       ├── favicon-cache.php
│       ├── favicon-discoverer.php
│       └── favicon-helper.php
├── 📁 assets/                # Static assets
│   ├── js/                   # JavaScript files
│   │   ├── app.js            # Main application loader
│   │   └── modules/          # Modular JavaScript components
│   │       ├── flash-messages.js    # User feedback system
│   │       ├── global-search.js     # Search functionality
│   │       ├── page-navigation.js   # Page navigation
│   │       ├── drag-drop.js         # Drag & drop operations
│   │       ├── section-management.js # Section expand/collapse
│   │       ├── utils.js             # DOM update utilities
│   │       ├── modal-management.js  # Modal system
│   │       ├── bookmark-management.js # Bookmark CRUD
│   │       ├── bookmark-actions.js  # Activity meter and bookmark actions
│   │       ├── category-management.js # Category CRUD
│   │       ├── page-management.js   # Page CRUD
│   │       ├── context-menu.js      # Context menu system
│   │       ├── password-management.js # Password operations
│   │       └── favicon-management.js # Favicon refresh
│   ├── css/                  # CSS files
│   │   ├── main.css          # Main application styles
│   │   ├── bookmark-colors.css # Bookmark color schemes
│   │   └── responsive.css    # Mobile/responsive styles
│   └── images/               # Images
├── 📁 database/              # Database files
│   ├── setup.sql             # Main database setup
│   ├── auth_setup.sql        # Authentication tables
│   └── migrations/           # Database migrations
├── 📁 tools/                 # Utility tools
│   ├── bookmarklet.php       # Bookmarklet generator
│   ├── cache-manager.php     # Cache management
│   └── get-favicon.php       # Favicon tool
├── 📁 public/                # Public assets
│   ├── favicon.ico
│   ├── favicon-16x16.png
│   └── favicon-32x32.png
├── 📁 cache/                 # Cache directory
└── 📁 tests/                 # Test files (future)
```

## 🚀 Quick Start

### 1. Database Setup
```bash
# Create database and run setup scripts
mysql -u username -p database_name < database/setup.sql
mysql -u username -p database_name < database/auth_setup.sql
```

### 2. Configuration
Update `includes/db.php` with your database credentials.

### 3. Start Development Server
```bash
php -S localhost:8000
```

### 4. Access Application
- **Main App**: http://localhost:8000/app/
- **Admin Panel**: http://localhost:8000/app/admin.php
- **Tools**: http://localhost:8000/tools/

## 🔧 Features

### Core Features
- ✅ **Multi-user support** with data isolation
- ✅ **Global search** across all bookmarks
- ✅ **Drag & drop** bookmark reordering
- ✅ **Favicon support** with automatic discovery and caching
- ✅ **Bookmarklet** for easy bookmark adding
- ✅ **Rate limiting** and anti-spam measures
- ✅ **Remember me** functionality
- ✅ **Admin panel** for user management

### Category Management
- ✅ **Right-click context menu** on categories for quick actions
- ✅ **Mobile long-press support** for touch devices
- ✅ **Category-specific actions**: Add Bookmark, Edit, Open All, Delete
- ✅ **Smart context menu** that auto-hides when actions are selected
- ✅ **Category editing** with width, description, and favicon preferences

### Search Features
- ✅ **Real-time search** with debouncing
- ✅ **Keyboard navigation** (Arrow keys, Enter, Escape)
- ✅ **Search across** name, description, URL, category, and page
- ✅ **Lazy loading** for performance
- ✅ **Result highlighting** with search term emphasis
- ✅ **Favicon display** in search results

### User Management
- ✅ **User registration** with validation
- ✅ **Password reset** via admin panel
- ✅ **User deletion** with data cleanup
- ✅ **Session management** with secure tokens
- ✅ **Device tracking** for security

### UI/UX Features
- ✅ **Flash messages** for user feedback
- ✅ **Modal dialogs** for editing
- ✅ **Responsive design** with Tailwind CSS
- ✅ **Favicon refresh** in edit dialog
- ✅ **"Open all"** category functionality
- ✅ **Test environment** indicator
- ✅ **Section expand/collapse** functionality
- ✅ **Context menu** for quick actions (empty space + categories)
- ✅ **Four-segment activity meter** with bookmark edit, open, copy, and delete actions
- ✅ **Drag & drop** for bookmarks and categories
- ✅ **External CSS organization** for better maintainability

## 🛠️ Development

### File Organization
- **`app/`**: Main application pages
- **`api/`**: REST API endpoints
- **`includes/`**: Shared PHP libraries
- **`database/`**: SQL scripts and migrations
- **`tools/`**: Utility scripts and tools
- **`public/`**: Public assets (favicons, etc.)
- **`assets/css/`**: Organized CSS files by component

### Adding New Features
1. **API Endpoints**: Add to `api/` directory
2. **Pages**: Add to `app/` directory
3. **Libraries**: Add to `includes/` directory
4. **Database Changes**: Add migration to `database/migrations/`
5. **JavaScript Modules**: Add to `assets/js/modules/` directory
6. **CSS Styles**: Add to appropriate file in `assets/css/` directory

### Context Menu System
The application features a sophisticated context menu system:
- **Empty space right-click**: Add Link, Category, or Page
- **Category right-click**: Category-specific actions
- **Mobile support**: Long-press for touch devices
- **Auto-hide**: Context menu disappears when actions are selected

### Debug System Usage
```javascript
// Enable global debugging
DEBUG.enabled = true;

// Enable for specific modules
DEBUG.enableFor('MODAL');
DEBUG.enableFor(['MODAL', 'BOOKMARK']);

// Toggle debugging
DEBUG.toggle();

// Show help
DEBUG.help();
```

### JavaScript Module Architecture
- **Sequential Loading**: Modules load in dependency order
- **Global Debug System**: Centralized debugging across all modules
- **Module-Specific Logging**: Each module has its own debug identifier
- **Production Safe**: Default disabled state prevents console spam

### Database Migrations
```bash
# Run migrations in order
mysql -u username -p database_name < database/migrations/migrate_to_multi_user.sql
mysql -u username -p database/migrations/migrate_add_user_agent.sql
```

## 🔒 Security Features

- ✅ **SQL injection** prevention with prepared statements
- ✅ **XSS protection** with proper escaping
- ✅ **CSRF protection** with session validation
- ✅ **Rate limiting** to prevent abuse
- ✅ **Password hashing** with bcrypt
- ✅ **Secure cookies** with HttpOnly and Secure flags
- ✅ **User agent tracking** for security monitoring
- ✅ **Input validation** and sanitization
- ✅ **Multi-user data isolation** with user-specific queries
- ✅ **Session management** with secure token validation

## 🐛 Debugging

### Quick Start
1. Open browser console
2. Type: `DEBUG.help()` to see all options
3. Type: `DEBUG.enabled = true` to enable debugging
4. Interact with the application
5. Watch console for module-specific logs (e.g., `[MODAL]`, `[SECTION]`)
6. Type: `DEBUG.enabled = false` when done

### Available Debug Modules
- **MODAL**: Modal management operations
- **SECTION**: Section expand/collapse operations
- **BOOKMARK**: Bookmark CRUD operations
- **CATEGORY**: Category management
- **PAGE**: Page management
- **SEARCH**: Global search operations
- **NAVIGATION**: Page navigation
- **DRAGDROP**: Drag and drop operations
- **CONTEXT**: Context menu operations

### Example Debug Output
```
[MODAL] Opening category edit modal for: My Category
[SECTION] Expanding section...
[BOOKMARK] Deleting bookmark with ID: 123
[MODAL] Bookmark removed from DOM
[CONTEXT] Showing category context menu for: Work
```

## 📱 Mobile Features

- ✅ **Touch-friendly interface** with long-press context menus
- ✅ **Responsive design** that adapts to mobile screens
- ✅ **Mobile search toggle** for better mobile UX
- ✅ **Touch-optimized** drag and drop (disabled on mobile)
- ✅ **Mobile-specific** category edit buttons

## 📝 License

This project is open source and available under the MIT License. 
