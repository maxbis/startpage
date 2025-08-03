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

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['name']) || !isset($input['page_id'])) {
        throw new Exception('ID, name, and page_id are required');
    }
    
    $id = (int)$input['id'];
    $name = trim($input['name']);
    $pageId = (int)$input['page_id'];
    
    // Validate name
    if (empty($name)) {
        throw new Exception('Category name cannot be empty');
    }
    
    if (strlen($name) > 100) {
        throw new Exception('Category name cannot exceed 100 characters');
    }
    
    // Validate page exists and belongs to user
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$pageId, $currentUserId]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid page');
    }
    
    // Update the category (only if it belongs to the current user)
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, page_id = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$name, $pageId, $id, $currentUserId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Category not found');
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