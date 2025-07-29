<?php
require_once 'includes/favicon-cache.php';
require_once 'includes/db.php';

$faviconCache = new FaviconCache('cache/favicons/');

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'cleanup':
            $faviconCache->cleanupCache();
            $message = "Cache cleaned up successfully!";
            break;
        case 'clear':
            $files = glob('cache/favicons/*.ico');
            foreach ($files as $file) {
                unlink($file);
            }
            $message = "Cache cleared successfully!";
            break;
        case 'refresh':
            // Delete all cached favicons
            $files = glob('cache/favicons/*.ico');
            $deletedCount = 0;
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
            
            // Get all bookmarks from database and refresh their favicons
            $stmt = $pdo->query('SELECT id, url FROM bookmarks');
            $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $refreshedCount = 0;
            $failedCount = 0;
            $updatedCount = 0;
            $domains = [];
            
            foreach ($bookmarks as $bookmark) {
                $domain = parse_url($bookmark['url'], PHP_URL_HOST);
                if ($domain && !in_array($domain, $domains)) {
                    try {
                        $cachedFaviconUrl = $faviconCache->getFaviconUrl($domain);
                        $refreshedCount++;
                        $domains[] = $domain;
                        
                        // Update all bookmarks for this domain to use the cached favicon
                        $updateStmt = $pdo->prepare('UPDATE bookmarks SET favicon_url = ? WHERE url LIKE ?');
                        $updateStmt->execute([$cachedFaviconUrl, '%' . $domain . '%']);
                        $updatedCount += $updateStmt->rowCount();
                        
                    } catch (Exception $e) {
                        $failedCount++;
                    }
                }
            }
            
            $message = "Deleted {$deletedCount} old favicons. ";
            $message .= "Refreshed {$refreshedCount} favicons successfully! ";
            $message .= "Updated {$updatedCount} bookmarks in database to use cached favicons.";
            if ($failedCount > 0) {
                $message .= " Failed to refresh {$failedCount} favicons.";
            }
            break;
    }
}

$stats = $faviconCache->getCacheStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favicon Cache Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">📁 Favicon Cache Manager</h1>
        
        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Cache Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600"><?= $stats['count'] ?></div>
                    <div class="text-sm text-blue-500">Cached Favicons</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?= $stats['size_formatted'] ?></div>
                    <div class="text-sm text-green-500">Total Size</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">30 days</div>
                    <div class="text-sm text-purple-500">Cache Duration</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Cache Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="?action=cleanup" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition text-center">
                    🧹 Cleanup Expired
                </a>
                <a href="?action=clear" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition text-center" 
                   onclick="return confirm('Are you sure you want to clear all cached favicons?')">
                    🗑️ Clear All
                </a>
                <a href="?action=refresh" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-center"
                   onclick="return confirm('Are you sure you want to refresh all cached favicons from bookmarks? This will re-download all favicons from all bookmarks. This may take a few moments.')">
                    🔄 Refresh All Icons
                </a>
                <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-center">
                    ← Back to Startpage
                </a>
            </div>
        </div>
        
        <?php if ($stats['count'] > 0): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                <h2 class="text-xl font-semibold mb-4">Cached Favicons</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php
                    $files = glob('cache/favicons/*.ico');
                    foreach ($files as $file):
                        $filename = basename($file);
                        $domain = str_replace('.ico', '', $filename);
                        $domain = str_replace('_', '.', $domain);
                    ?>
                        <div class="text-center p-2 border rounded">
                            <img src="cache/favicons/<?= $filename ?>" alt="<?= $domain ?>" 
                                 class="w-8 h-8 mx-auto mb-2" onerror="this.style.display='none'">
                            <div class="text-xs text-gray-600 truncate" title="<?= $domain ?>"><?= $domain ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 