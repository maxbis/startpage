<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
if (!isAuthenticated($pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$currentUserId = getCurrentUserId();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Category ID is required');
    }
    
    $categoryId = (int)$input['id'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if category has bookmarks
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE category_id = ? AND user_id = ?");
    $stmt->execute([$categoryId, $currentUserId]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete category that contains bookmarks. Please move or delete all bookmarks first.');
    }
    
    // Delete the category (only if it belongs to the current user)
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$categoryId, $currentUserId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Category not found');
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
        'message' => $e->getMessage()
    ]);
}
?> 