<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['name'])) {
        throw new Exception('ID and name are required');
    }
    
    $id = (int)$input['id'];
    $name = trim($input['name']);
    
    // Validate name
    if (empty($name)) {
        throw new Exception('Category name cannot be empty');
    }
    
    if (strlen($name) > 100) {
        throw new Exception('Category name cannot exceed 100 characters');
    }
    
    // Update the category
    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    
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