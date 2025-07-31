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
    
    // Check if this was the current page (by checking the cookie)
    $isCurrentPage = false;
    if (isset($_COOKIE['current_page_id']) && (int)$_COOKIE['current_page_id'] === $pageId) {
        $isCurrentPage = true;
    }
    
    // Delete the page (categories and bookmarks will be deleted via CASCADE)
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    
    // If this was the current page, get the first available page
    $redirectPageId = null;
    if ($isCurrentPage) {
        $stmt = $pdo->query("SELECT id FROM pages ORDER BY sort_order ASC, id ASC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $redirectPageId = $result['id'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Page deleted successfully',
        'id' => $pageId,
        'name' => $page['name'],
        'wasCurrentPage' => $isCurrentPage,
        'redirectPageId' => $redirectPageId
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting page: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 