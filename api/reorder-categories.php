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
    
    if (!isset($input['order']) || !is_array($input['order'])) {
        throw new Exception('Invalid input data');
    }
    
    $order = $input['order'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update each category's sort_order (ensure categories belong to user)
    foreach ($order as $index => $categoryId) {
        $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$index, $categoryId, $currentUserId]);
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