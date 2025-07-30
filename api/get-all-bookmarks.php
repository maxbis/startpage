<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

header('Content-Type: application/json');

try {
    // Get all bookmarks from all pages with category and page information
    $stmt = $pdo->prepare('
        SELECT 
            b.id,
            b.title,
            b.url,
            b.description,
            b.favicon_url,
            b.category_id,
            c.name as category_name,
            p.id as page_id,
            p.name as page_name
        FROM bookmarks b
        JOIN categories c ON b.category_id = c.id
        JOIN pages p ON c.page_id = p.id
        ORDER BY p.sort_order ASC, c.sort_order ASC, b.sort_order ASC
    ');
    
    $stmt->execute();
    $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookmarks' => $bookmarks
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch bookmarks: ' . $e->getMessage()
    ]);
}
?> 