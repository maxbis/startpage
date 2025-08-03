-- Verification script - run this AFTER migration to confirm everything worked

-- Check that all tables have user_id column
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'startpage' 
AND COLUMN_NAME = 'user_id'
ORDER BY TABLE_NAME;

-- Check that all data belongs to user ID 1 (admin)
SELECT 
    'pages' as table_name, 
    COUNT(*) as total_records,
    COUNT(CASE WHEN user_id = 1 THEN 1 END) as admin_records,
    COUNT(CASE WHEN user_id != 1 THEN 1 END) as other_user_records
FROM pages
UNION ALL
SELECT 
    'categories' as table_name, 
    COUNT(*) as total_records,
    COUNT(CASE WHEN user_id = 1 THEN 1 END) as admin_records,
    COUNT(CASE WHEN user_id != 1 THEN 1 END) as other_user_records
FROM categories
UNION ALL
SELECT 
    'bookmarks' as table_name, 
    COUNT(*) as total_records,
    COUNT(CASE WHEN user_id = 1 THEN 1 END) as admin_records,
    COUNT(CASE WHEN user_id != 1 THEN 1 END) as other_user_records
FROM bookmarks;

-- Check foreign key constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'startpage' 
AND REFERENCED_TABLE_NAME = 'users'
ORDER BY TABLE_NAME;

-- Check indexes
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'startpage' 
AND INDEX_NAME LIKE '%user%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Test a sample query to ensure user filtering works
SELECT 
    p.name as page_name,
    c.name as category_name,
    b.title as bookmark_title
FROM pages p
JOIN categories c ON p.id = c.page_id AND p.user_id = c.user_id
JOIN bookmarks b ON c.id = b.category_id AND c.user_id = b.user_id
WHERE p.user_id = 1
LIMIT 5; 