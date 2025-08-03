-- Backup script - run this BEFORE migration
-- This creates backup tables with current data

-- Create backup tables
CREATE TABLE pages_backup AS SELECT * FROM pages;
CREATE TABLE categories_backup AS SELECT * FROM categories;
CREATE TABLE bookmarks_backup AS SELECT * FROM bookmarks;

-- Add backup timestamp
ALTER TABLE pages_backup ADD COLUMN backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE categories_backup ADD COLUMN backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE bookmarks_backup ADD COLUMN backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Verify backup data
SELECT 'pages_backup' as table_name, COUNT(*) as count FROM pages_backup;
SELECT 'categories_backup' as table_name, COUNT(*) as count FROM categories_backup;
SELECT 'bookmarks_backup' as table_name, COUNT(*) as count FROM bookmarks_backup; 