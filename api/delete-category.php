<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

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
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete category that contains bookmarks. Please move or delete all bookmarks first.');
    }
    
    // Delete the category
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    
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