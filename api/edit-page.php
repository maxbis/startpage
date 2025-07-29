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
    
    // Check if page exists
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Page not found']);
        exit;
    }
    
    // Check if page name already exists (excluding current page)
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE name = ? AND id != ?");
    $stmt->execute([$name, $pageId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A page with this name already exists']);
        exit;
    }
    
    // Update the page
    $stmt = $pdo->prepare("UPDATE pages SET name = ? WHERE id = ?");
    $stmt->execute([$name, $pageId]);
    
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