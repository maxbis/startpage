<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

requireAuth($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'], $input['confirmation_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category ID and confirmation name are required']);
        exit;
    }

    $currentUserId = getCurrentUserId();
    $categoryId = (int)$input['id'];
    $confirmationName = (string)$input['confirmation_name'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        SELECT id, name
        FROM categories
        WHERE id = ?
            AND user_id = ?
            AND deleted_at IS NOT NULL
        FOR UPDATE
    ');
    $stmt->execute([$categoryId, $currentUserId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trashed category not found']);
        exit;
    }

    if (!hash_equals((string)$category['name'], $confirmationName)) {
        $pdo->rollBack();
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The confirmation name does not match']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM bookmarks WHERE category_id = ? AND user_id = ?');
    $stmt->execute([$categoryId, $currentUserId]);
    $deletedBookmarkCount = $stmt->rowCount();

    $stmt = $pdo->prepare('
        DELETE FROM categories
        WHERE id = ?
            AND user_id = ?
            AND deleted_at IS NOT NULL
    ');
    $stmt->execute([$categoryId, $currentUserId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Category could not be deleted');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Category permanently deleted',
        'id' => $categoryId,
        'deleted_bookmark_count' => $deletedBookmarkCount
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to permanently delete category']);
}
