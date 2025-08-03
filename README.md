# StartPage

A customizable, multi-user startpage application with bookmark management, search functionality, and favicon support.

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
│   ├── delete.php            # Delete bookmarks
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
│   ├── css/                  # CSS files
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

### Search Features
- ✅ **Real-time search** with debouncing
- ✅ **Keyboard navigation** (Enter to open first result)
- ✅ **Search across** name, description, and URL
- ✅ **Lazy loading** for performance

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

## 🛠️ Development

### File Organization
- **`app/`**: Main application pages
- **`api/`**: REST API endpoints
- **`includes/`**: Shared PHP libraries
- **`database/`**: SQL scripts and migrations
- **`tools/`**: Utility scripts and tools
- **`public/`**: Public assets (favicons, etc.)

### Adding New Features
1. **API Endpoints**: Add to `api/` directory
2. **Pages**: Add to `app/` directory
3. **Libraries**: Add to `includes/` directory
4. **Database Changes**: Add migration to `database/migrations/`

### Database Migrations
```bash
# Run migrations in order
mysql -u username -p database_name < database/migrations/migrate_to_multi_user.sql
mysql -u username -p database_name < database/migrations/migrate_add_user_agent.sql
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

## 📝 License

This project is open source and available under the MIT License. 

