<?php
require_once '../includes/session_config.php';
session_start();

require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-cache.php';
require_once '../includes/favicon/favicon-config.php';
require_once '../includes/color_map.php';
require_once '../includes/services/index-data-service.php';

// Initialize favicon cache
$faviconCache = new FaviconCache('../cache/favicons/');

// Require authentication
requireAuth($pdo);

$currentUserId = getCurrentUserId();

// Initialize the data service
$dataService = new IndexDataService($pdo, $currentUserId);

// Get current page ID (creates default page if needed)
$currentPageId = $dataService->getCurrentPageId();

// Get bookmarklet data
$bookmarkletData = $dataService->getBookmarkletData();
$isAddingBookmark = $bookmarkletData['isAddingBookmark'];
$prefillUrl = $bookmarkletData['prefillUrl'];
$prefillTitle = $bookmarkletData['prefillTitle'];
$prefillDesc = $bookmarkletData['prefillDesc'];
$urlError = $bookmarkletData['urlError'];

// Get categories and bookmarks
$categoriesData = $dataService->getCategoriesAndBookmarks();
$categories = $categoriesData['categories'];
$bookmarksByCategory = $categoriesData['bookmarksByCategory'];

// Get current page name
$currentPageName = $dataService->getCurrentPageName();

// Get all pages for dropdown
$allPages = $dataService->getAllPages();

// Get categories grouped by page for dropdowns
$categoriesByPage = $dataService->getCategoriesByPage();

// Version local assets so browser caches are refreshed after a deployment.
$moduleFiles = glob(__DIR__ . '/../assets/js/modules/*.js') ?: [];
$moduleVersion = $moduleFiles
    ? max(array_map('filemtime', $moduleFiles))
    : time();
$appJsVersion = filemtime(__DIR__ . '/../assets/js/app.js');
$bookmarkColorsVersion = filemtime(__DIR__ . '/../assets/css/bookmark-colors.css');
$mainCssVersion = filemtime(__DIR__ . '/../assets/css/main.css');
$responsiveCssVersion = filemtime(__DIR__ . '/../assets/css/responsive.css');
$currentUsername = getCurrentUsername();
$isLocalEnvironment = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Start Page</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../public/apple-touch-icon.png">
   
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
    <script src="../assets/js/app.js?v=<?= $appJsVersion ?>" defer onerror="console.error('Failed to load app.js')"></script>
 
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/bookmark-colors.css?v=<?= $bookmarkColorsVersion ?>" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= $mainCssVersion ?>" rel="stylesheet">
    <link href="../assets/css/responsive.css?v=<?= $responsiveCssVersion ?>" rel="stylesheet">

    <script>
        // Favicon configuration from PHP
        window.faviconConfig = <?= json_encode(FaviconConfig::getConfigForJavaScript()) ?>;
        // Expose color mapping to JS so token<->int stays in sync with PHP
        window.bookmarkColorMapping = <?= json_encode(getBookmarkColorMapping()) ?>; // {0:'none',1:'pink',...}
        window.bookmarkColorLabels = <?= json_encode(getBookmarkColorLabels()) ?>; // {'none':'None (default)',...}
        // Also provide reverse map token->int
        window.bookmarkColorTokenToInt = <?= json_encode(getBookmarkColorTokenToInt()) ?>;
        // CSS classes for dynamic class removal
        window.bookmarkBgClasses = <?= json_encode(getBookmarkBgClasses()) ?>;
        // Cache version used by the dynamic JavaScript module loader.
        window.moduleAssetVersion = <?= json_encode((string)$moduleVersion) ?>;
    </script>
    
</head>

<body>

    <!-- Flash Message Container -->
    <div id="flashMessage" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 hidden">
        <div class="flash-panel px-6 py-4 flex items-center gap-3">
            <div id="flashIcon" class="text-xl"></div>
            <div id="flashText" class="text-sm font-medium"></div>
            <button id="flashClose" class="ml-4 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Menu Bar -->
    <header class="app-header sticky top-0 z-10">
        <div class="app-header-inner">
            <div class="header-pages">
                <button id="pageDropdown" class="header-icon-button" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Choose page" title="Choose page">
                    <img src="../public/favicon-32x32.png" alt="" class="header-app-icon" id="pageDropdownIcon">
                </button>
                <button id="prevPageBtn" class="header-nav-button" type="button" aria-label="Previous page" title="Previous page (←)">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button title="Edit page name" id="pageEditButton" class="header-page-title" data-page-id="<?= $currentPageId ?>" data-page-name="<?= htmlspecialchars($currentPageName) ?>">
                    <?= htmlspecialchars($currentPageName) ?>
                </button>
                <button id="nextPageBtn" class="header-nav-button" type="button" aria-label="Next page" title="Next page (→)">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg>
                </button>
                <span id="pageCounter" class="header-page-counter" aria-label="Page position">
                    <span id="currentPageNum">1</span>/<span id="totalPages"><?= count($allPages) ?></span>
                </span>

                <div id="pageDropdownMenu" class="floating-menu page-dropdown-menu hidden" role="menu" aria-label="Choose page">
                    <?php foreach ($allPages as $page): ?>
                        <button class="page-option" role="menuitem" data-page-id="<?= $page['id'] ?>">
                            <?php if ($page['id'] == $currentPageId): ?>
                                <span class="page-option-marker is-current">✓</span>
                            <?php else: ?>
                                <span class="page-option-marker">○</span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($page['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="header-search search-box">
                <div class="header-search-field">
                    <svg class="header-search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input type="text" id="globalSearch" placeholder="Search all bookmarks…" class="mobile-search-input" autocomplete="off">
                    <kbd id="searchShortcutHint" class="search-shortcut-hint">⌘K</kbd>
                </div>
            </div>

            <div class="header-actions">
                <button id="mobileSearchToggle" class="header-icon-button mobile-search-toggle mobile-only" type="button" aria-label="Toggle search" title="Toggle search">🔍</button>
                <button id="addBookmarkButton" class="header-icon-button" type="button" aria-label="Add bookmark" title="Add bookmark">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                </button>
                <button id="accountMenuButton" class="account-menu-button" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="accountMenu">
                    <?php if ($isLocalEnvironment): ?><span class="environment-dot" aria-hidden="true"></span><?php endif; ?>
                    <span class="account-menu-name"><?= htmlspecialchars($currentUsername) ?></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
                </button>

                <div id="accountMenu" class="account-menu floating-menu hidden" role="menu" aria-label="User menu">
                    <div class="account-menu-header">
                        <span>Signed in as</span>
                        <strong><?= htmlspecialchars($currentUsername) ?></strong>
                        <?php if ($isLocalEnvironment): ?><small><span class="environment-dot" aria-hidden="true"></span> Local environment</small><?php endif; ?>
                    </div>
                    <div class="account-menu-section">
                        <button type="button" class="account-menu-item" role="menuitem" data-account-action="activity">Activity legend</button>
                        <a class="account-menu-item" role="menuitem" href="../tools/bookmarklet.php">Get bookmarklet</a>
                        <a class="account-menu-item" role="menuitem" href="../tools/cache-manager.php">Cache manager</a>
                        <?php if ($currentUserId === 1): ?><a class="account-menu-item" role="menuitem" href="admin.php">Admin panel</a><?php endif; ?>
                    </div>
                    <div class="account-menu-section">
                        <button type="button" class="account-menu-item" role="menuitem" data-account-action="password">Change password</button>
                    </div>
                    <div class="account-menu-section">
                        <a class="account-menu-item is-danger" role="menuitem" href="logout.php">Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div id="activityLegendModal" class="modal-backdrop hidden fixed inset-0 items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="activityLegendTitle">
        <div class="modal-panel activity-legend-panel">
            <div class="activity-legend-header">
                <div><h2 id="activityLegendTitle">Bookmark activity</h2><p>Activity is based on when a bookmark was last opened.</p></div>
                <button id="activityLegendClose" class="header-icon-button" type="button" aria-label="Close activity legend">×</button>
            </div>
            <div class="activity-legend-list">
                <?php foreach ([['recent', 'Used within 3 days'], ['fortnight', 'Used within 14 days'], ['normal', 'Used within 3 months'], ['stale', 'Stale or never used']] as [$state, $label]): ?>
                    <div class="activity-legend-row">
                        <span class="activity-legend-meter" data-usage-state="<?= $state ?>" aria-hidden="true">
                            <span class="bookmark-recency-arc">
                                <svg viewBox="0 0 20 20">
                                    <circle class="bookmark-recency-track" cx="10" cy="10" r="7"></circle>
                                    <circle class="bookmark-recency-value" cx="10" cy="10" r="7" pathLength="100"></circle>
                                </svg>
                            </span>
                        </span>
                        <span><?= $label ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="activity-legend-note">Opening a bookmark immediately moves it to the most recent level. Never-used bookmarks and bookmarks older than three months share the lowest level.</p>
        </div>
    </div>

    <main class="dashboard-main max-w-8xl mx-auto px-4 pb-4">
          
        <div id="categories-container" class="flex flex-wrap gap-3">
            <?php foreach ($categories as $cat): ?>
                <?php $bookmarkCount = count($bookmarksByCategory[$cat['id']]); ?>

                <!-- Header: Bookmark Category -->
                <section style="max-width:<?= $cat['width'] ?>px;" class="category-slot cursor-move w-full mobile:cursor-default" data-category-id="<?= $cat['id'] ?>">
                    <div class="category-card pt-1 p-2 relative w-full">
                        <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="text-gray-400 cursor-move flex-shrink-0 mobile:cursor-default mobile:opacity-30">⋮⋮</span>
                            <h2 title="Edit Category" class="category-title cursor-pointer hover:text-blue-600 transition-colors truncate min-w-0 flex-1" data-action="edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" data-page-id="<?= $cat['page_id'] ?>" data-width="<?= $cat['preferences']['cat_width'] ?? 3 ?>" data-no-description="<?= $cat['no_url_description'] ?>" data-show-favicon="<?= $cat['show_favicon'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </h2>
                            <!-- Mobile-friendly category edit button -->
                            <button 
                                title="Edit Category" 
                                data-action="edit-category" 
                                data-id="<?= $cat['id'] ?>" 
                                data-name="<?= htmlspecialchars($cat['name']) ?>" 
                                data-page-id="<?= $cat['page_id'] ?>" 
                                data-width="<?= $cat['preferences']['cat_width'] ?? 3 ?>" 
                                data-no-description="<?= $cat['no_url_description'] ?>" 
                                data-show-favicon="<?= $cat['show_favicon'] ?>"
                                class="mobile-only opacity-60 hover:opacity-100 transition-opacity duration-200 text-gray-500 hover:text-blue-600 p-1 rounded flex-shrink-0"
                            >
                                ✏️
                            </button>
                        </div>
                        <?php if (!empty($bookmarksByCategory[$cat['id']])): ?>
                            <button 
                                type="button"
                                class="open-all-category-btn"
                                data-category-id="<?= $cat['id'] ?>"
                                aria-label="Open all bookmarks in <?= htmlspecialchars($cat['name']) ?>"
                                title="Open all bookmarks in <?= htmlspecialchars($cat['name']) ?>"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </button>
                        <?php endif; ?>
                        </div>

                        <!-- Bookmark List -->
                        <div id="category-content-<?= $cat['id'] ?>" class="section-content<?= $bookmarkCount > 5 ? ' has-expand-control' : '' ?>">
                        <ul class="bookmark-list<?= $cat['show_favicon'] ? '' : ' no-favicons' ?>" data-category-id="<?= $cat['id'] ?>">
                            <?php if (empty($bookmarksByCategory[$cat['id']])): ?>
                                <li class="text-gray-400 text-sm italic py-3 px-2 text-center border border-dashed border-gray-200 rounded-lg bg-gray-50">
                                    <span class="opacity-60">📭 No bookmarks yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($bookmarksByCategory[$cat['id']] as $bm): ?>
                                    <?php
                                        $colorInt = isset($bm['color']) ? (int)$bm['color'] : 0;
                                        $bgToken = bookmarkColorToken($colorInt);
                                        $bgClass = bookmarkBgClassFromToken($bgToken);
                                        $usageState = in_array($bm['usage_state'] ?? '', ['recent', 'fortnight', 'normal', 'stale'], true)
                                            ? $bm['usage_state']
                                            : 'normal';
                                        $usageLabels = [
                                            'recent' => 'Used within the last 3 days',
                                            'fortnight' => 'Used within the last 14 days',
                                            'normal' => 'Used within the last 3 months',
                                            'stale' => empty($bm['last_clicked_at']) ? 'Never used' : 'Last used more than 3 months ago'
                                        ];
                                        $usageLabel = $usageLabels[$usageState];
                                    ?>
                                    <li class="bookmark-item<?= !empty($bm['description']) && !$cat['no_url_description'] ? ' has-description' : '' ?> mobile:not-draggable <?= $bgClass ?>"
                                        data-id="<?= $bm['id'] ?>" 
                                        data-title="<?= htmlspecialchars($bm['title']) ?>" 
                                        data-url="<?= htmlspecialchars($bm['url']) ?>" 
                                        data-description="<?= htmlspecialchars($bm['description'] ?? '') ?>"
                                        data-category-id="<?= $bm['category_id'] ?>"
                                        data-favicon-url="<?= htmlspecialchars($bm['favicon_url'] ?? '') ?>"
                                        data-color="<?= $colorInt ?>"
                                        data-usage-state="<?= $usageState ?>"
                                        data-last-clicked-at="<?= htmlspecialchars($bm['last_clicked_at'] ?? '') ?>"
                                        data-background-color="<?= $bgToken ?>">
                                        <!-- Bookmark icon and desktop drag handle -->
                                        <div class="bookmark-icon drag-handle mobile:cursor-default mobile:opacity-60"<?= $cat['show_favicon'] ? '' : ' style="display:none;"' ?>>
                                            <img src="<?= htmlspecialchars(FaviconConfig::getDisplayFaviconUrl($bm['favicon_url'] ?? '', $bm['url'] ?? '')) ?>" alt="" aria-hidden="true">
                                        </div>
                                        <div class="min-w-0 flex-1 no-drag flex flex-col justify-center">
                                            <!-- Bookmark title -->
                                            <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="bookmark-title" data-tooltip="<?= htmlspecialchars($bm['title']) ?>" data-tooltip-detail="<?= htmlspecialchars($bm['url']) ?>">
                                                <?= htmlspecialchars($bm['title']) ?>
                                                <!-- Bookmark description -->
                                                <?php if (!empty($bm['description']) && !$cat['no_url_description']): ?>
                                                    <p class="bookmark-description"><?= htmlspecialchars($bm['description']) ?></p>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <!-- Bookmark activity and actions -->
                                        <div class="bookmark-activity-slot flex-shrink-0 no-drag">
                                            <button
                                                type="button"
                                                class="bookmark-activity-button"
                                                data-action="bookmark-actions"
                                                data-id="<?= $bm['id'] ?>"
                                                data-usage-state="<?= $usageState ?>"
                                                aria-haspopup="menu"
                                                aria-expanded="false"
                                                aria-label="<?= htmlspecialchars($usageLabel) ?>. Bookmark actions"
                                                title="<?= htmlspecialchars($usageLabel) ?> — bookmark actions"
                                            >
                                                <span class="bookmark-recency-arc" aria-hidden="true">
                                                    <svg viewBox="0 0 20 20">
                                                        <circle class="bookmark-recency-track" cx="10" cy="10" r="7"></circle>
                                                        <circle class="bookmark-recency-value" cx="10" cy="10" r="7" pathLength="100"></circle>
                                                    </svg>
                                                </span>
                                            </button>
                                        </div>
                                    </li>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        </div>

                        <?php if ($bookmarkCount > 5): ?>
                            <div class="expand-control-footer">
                                <button
                                    type="button"
                                    class="expand-indicator"
                                    data-section-id="<?= $cat['id'] ?>"
                                    aria-controls="category-content-<?= $cat['id'] ?>"
                                    aria-expanded="false"
                                    aria-label="Show more bookmarks in <?= htmlspecialchars($cat['name']) ?>"
                                    title="Show more bookmarks in <?= htmlspecialchars($cat['name']) ?>"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <div class="text-center text-gray-600 text-sm opacity-30 hover:opacity-80 transition-opacity duration-300 mt-8 mb-6">
            Made with ❤️ and using PHP, Tailwind, Cursor & OpenAI (July 2025).
        </div>
    </footer>

    <!-- Invalid URL Error Modal -->
    <?php if (!empty($urlError)): ?>
    <div id="invalidUrlModal" class="modal-backdrop flex fixed inset-0 items-center justify-center z-50">
        <div class="modal-panel p-8 w-full max-w-md mx-4">
            <div class="text-center">
                <!-- Warning Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-6">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Invalid URL</h3>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($urlError) ?></p>
                <p class="text-sm text-gray-500 mb-8">The bookmarklet only works with valid HTTP and HTTPS websites.</p>
                
                <div class="flex gap-4">
                    <button onclick="window.close()" class="flex-1 bg-blue-500 text-white py-3 px-6 rounded-lg hover:bg-blue-600 transition font-medium">
                        Close Window
                    </button>
                    <button onclick="window.history.back()" class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-300 transition font-medium">
                        Go Back
                    </button>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

    <!-- Add Modal (via bookmarklet) -->
    <?php include '../includes/templates/modals/add-bookmark-modal.php'; ?>
    <!-- Delete Confirmation Modal -->
    <?php include '../includes/templates/modals/delete-confirmation-modal.php'; ?>
    <!-- Category Edit Modal -->
    <?php include '../includes/templates/modals/category-edit-modal.php'; ?>
    <!-- Context Menu -->
    <?php include '../includes/templates/modals/context-menu.php'; ?>
    <!-- Category Add Modal -->
    <?php include '../includes/templates/modals/category-add-modal.php'; ?>
    <!-- Page Add Modal -->
    <?php include '../includes/templates/modals/page-add-modal.php'; ?>
    <!-- Page Edit Modal -->
    <?php include '../includes/templates/modals/page-edit-modal.php'; ?>
    <!-- Edit Bookmark Modal -->
    <?php include '../includes/templates/modals/edit-bookmark-modal.php'; ?>
    <!-- Password Change Modal -->
    <?php include '../includes/templates/modals/password-change-modal.php'; ?>

    <!-- Search Results Overlay -->
    <div id="searchResults" class="modal-backdrop hidden fixed inset-0 z-40">
        <div class="absolute top-20 left-1/2 transform -translate-x-1/2 w-full max-w-3xl mx-4">
            <div class="modal-panel max-h-[70vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Search Results</h3>
                    <button id="closeSearch" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div id="searchResultsContent" class="overflow-y-auto max-h-[calc(70vh-80px)]">
                    <!-- Search results will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    let justLoaded = true;
    let searchVisible = false;
    let scrollTimeout;

    window.addEventListener("load", () => {
      setTimeout(() => justLoaded = false, 1000); // Wait 1s before allowing refresh
    });

    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "visible" && !justLoaded) {
        try {
          location.reload();
        } catch (error) {
          console.error('Failed to reload page:', error);
        }
      }
    });

    // Add console message when page is reloaded
    // Store a flag in sessionStorage to detect reloads
    const wasReloaded = sessionStorage.getItem('pageReloaded');
    
    if (wasReloaded) {
      const reloadTime = new Date().toLocaleString();
      console.log(`Page was reloaded at ${reloadTime}!`);
      console.log('type DEBUG.help() to see debug options');
      sessionStorage.removeItem('pageReloaded'); // Clear the flag
    } else {
      console.log("Page loaded for the first time!");
    }

    // Set flag before page unloads
    window.addEventListener("beforeunload", function() {
      sessionStorage.setItem('pageReloaded', 'true');
    });

    // Mobile search toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileSearchToggle = document.getElementById('mobileSearchToggle');
        const searchBox = document.querySelector('.search-box');
        const globalSearch = document.getElementById('globalSearch');
        
        if (mobileSearchToggle && searchBox) {
            // Toggle search visibility
            mobileSearchToggle.addEventListener('click', function() {
                searchVisible = !searchVisible;
                
                if (searchVisible) {
                    searchBox.classList.add('active');
                    globalSearch.focus();
                    mobileSearchToggle.textContent = '❌'; // Change to X when open
                } else {
                    searchBox.classList.remove('active');
                    globalSearch.blur();
                    mobileSearchToggle.textContent = '🔍'; // Change back to magnifying glass
                }
            });
            
            // Auto-hide search on scroll
            window.addEventListener('scroll', function() {
                if (searchVisible) {
                    // Clear existing timeout
                    clearTimeout(scrollTimeout);
                    
                    // Set new timeout to hide search after scrolling stops
                    scrollTimeout = setTimeout(function() {
                        if (searchVisible) {
                            searchVisible = false;
                            searchBox.classList.remove('active');
                            globalSearch.blur();
                            mobileSearchToggle.textContent = '🔍';
                        }
                    }, 1000); // Hide after 1 second of no scrolling
                }
            });
            
            // Hide search when clicking outside
            document.addEventListener('click', function(event) {
                if (searchVisible && 
                    !searchBox.contains(event.target) && 
                    !mobileSearchToggle.contains(event.target)) {
                    searchVisible = false;
                    searchBox.classList.remove('active');
                    globalSearch.blur();
                    mobileSearchToggle.textContent = '🔍';
                }
            });
        }
    });
    </script>
    
</body>
</html>
