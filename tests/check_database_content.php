<?php
/**
 * Check database content to understand existing data
 */

require_once '../includes/db.php';

echo "Database Content Check\n";
echo "=====================\n\n";

// Check users
echo "=== Users ===\n";
try {
    $stmt = $pdo->query("SELECT id, username, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Created: {$user['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check pages
echo "=== Pages ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, user_id, sort_order FROM pages ORDER BY user_id, sort_order");
    $pages = $stmt->fetchAll();
    foreach ($pages as $page) {
        echo "ID: {$page['id']}, Name: {$page['name']}, User ID: {$page['user_id']}, Sort: {$page['sort_order']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check categories
echo "=== Categories ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, page_id, user_id, preferences FROM categories ORDER BY user_id, page_id, sort_order LIMIT 10");
    $categories = $stmt->fetchAll();
    foreach ($categories as $category) {
        echo "ID: {$category['id']}, Name: {$category['name']}, Page ID: {$category['page_id']}, User ID: {$category['user_id']}\n";
        echo "  Preferences: {$category['preferences']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check specific category for testing
echo "=== Testing Category Details ===\n";
try {
    $stmt = $pdo->prepare("SELECT c.*, p.name as page_name FROM categories c JOIN pages p ON c.page_id = p.id WHERE c.id = ?");
    $stmt->execute([1]);
    $category = $stmt->fetch();
    if ($category) {
        echo "Category ID 1:\n";
        echo "  Name: {$category['name']}\n";
        echo "  Page: {$category['page_name']} (ID: {$category['page_id']})\n";
        echo "  User ID: {$category['user_id']}\n";
        echo "  Preferences: {$category['preferences']}\n";
    } else {
        echo "Category ID 1 not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check page ownership
echo "=== Page Ownership Check ===\n";
try {
    $stmt = $pdo->prepare("SELECT id, name, user_id FROM pages WHERE id = ? AND user_id = ?");
    $stmt->execute([1, 1]);
    $page = $stmt->fetch();
    if ($page) {
        echo "Page ID 1 belongs to user ID 1: ✓\n";
    } else {
        echo "Page ID 1 does not belong to user ID 1: ✗\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "Database check completed!\n";
?> 