<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $currentUserId = getCurrentUserId();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Page ID is required']);
        exit;
    }
    
    $pageId = (int)$input['id'];
    
    // Check if page exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, name FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$pageId, $currentUserId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        echo json_encode(['success' => false, 'message' => 'Page not found or access denied']);
        exit;
    }
    
    // Check if this is the last page for this user (prevent deleting all pages)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pages WHERE user_id = ?");
    $stmt->execute([$currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last remaining page']);
        exit;
    }
    
    // Check if this was the current page (by checking the cookie)
    $isCurrentPage = false;
    if (isset($_COOKIE['startpage_current_page_id']) && (int)$_COOKIE['startpage_current_page_id'] === $pageId) {
        $isCurrentPage = true;
    }
    
    // Delete the page (categories and bookmarks will be deleted via CASCADE)
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$pageId, $currentUserId]);
    
    // If this was the current page, get the first available page for this user
    $redirectPageId = null;
    if ($isCurrentPage) {
        $stmt = $pdo->prepare("SELECT id FROM pages WHERE user_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
        $stmt->execute([$currentUserId]);
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