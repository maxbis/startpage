<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $currentUserId = getCurrentUserId();

    if (!isset($input['id'])) {
        throw new Exception('Bookmark ID is required');
    }

    $bookmarkId = (int) $input['id'];

    // Increment usage and record the exact time (ensure it belongs to user).
    $stmt = $pdo->prepare("
        UPDATE bookmarks
        SET click_count = COALESCE(click_count, 0) + 1,
            last_clicked_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$bookmarkId, $currentUserId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bookmark not found or access denied']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT click_count, last_clicked_at FROM bookmarks WHERE id = ? AND user_id = ?');
    $stmt->execute([$bookmarkId, $currentUserId]);
    $usage = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'click_count' => (int)$usage['click_count'],
        'last_clicked_at' => $usage['last_clicked_at']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
