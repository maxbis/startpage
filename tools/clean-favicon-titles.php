<?php
/**
 * Clean up bookmark titles that have "favicon" at the beginning
 * This tool will remove "favicon" from the start of bookmark titles
 */

require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

$currentUserId = getCurrentUserId();

// Get all bookmarks for the current user
$stmt = $pdo->prepare('SELECT id, title FROM bookmarks WHERE user_id = ?');
$stmt->execute([$currentUserId]);
$bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cleanedCount = 0;
$updates = [];

foreach ($bookmarks as $bookmark) {
    $originalTitle = $bookmark['title'];
    $cleanedTitle = $originalTitle;
    
    // Remove "favicon" from the beginning of the title (case insensitive)
    $cleanedTitle = preg_replace('/^favicon\s*/i', '', $cleanedTitle);
    
    // If the title was changed, prepare for update
    if ($cleanedTitle !== $originalTitle) {
        $updates[] = [
            'id' => $bookmark['id'],
            'original' => $originalTitle,
            'cleaned' => $cleanedTitle
        ];
    }
}

// Display what will be changed
if (empty($updates)) {
    echo "<h2>No bookmarks with 'favicon' in title found.</h2>";
} else {
    echo "<h2>Bookmarks that will be cleaned:</h2>";
    echo "<ul>";
    foreach ($updates as $update) {
        echo "<li><strong>ID {$update['id']}:</strong> \"{$update['original']}\" → \"{$update['cleaned']}\"</li>";
    }
    echo "</ul>";
    
    // Ask for confirmation
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        // Perform the updates
        $stmt = $pdo->prepare('UPDATE bookmarks SET title = ? WHERE id = ? AND user_id = ?');
        
        foreach ($updates as $update) {
            $stmt->execute([$update['cleaned'], $update['id'], $currentUserId]);
            $cleanedCount++;
        }
        
        echo "<h3>✅ Successfully cleaned {$cleanedCount} bookmark titles!</h3>";
        echo "<p><a href='../app/index.php'>Return to startpage</a></p>";
    } else {
        echo "<form method='post'>";
        echo "<p><strong>This will update {$cleanedCount} bookmarks.</strong></p>";
        echo "<input type='hidden' name='confirm' value='yes'>";
        echo "<button type='submit' style='background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Confirm Cleanup</button>";
        echo "</form>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Favicon Titles</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h2 {
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        ul {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        li {
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🧹 Clean Favicon Titles Tool</h1>
    <p>This tool will remove "favicon" from the beginning of bookmark titles.</p>
    
    <?php if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes'): ?>
        <div class="success">
            <h3>✅ Successfully cleaned <?= $cleanedCount ?> bookmark titles!</h3>
            <p><a href="../app/index.php">Return to startpage</a></p>
        </div>
    <?php endif; ?>
</body>
</html>
