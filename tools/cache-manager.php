<?php
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-cache.php';

$faviconCache = new FaviconCache('../cache/favicons/');

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'cleanup':
            // Check if cache directory exists
            $cacheDir = '../cache/favicons/';
            if (!is_dir($cacheDir)) {
                $message = "Cache directory does not exist. No cleanup needed.";
            } else {
                $faviconCache->cleanupCache();
                $message = "Cache cleaned up successfully!";
            }
            break;
        case 'clear':
            $cacheDir = '../cache/favicons/';
            if (!is_dir($cacheDir)) {
                $message = "Cache directory does not exist. No files to clear.";
            } else {
                $deletedCount = $faviconCache->clearCache();
                $message = "Cache cleared successfully! Deleted {$deletedCount} files.";
            }
            break;
        case 'refresh':
            // Check if cache directory exists and create it if not
            $cacheDir = '../cache/favicons/';
            if (!is_dir($cacheDir)) {
                if (!mkdir($cacheDir, 0755, true)) {
                    $message = "Error: Could not create cache directory!";
                    break;
                }
                $message = "Created cache directory. ";
            }
            
            $deletedCount = $faviconCache->clearCache();
            
            // Get all bookmarks from database and refresh their favicons
            $stmt = $pdo->query('SELECT id, url FROM bookmarks');
            $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $refreshedCount = 0;
            $failedCount = 0;
            $updatedCount = 0;
            foreach ($bookmarks as $bookmark) {
                try {
                    $resolved = $faviconCache->resolveForUrl($bookmark['url'], true);
                    $refreshedCount++;

                    $updateStmt = $pdo->prepare('UPDATE bookmarks SET favicon_url = ? WHERE id = ?');
                    $updateStmt->execute([$resolved['favicon_url'], $bookmark['id']]);
                    $updatedCount += $updateStmt->rowCount();
                } catch (Exception $e) {
                    $failedCount++;
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
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    <main class="wp-page-shell">
        <div class="wp-page-stack">
        <h1 class="wp-page-title">📁 Favicon Cache Manager</h1>
        
        <?php if (isset($message)): ?>
            <div class="wp-alert wp-alert--success" role="status">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <section class="wp-panel wp-panel--section">
            <h2 class="wp-section-title">Cache Statistics</h2>
            <div class="wp-metric-grid">
                <div class="wp-metric">
                    <div class="wp-metric__value"><?= $stats['count'] ?></div>
                    <div class="wp-metric__label">Cached Favicons</div>
                </div>
                <div class="wp-metric">
                    <div class="wp-metric__value"><?= $stats['size_formatted'] ?></div>
                    <div class="wp-metric__label">Total Size</div>
                </div>
                <div class="wp-metric">
                    <div class="wp-metric__value">30 days</div>
                    <div class="wp-metric__label">Cache Duration</div>
                </div>
            </div>
        </section>

        <section class="wp-panel wp-panel--section">
            <h2 class="wp-section-title">Cache Actions</h2>
            <div class="wp-action-grid">
                <a href="?action=cleanup" class="wp-button wp-button--warning">
                    🧹 Cleanup Expired
                </a>
                <a href="?action=clear" class="wp-button wp-button--danger"
                   onclick="return confirm('Are you sure you want to clear all cached favicons?')">
                    🗑️ Clear All
                </a>
                <a href="?action=refresh" class="wp-button wp-button--primary"
                   onclick="return confirm('Are you sure you want to refresh all cached favicons from bookmarks? This will re-download all favicons from all bookmarks. This may take a few moments.')">
                    🔄 Refresh All Icons
                </a>
                <a href="../app/" class="wp-button wp-button--secondary">
                    ← Back to Startpage
                </a>
            </div>
        </section>
        
        <?php if ($stats['count'] > 0): ?>
            <section class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">Cached Favicons</h2>
                <div class="wp-icon-grid">
                    <?php
                    $files = $faviconCache->getCachePreviewFiles();
                    foreach ($files as $file):
                        $filename = basename($file);
                        $domain = preg_replace('/-[a-f0-9]{12}\.[^.]+$/', '', $filename);
                    ?>
                        <div class="wp-icon-preview">
                            <img src="../cache/favicons/<?= $filename ?>" alt="<?= $domain ?>"
                                 onerror="this.style.display='none'">
                            <div class="wp-meta wp-truncate" title="<?= $domain ?>"><?= $domain ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        </div>
    </main>
</body>
</html> 
