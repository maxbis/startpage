# 📌 My Startpage

A beautiful, customizable startpage with drag & drop bookmark management and easy bookmark adding.

## ✨ Features

- **Drag & Drop Reordering**: Move bookmarks between categories or reorder within categories
- **Quick Bookmark Adding**: Multiple ways to add bookmarks from any website
- **Category Management**: Organize bookmarks into categories
- **Category Add/Edit/Delete**: Right-click context menu to add categories, edit category names, and delete empty categories
- **Context Menu**: Right-click to quickly add links or categories from anywhere on the page
- **Edit & Delete**: Full CRUD operations for bookmarks
- **Automatic Favicons**: Bookmarks automatically get favicons from Google's service
- **Responsive Design**: Works on desktop and mobile devices


## 🚀 Quick Start

1. **Setup Database**: Import `setup.sql` into your MySQL database
2. **Configure Database**: Update `includes/db.php` with your database credentials
3. **Access Startpage**: Open `index.php` in your browser

## 📌 Adding Bookmarks

### Method 1: Bookmarklet (Recommended)

1. Go to `bookmarklet.php` on your startpage
2. Drag the "📌 Add to Startpage" button to your browser's bookmarks bar
3. When on any website, click the bookmarklet to quickly add it to your startpage

### Method 2: Manual Entry

1. Click "📌 Get Bookmarklet" in the header
2. Use the "Quick Add Form" to paste any URL
3. Fill in the details and choose a category

### Method 3: In-Page Form

1. Use the "Add" form at the bottom of each category
2. Just paste the URL and click "Add"

## 🎯 How to Use

### Drag & Drop
- **Reorder**: Drag bookmarks up/down within a category
- **Move Categories**: Drag bookmarks between different categories
- **Auto-Save**: All changes are automatically saved to the database

### Edit Bookmarks
- Click the ✏️ icon next to any bookmark
- Edit title, URL, and description
- Click "Save" to update

### Delete Bookmarks
- Click the 🗑 icon next to any bookmark
- Confirm deletion

### Category Management
- **Add Category**: Right-click on empty space and select "Add Category"
- **Edit Category**: Click the ✏️ icon next to any category name
- **Delete Category**: Use the delete button in the category edit modal (only works for empty categories)
- **Reorder Categories**: Drag and drop categories to reorder them

### Quick Actions
- **Add Link**: Right-click on empty space and select "Add Link" to quickly add a bookmark
- **Context Menu**: Right-click anywhere on the page (except on bookmarks/categories) to access quick actions

## 🛠 Technical Details

- **Backend**: PHP with MySQL
- **Frontend**: Vanilla JavaScript with SortableJS for drag & drop

- **Styling**: Tailwind CSS
- **Favicons**: Google's favicon service
- **API**: RESTful endpoints for all operations

## 📁 File Structure

```
startpage/
├── api/
│   ├── add.php              # Add new bookmarks
│   ├── add-category.php     # Add new categories
│   ├── delete.php           # Delete bookmarks
│   ├── delete-category.php  # Delete categories
│   ├── edit.php             # Edit bookmarks
│   ├── edit-category.php    # Edit categories
│   ├── reorder.php          # Handle bookmark drag & drop reordering
│   └── reorder-categories.php # Handle category drag & drop reordering
├── assets/
│   └── js/
│       └── app.js       # Frontend JavaScript
├── includes/
│   └── db.php          # Database connection
├── index.php           # Main startpage
├── bookmarklet.php     # Bookmarklet setup page
├── setup.sql          # Database schema
└── README.md          # This file
```

## 🔧 Database Schema

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

### Categories Table
- `id`: Primary key
- `name`: Category name

## 🎨 Customization

The startpage uses Tailwind CSS for styling. You can customize the appearance by:
- Modifying the CSS classes in `index.php`
- Adding custom CSS
- Changing the color scheme in the Tailwind classes

## 🔒 Security Notes

- All user inputs are validated and sanitized
- SQL injection protection via prepared statements
- XSS protection via `htmlspecialchars()`
- URL validation before processing

## 🚀 Deployment

1. Upload all files to your web server
2. Ensure PHP and MySQL are installed
3. Create the database and import `setup.sql`
4. Update database credentials in `includes/db.php`
5. Set appropriate file permissions

---

Made with ❤️ using PHP & Tailwind CSS 