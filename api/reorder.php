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
    
    $targetCategoryId = (int)$input['category_id'];
    $order = $input['order'];
    
    // Validate that the target category belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetCategoryId, $currentUserId]);
    if (!$stmt->fetch()) {
        throw new Exception('Category not found or access denied');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // First, get the current category_id for each bookmark to detect cross-category moves
    $bookmarkCurrentCategories = [];
    foreach ($order as $bookmarkId) {
        $stmt = $pdo->prepare("SELECT category_id FROM bookmarks WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookmarkId, $currentUserId]);
        $result = $stmt->fetch();
        if ($result) {
            $bookmarkCurrentCategories[$bookmarkId] = $result['category_id'];
        }
    }
    
    // Update each bookmark's category_id and sort_order (ensure bookmarks belong to user)
    foreach ($order as $index => $bookmarkId) {
        $stmt = $pdo->prepare("
            UPDATE bookmarks 
            SET category_id = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$targetCategoryId, $index, $bookmarkId, $currentUserId]);
    }
    
    // If any bookmarks were moved from a different category, we need to reorder the source category
    $sourceCategories = array_unique(array_filter($bookmarkCurrentCategories, function($catId) use ($targetCategoryId) {
        return $catId !== null && $catId !== $targetCategoryId;
    }));
    
    foreach ($sourceCategories as $sourceCategoryId) {
        // Get remaining bookmarks in the source category and reorder them
        $stmt = $pdo->prepare("
            SELECT id FROM bookmarks 
            WHERE category_id = ? AND user_id = ? 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$sourceCategoryId, $currentUserId]);
        $remainingBookmarks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Update sort_order for remaining bookmarks
        foreach ($remainingBookmarks as $index => $bookmarkId) {
            $stmt = $pdo->prepare("
                UPDATE bookmarks 
                SET sort_order = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$index, $bookmarkId, $currentUserId]);
        }
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