<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

try {
    $currentUserId = getCurrentUserId();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['category_id']) || !isset($input['order']) || !is_array($input['order'])) {
        throw new Exception('Invalid input data');
    }
    
    $categoryId = (int)$input['category_id'];
    $order = $input['order'];
    
    // Validate that the category belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$categoryId, $currentUserId]);
    if (!$stmt->fetch()) {
        throw new Exception('Category not found or access denied');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update each bookmark's category_id and sort_order (ensure bookmarks belong to user)
    foreach ($order as $index => $bookmarkId) {
        $stmt = $pdo->prepare("
            UPDATE bookmarks 
            SET category_id = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$categoryId, $index, $bookmarkId, $currentUserId]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 