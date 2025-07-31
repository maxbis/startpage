# 📌 My Startpage

A beautiful, customizable startpage with drag & drop bookmark management, global search, and easy bookmark adding.

## ✨ Features

- **🔍 Global Search**: Google-style search across all bookmarks with real-time results
- **📄 Multi-Page Support**: Organize bookmarks across multiple pages
- **🎯 Drag & Drop Reordering**: Move bookmarks between categories or reorder within categories
- **⚡ Quick Bookmark Adding**: Multiple ways to add bookmarks from any website
- **📁 Category Management**: Organize bookmarks into categories with full CRUD operations
- **🖱️ Context Menu**: Right-click to quickly add links, categories, or pages
- **✏️ Edit & Delete**: Full CRUD operations for bookmarks, categories, and pages
- **🎨 Automatic Favicons**: Bookmarks automatically get favicons from Google's service
- **📱 Responsive Design**: Works on desktop and mobile devices
- **🔓 User Authentication**: Secure login system with password protection
- **⚙️ Cache Management**: Built-in favicon cache management system

## 🚀 Quick Start

1. **Setup Database**: Import `setup.sql` into your MySQL database
2. **Configure Database**: Update `includes/db.php` with your database credentials
3. **Set Up Authentication**: Import `auth_setup.sql` for user authentication
4. **Access Startpage**: Open `index.php` in your browser and log in

## 🔍 Search Features

### Global Search
- **Real-time search** as you type (after 3 characters)
- **Multi-field search** across title, description, URL, category, and page
- **Keyboard navigation** with arrow keys and Enter
- **Search highlighting** shows matched terms
- **Lazy loading** for optimal performance

### Search Usage
- Type in the search box in the header
- Use arrow keys to navigate results
- Press Enter to open selected/first result
- Press Escape to close search results

## 📄 Page Management

### Multi-Page Organization
- **Create multiple pages** to organize bookmarks
- **Switch between pages** using the dropdown in the header
- **Page-specific categories** and bookmarks
- **Edit page names** and delete pages

### Page Operations
- **Add Page**: Right-click and select "Add Page"
- **Edit Page**: Click the page name in the header
- **Delete Page**: Use the delete button in page edit modal

## 📌 Adding Bookmarks

### Method 1: Bookmarklet (Recommended)
1. Go to `bookmarklet.php` on your startpage
2. Drag the "📌 Add to Startpage" button to your browser's bookmarks bar
3. When on any website, click the bookmarklet to quickly add it to your startpage

### Method 2: Quick Add Form
1. Right-click on empty space and select "Add Link"
2. Use the "Quick Add Form" to paste any URL
3. Fill in the details and choose a category

### Method 3: In-Page Form
1. Use the "Add" form at the bottom of each category
2. Just paste the URL and click "Add"

## 🎯 How to Use

### Search & Navigation
- **Global Search**: Type in the header search box to find any bookmark
- **Keyboard Navigation**: Use arrow keys, Enter, and Escape in search results
- **Quick Access**: Click any search result to open in new tab

### Drag & Drop
- **Reorder**: Drag bookmarks up/down within a category
- **Move Categories**: Drag bookmarks between different categories
- **Reorder Categories**: Drag categories to reorder them
- **Auto-Save**: All changes are automatically saved to the database

### Edit & Delete
- **Edit Bookmarks**: Click the ✏️ icon next to any bookmark
- **Edit Categories**: Click the category name to edit
- **Edit Pages**: Click the page name in the header
- **Delete**: Use delete buttons in edit modals

### Category Management
- **Add Category**: Right-click on empty space and select "Add Category"
- **Edit Category**: Click the category name to edit
- **Delete Category**: Use the delete button in category edit modal
- **Open All**: Click the ↗️ icon in category header to open all bookmarks

### Quick Actions
- **Add Link**: Right-click on empty space and select "Add Link"
- **Add Category**: Right-click and select "Add Category"
- **Add Page**: Right-click and select "Add Page"
- **Context Menu**: Right-click anywhere (except on bookmarks/categories) for quick actions

## 🛠 Technical Details

- **Backend**: PHP with MySQL
- **Frontend**: Vanilla JavaScript with SortableJS for drag & drop
- **Search**: Client-side search with lazy loading
- **Styling**: Tailwind CSS
- **Favicons**: Google's favicon service with caching
- **API**: RESTful endpoints for all operations
- **Authentication**: Session-based login system

## 📁 File Structure

```
startpage/
├── api/
│   ├── add.php                    # Add new bookmarks
│   ├── add-category.php           # Add new categories
│   ├── add-page.php               # Add new pages
│   ├── delete.php                 # Delete bookmarks
│   ├── delete-category.php        # Delete categories
│   ├── delete-page.php            # Delete pages
│   ├── edit.php                   # Edit bookmarks
│   ├── edit-category.php          # Edit categories
│   ├── edit-page.php              # Edit pages
│   ├── reorder.php                # Handle bookmark drag & drop
│   ├── reorder-categories.php     # Handle category drag & drop
│   ├── get-all-bookmarks.php      # Search API endpoint
│   ├── change-password.php        # Password change
│   └── auth_functions.php         # Authentication functions
├── assets/
│   └── js/
│       └── app.js                 # Frontend JavaScript
├── includes/
│   ├── db.php                     # Database connection
│   ├── auth_functions.php         # Authentication functions
│   └── favicon-cache.php          # Favicon caching
├── cache/                         # Favicon cache directory
├── index.php                      # Main startpage
├── login.php                      # Login page
├── logout.php                     # Logout handler
├── bookmarklet.php                # Bookmarklet setup page
├── cache-manager.php              # Cache management
├── setup.sql                      # Database schema
├── auth_setup.sql                 # Authentication schema
└── README.md                      # This file
```

## 🔧 Database Schema

### Pages Table
- `id`: Primary key
- `name`: Page name
- `sort_order`: Order of pages
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Categories Table
- `id`: Primary key
- `name`: Category name
- `page_id`: Foreign key to pages
- `sort_order`: Order within page
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Bookmarks Table
- `id`: Primary key
- `title`: Bookmark title
- `url`: Bookmark URL
- `description`: Optional description
- `favicon_url`: Favicon URL
- `category_id`: Foreign key to categories
- `sort_order`: Order within category
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Users Table
- `id`: Primary key
- `username`: Username
- `password_hash`: Hashed password
- `created_at`: Creation timestamp

## 🎨 Customization

The startpage uses Tailwind CSS for styling. You can customize the appearance by:
- Modifying the CSS classes in `index.php`
- Adding custom CSS
- Changing the color scheme in the Tailwind classes
- Customizing the favicon in the header

## 🔒 Security Features

- **User Authentication**: Secure login system with password protection
- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: `htmlspecialchars()` for all output
- **URL Validation**: Proper URL validation before processing
- **Session Management**: Secure session handling

## 🚀 Deployment

1. **Upload Files**: Upload all files to your web server
2. **Install Dependencies**: Ensure PHP and MySQL are installed
3. **Create Database**: Create the database and import `setup.sql`
4. **Set Up Authentication**: Import `auth_setup.sql` for user login
5. **Configure Database**: Update database credentials in `includes/db.php`
6. **Set Permissions**: Ensure proper file permissions for cache directory
7. **Access**: Open `index.php` and log in with default credentials

## 🔧 Cache Management

The system includes a favicon cache to improve performance:
- **Automatic Caching**: Favicons are cached locally
- **Cache Management**: Use `cache-manager.php` to manage cached favicons
- **Performance**: Reduces external requests for favicons

---

Made with ❤️ using PHP, Tailwind CSS, and modern web technologies 

