<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || !isset($input['title']) || !isset($input['url'])) {
        throw new Exception('ID, title, and URL are required');
    }

    $id = (int) $input['id'];
    $title = trim($input['title']);
    $url = trim($input['url']);
    $description = trim($input['description'] ?? '');

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }

    // Validate title
    if (empty($title)) {
        throw new Exception('Title cannot be empty');
    }

    // Update the bookmark
    $stmt = $pdo->prepare('
        UPDATE bookmarks 
        SET title = ?, url = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ');

    $stmt->execute([$title, $url, $description, $id]);

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