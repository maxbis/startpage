<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['category_id']) || !isset($input['order']) || !is_array($input['order'])) {
        throw new Exception('Invalid input data');
    }
    
    $categoryId = (int)$input['category_id'];
    $order = $input['order'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update each bookmark's category_id and sort_order
    foreach ($order as $index => $bookmarkId) {
        $stmt = $pdo->prepare("
            UPDATE bookmarks 
            SET category_id = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$categoryId, $index, $bookmarkId]);
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