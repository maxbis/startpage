<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Bookmark ID is required');
    }
    
    $bookmarkId = (int)$input['id'];
    
    // Delete the bookmark
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE id = ?");
    $stmt->execute([$bookmarkId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Bookmark not found');
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