-- Migration script to convert single-user system to multi-user
-- This script adds user_id to all tables and assigns existing data to admin user (ID=1)

-- Step 1: Add user_id columns to all tables
ALTER TABLE pages ADD COLUMN user_id INT NOT NULL DEFAULT 1;
ALTER TABLE categories ADD COLUMN user_id INT NOT NULL DEFAULT 1;
ALTER TABLE bookmarks ADD COLUMN user_id INT NOT NULL DEFAULT 1;

-- Step 2: Add foreign key constraints
ALTER TABLE pages ADD CONSTRAINT fk_pages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE categories ADD CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE bookmarks ADD CONSTRAINT fk_bookmarks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Step 3: Add indexes for performance
CREATE INDEX idx_pages_user ON pages(user_id);
CREATE INDEX idx_categories_user ON categories(user_id);
CREATE INDEX idx_bookmarks_user ON bookmarks(user_id);

-- Step 4: Add composite indexes for common queries
CREATE INDEX idx_categories_user_page ON categories(user_id, page_id);
CREATE INDEX idx_bookmarks_user_category ON bookmarks(user_id, category_id);

-- Step 5: Verify data assignment (all data should now belong to user ID 1)
-- This query should return the same count as before migration
SELECT 
    'pages' as table_name, COUNT(*) as count FROM pages WHERE user_id = 1
UNION ALL
SELECT 'categories' as table_name, COUNT(*) as count FROM categories WHERE user_id = 1
UNION ALL
SELECT 'bookmarks' as table_name, COUNT(*) as count FROM bookmarks WHERE user_id = 1;

-- Step 6: Remove default values (optional - for production)
-- ALTER TABLE pages ALTER COLUMN user_id DROP DEFAULT;
-- ALTER TABLE categories ALTER COLUMN user_id DROP DEFAULT;
-- ALTER TABLE bookmarks ALTER COLUMN user_id DROP DEFAULT;

-- Verification queries (run these to confirm migration worked):
-- SELECT 'Total pages' as info, COUNT(*) as count FROM pages;
-- SELECT 'Total categories' as info, COUNT(*) as count FROM categories;
-- SELECT 'Total bookmarks' as info, COUNT(*) as count FROM bookmarks;
-- SELECT 'Admin user pages' as info, COUNT(*) as count FROM pages WHERE user_id = 1;
-- SELECT 'Admin user categories' as info, COUNT(*) as count FROM categories WHERE user_id = 1;
-- SELECT 'Admin user bookmarks' as info, COUNT(*) as count FROM bookmarks WHERE user_id = 1; 