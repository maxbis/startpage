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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Page ID and name are required']);
        exit;
    }
    
    $pageId = (int)$input['id'];
    $name = trim($input['name']);
    
    // Validate page name
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Page name cannot be empty']);
        exit;
    }
    
    if (strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Page name is too long (max 100 characters)']);
        exit;
    }
    
    // Check if page exists and belongs to user
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$pageId, $currentUserId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Page not found']);
        exit;
    }
    
    // Check if page name already exists for this user (excluding current page)
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE name = ? AND id != ? AND user_id = ?");
    $stmt->execute([$name, $pageId, $currentUserId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A page with this name already exists']);
        exit;
    }
    
    // Update the page (only if it belongs to the current user)
    $stmt = $pdo->prepare("UPDATE pages SET name = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$name, $pageId, $currentUserId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Page updated successfully',
        'id' => $pageId,
        'name' => $name
    ]);
    
} catch (Exception $e) {
    error_log("Error updating page: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 