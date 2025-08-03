-- Rollback script - use this if migration needs to be undone
-- WARNING: This will remove user_id columns and restore original structure

-- Step 1: Drop foreign key constraints
ALTER TABLE pages DROP FOREIGN KEY fk_pages_user;
ALTER TABLE categories DROP FOREIGN KEY fk_categories_user;
ALTER TABLE bookmarks DROP FOREIGN KEY fk_bookmarks_user;

-- Step 2: Drop indexes
DROP INDEX idx_pages_user ON pages;
DROP INDEX idx_categories_user ON categories;
DROP INDEX idx_bookmarks_user ON bookmarks;
DROP INDEX idx_categories_user_page ON categories;
DROP INDEX idx_bookmarks_user_category ON bookmarks;

-- Step 3: Remove user_id columns
ALTER TABLE pages DROP COLUMN user_id;
ALTER TABLE categories DROP COLUMN user_id;
ALTER TABLE bookmarks DROP COLUMN user_id;

-- Step 4: Verify rollback
SELECT 'pages' as table_name, COUNT(*) as count FROM pages;
SELECT 'categories' as table_name, COUNT(*) as count FROM categories;
SELECT 'bookmarks' as table_name, COUNT(*) as count FROM bookmarks;

-- Note: If you need to restore from backup:
-- DROP TABLE pages; CREATE TABLE pages AS SELECT * FROM pages_backup;
-- DROP TABLE categories; CREATE TABLE categories AS SELECT * FROM categories_backup;
-- DROP TABLE bookmarks; CREATE TABLE bookmarks AS SELECT * FROM bookmarks_backup; 