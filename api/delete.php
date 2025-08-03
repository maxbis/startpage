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
    
    if (!isset($input['id'])) {
        throw new Exception('Bookmark ID is required');
    }
    
    $bookmarkId = (int)$input['id'];
    
    // Delete the bookmark (ensure it belongs to user)
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookmarkId, $currentUserId]);
    
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