<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

requireAuth($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $currentUserId = getCurrentUserId();
    $stmt = $pdo->prepare('
        SELECT
            c.id,
            c.name,
            c.page_id,
            c.deleted_at,
            p.name AS page_name,
            COUNT(b.id) AS bookmark_count
        FROM categories c
        LEFT JOIN pages p
            ON p.id = c.page_id
            AND p.user_id = c.user_id
        LEFT JOIN bookmarks b
            ON b.category_id = c.id
            AND b.user_id = c.user_id
        WHERE c.user_id = ?
            AND c.deleted_at IS NOT NULL
        GROUP BY c.id, c.name, c.page_id, c.deleted_at, p.name
        ORDER BY c.deleted_at DESC, c.id DESC
    ');
    $stmt->execute([$currentUserId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('
        SELECT id, name
        FROM pages
        WHERE user_id = ?
        ORDER BY sort_order ASC, id ASC
    ');
    $stmt->execute([$currentUserId]);

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'pages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load Trash'
    ]);
}
