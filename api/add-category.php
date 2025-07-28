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
    
    if (!$input || !isset($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }
    
    $name = trim($input['name']);
    
    // Validate category name
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name cannot be empty']);
        exit;
    }
    
    if (strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Category name is too long (max 100 characters)']);
        exit;
    }
    
    // Check if category name already exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
        exit;
    }
    
    // Get the highest sort_order to place new category at the end
    $stmt = $pdo->query("SELECT MAX(sort_order) as max_order FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newSortOrder = ($result['max_order'] ?? 0) + 1;
    
    // Insert the new category
    $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
    $stmt->execute([$name, $newSortOrder]);
    
    $categoryId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'id' => $categoryId,
        'name' => $name,
        'sort_order' => $newSortOrder
    ]);
    
} catch (Exception $e) {
    error_log("Error adding category: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 