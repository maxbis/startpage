<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $currentUserId = getCurrentUserId();

    if (!isset($input['id']) || !isset($input['title']) || !isset($input['url']) || !isset($input['category_id'])) {
        throw new Exception('ID, title, URL, and category_id are required');
    }

    $id = (int) $input['id'];
    $title = trim($input['title']);
    $url = trim($input['url']);
    $description = trim($input['description'] ?? '');
    $categoryId = (int) $input['category_id'];
    $faviconUrl = trim($input['favicon_url'] ?? '');

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }

    // Validate title
    if (empty($title)) {
        throw new Exception('Title cannot be empty');
    }

    // Validate category exists and belongs to user
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND user_id = ?');
    $stmt->execute([$categoryId, $currentUserId]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid category');
    }

    // Update the bookmark (ensure it belongs to user)
    $stmt = $pdo->prepare('
        UPDATE bookmarks 
        SET title = ?, url = ?, description = ?, favicon_url = ?, category_id = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ');

    $stmt->execute([$title, $url, $description, $faviconUrl, $categoryId, $id, $currentUserId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Bookmark not found or access denied');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 