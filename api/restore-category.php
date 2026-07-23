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
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        exit;
    }

    $currentUserId = getCurrentUserId();
    $categoryId = (int)$input['id'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        SELECT id, name, page_id
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

    $destinationPageId = isset($input['page_id'])
        ? (int)$input['page_id']
        : (int)$category['page_id'];
    $stmt = $pdo->prepare('SELECT id FROM pages WHERE id = ? AND user_id = ?');
    $stmt->execute([$destinationPageId, $currentUserId]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Choose an existing page before restoring this category'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT id
        FROM categories
        WHERE name = ?
            AND page_id = ?
            AND user_id = ?
            AND deleted_at IS NULL
        LIMIT 1
    ');
    $stmt->execute([$category['name'], $destinationPageId, $currentUserId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'An active category with this name already exists on the original page'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('
        UPDATE categories
        SET page_id = ?, deleted_at = NULL
        WHERE id = ?
            AND user_id = ?
            AND deleted_at IS NOT NULL
    ');
    $stmt->execute([$destinationPageId, $categoryId, $currentUserId]);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Category restored',
        'id' => $categoryId
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to restore category']);
}
