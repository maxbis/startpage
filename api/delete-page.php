<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Page ID is required']);
        exit;
    }
    
    $pageId = (int)$input['id'];
    
    // Check if page exists
    $stmt = $pdo->prepare("SELECT id, name FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        echo json_encode(['success' => false, 'message' => 'Page not found']);
        exit;
    }
    
    // Check if this is the last page (prevent deleting all pages)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pages");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last remaining page']);
        exit;
    }
    
    // Delete the page (categories and bookmarks will be deleted via CASCADE)
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Page deleted successfully',
        'id' => $pageId,
        'name' => $page['name']
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting page: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 