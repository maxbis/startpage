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
    // Get current page ID from cookie
    $currentPageId = 1; // Default page ID
    if (isset($_COOKIE['current_page_id'])) {
        $currentPageId = (int)$_COOKIE['current_page_id'];
    }
    
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
    
    // Check if category name already exists within the same page
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND page_id = ?");
    $stmt->execute([$name, $currentPageId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A category with this name already exists on this page']);
        exit;
    }
    
    // Get the highest sort_order for the current page to place new category at the end
    $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM categories WHERE page_id = ?");
    $stmt->execute([$currentPageId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newSortOrder = ($result['max_order'] ?? 0) + 1;
    
    // Insert the new category with page_id
    $stmt = $pdo->prepare("INSERT INTO categories (name, page_id, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$name, $currentPageId, $newSortOrder]);
    
    $categoryId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'id' => $categoryId,
        'name' => $name,
        'page_id' => $currentPageId,
        'sort_order' => $newSortOrder
    ]);
    
} catch (Exception $e) {
    error_log("Error adding category: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 