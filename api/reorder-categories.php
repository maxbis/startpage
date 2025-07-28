<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['order']) || !is_array($input['order'])) {
        throw new Exception('Invalid input data');
    }
    
    $order = $input['order'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update each category's sort_order
    foreach ($order as $index => $categoryId) {
        $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index, $categoryId]);
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