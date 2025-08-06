<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Logging function
function logError($message, $data = []) {
    $logFile = __DIR__ . '/edit-category-erro.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if (!empty($data)) {
        $logEntry .= " - Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

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
    
    if (!isset($input['id']) || !isset($input['name']) || !isset($input['page_id']) || !isset($input['width']) || !isset($input['no_description']) || !isset($input['show_favicon'])) {
        logError('Missing required fields', $input);
        throw new Exception('ID, name, page_id, width, no_description, and show_favicon are required');
    }
    
    $id = (int)$input['id'];
    $name = trim($input['name']);
    $pageId = (int)$input['page_id'];
    $width = (int)$input['width'];
    $noDescription = (int)$input['no_description'];
    $showFavicon = (int)$input['show_favicon'];
    
    // Validate width
    if ($width < 1 || $width > 4) {
        logError('Invalid width value', ['width' => $width, 'user_id' => $currentUserId]);
        throw new Exception('Width must be between 1 and 4');
    }
    
    // Validate no_description
    if ($noDescription < 0 || $noDescription > 1) {
        logError('Invalid no_description value', ['no_description' => $noDescription, 'user_id' => $currentUserId]);
        throw new Exception('No description must be 0 or 1');
    }
    
    // Validate show_favicon
    if ($showFavicon < 0 || $showFavicon > 1) {
        logError('Invalid show_favicon value', ['show_favicon' => $showFavicon, 'user_id' => $currentUserId]);
        throw new Exception('Show favicon must be 0 or 1');
    }
    
    // Validate name
    if (empty($name)) {
        logError('Empty category name', ['user_id' => $currentUserId]);
        throw new Exception('Category name cannot be empty');
    }
    
    if (strlen($name) > 100) {
        logError('Category name too long', ['name_length' => strlen($name), 'user_id' => $currentUserId]);
        throw new Exception('Category name cannot exceed 100 characters');
    }
    
    // Validate page exists and belongs to user
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$pageId, $currentUserId]);
    if (!$stmt->fetch()) {
        logError('Invalid page access attempt', ['page_id' => $pageId, 'user_id' => $currentUserId]);
        throw new Exception('Invalid page');
    }
    
    // Create preferences JSON
    $preferences = json_encode(['cat_width' => $width, 'no_descr' => $noDescription, 'show_fav' => $showFavicon]);
    
    // Update the category (only if it belongs to the current user)
    // $stmt = $pdo->prepare("UPDATE categories SET name = ?, page_id = ?, preferences = ? WHERE id = ? AND user_id = ?");
    // $stmt->execute([$name, $pageId, $preferences, $id, $currentUserId]);
    $sql = "UPDATE categories SET name = '$name', page_id = '$pageId', preferences = '$preferences' WHERE id = '$id' AND user_id = '$currentUserId'";
    $pdo->exec($sql);

    if ($stmt->rowCount() === 0) {
        logError('Category not found or not owned by user', ['category_id' => $id, 'user_id' => $currentUserId]);
        logError('Rendered SQL update', ['sql' => $sql]);
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