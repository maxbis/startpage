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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Category ID is required');
    }
    
    $categoryId = (int)$input['id'];
    
    // Moving a category to Trash is reversible; its bookmarks stay attached.
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        SELECT id, name
        FROM categories
        WHERE id = ?
            AND user_id = ?
            AND deleted_at IS NULL
        FOR UPDATE
    ');
    $stmt->execute([$categoryId, $currentUserId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$category) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Active category not found']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookmarks WHERE category_id = ? AND user_id = ?');
    $stmt->execute([$categoryId, $currentUserId]);
    $bookmarkCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('
        UPDATE categories
        SET deleted_at = CURRENT_TIMESTAMP
        WHERE id = ?
            AND user_id = ?
            AND deleted_at IS NULL
    ');
    $stmt->execute([$categoryId, $currentUserId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Category moved to Trash',
        'id' => $categoryId,
        'bookmark_count' => $bookmarkCount
    ]);
    
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
