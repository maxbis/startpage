<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-cache.php';

// Function to get page icon based on name
// function getPageIcon($pageName) {
//     $name = strtolower($pageName);
    
//     // Map page names to icons
//     $iconMap = [
//         'work' => '💼',
//         'personal' => '👤',
//         'home' => '🏠',
//         'school' => '🎓',
//         'study' => '📚',
//         'gaming' => '🎮',
//         'social' => '👥',
//         'news' => '📰',
//         'shopping' => '🛒',
//         'finance' => '💰',
//         'health' => '🏥',
//         'travel' => '✈️',
//         'music' => '🎵',
//         'video' => '🎬',
//         'sports' => '⚽',
//         'tech' => '💻',
//         'design' => '🎨',
//         'cooking' => '👨‍🍳',
//         'fitness' => '💪',
//         'books' => '📖',
//         'default' => '📄'
//     ];
    
//     // Check for exact matches first
//     foreach ($iconMap as $keyword => $icon) {
//         if (strpos($name, $keyword) !== false) {
//             return $icon;
//         }
//     }
    
//     // Return default icon
//     return $iconMap['default'];
// }

// Initialize favicon cache
$faviconCache = new FaviconCache('../cache/favicons/');

// Require authentication
requireAuth($pdo);

$currentUserId = getCurrentUserId();

// Get user's pages to determine default page
$stmt = $pdo->prepare('SELECT id FROM pages WHERE user_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
$stmt->execute([$currentUserId]);
$userPage = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle page selection via cookie
$currentPageId = $userPage ? $userPage['id'] : null; // Use user's first page as default

// Check if page cookie exists and belongs to current user
if (isset($_COOKIE['current_page_id'])) {
    $cookiePageId = (int)$_COOKIE['current_page_id'];
    
    // Verify the page belongs to the current user
    $stmt = $pdo->prepare('SELECT id FROM pages WHERE id = ? AND user_id = ?');
    $stmt->execute([$cookiePageId, $currentUserId]);
    if ($stmt->fetch()) {
        $currentPageId = $cookiePageId;
    }
}

// If user has no pages, create a default page
if (!$currentPageId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO pages (user_id, name, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$currentUserId, 'My Startpage', 0]);
        $currentPageId = $pdo->lastInsertId();
        
        // Create some default categories
        $defaultCategories = [
            ['Work', 0],
            ['Personal', 1],
            ['Tools', 2]
        ];
        
        foreach ($defaultCategories as $category) {
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, page_id, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$currentUserId, $category[0], $currentPageId, $category[1]]);
        }
    } catch (Exception $e) {
        // Handle error - could log this
        $currentPageId = 1; // Fallback to admin page if creation fails
    }
}

// Set default page cookie if it doesn't exist or is invalid
if (!isset($_COOKIE['current_page_id']) || $_COOKIE['current_page_id'] != $currentPageId) {
    setcookie('current_page_id', $currentPageId, time() + (86400 * 365), '/'); // 1 year expiry
}

// Check if we're adding a bookmark via bookmarklet or quick add
$isAddingBookmark = isset($_GET['add']) && $_GET['add'] == '1';
$prefillUrl = $_GET['url'] ?? '';
$prefillTitle = $_GET['title'] ?? '';
$prefillDesc = $_GET['desc'] ?? '';

// Enhanced URL validation
$isValidUrl = false;
$urlError = '';

if ($prefillUrl != '') {
    // Check if URL is valid and uses HTTP/HTTPS protocol
    if (filter_var($prefillUrl, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($prefillUrl);
        if (isset($parsedUrl['scheme']) && in_array($parsedUrl['scheme'], ['http', 'https'])) {
            $isValidUrl = true;
        }
    }
}

// Only show modal if we have a valid URL
if (!$isValidUrl) {
    $isAddingBookmark = false;
}

// Get all data in one optimized query
$stmt = $pdo->prepare('
    SELECT 
        c.id as category_id,
        c.name as category_name,
        c.page_id,
        c.sort_order as category_sort,
        p.name as page_name,
        p.sort_order as page_sort,
        b.id as bookmark_id,
        b.title as bookmark_title,
        b.url as bookmark_url,
        b.description as bookmark_description,
        b.favicon_url,
        b.sort_order as bookmark_sort
    FROM categories c 
    JOIN pages p ON c.page_id = p.id AND p.user_id = ?
    LEFT JOIN bookmarks b ON c.id = b.category_id AND b.user_id = ?
    WHERE c.page_id = ? AND c.user_id = ?
    ORDER BY c.sort_order ASC, c.id ASC, b.sort_order ASC, b.id ASC
');
$stmt->execute([$currentUserId, $currentUserId, $currentPageId, $currentUserId]);
$allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the current page name (separate query to handle pages with no categories)
$stmt = $pdo->prepare('SELECT name FROM pages WHERE id = ? AND user_id = ?');
$stmt->execute([$currentPageId, $currentUserId]);
$pageResult = $stmt->fetch(PDO::FETCH_ASSOC);
$currentPageName = $pageResult ? $pageResult['name'] : 'My Start Page';

// Process the data
$categories = [];
$bookmarksByCategory = [];

foreach ($allData as $row) {
    $categoryId = $row['category_id'];
    
    // Add category if not already added
    if (!isset($categories[$categoryId])) {
        $categories[$categoryId] = [
            'id' => $categoryId,
            'name' => $row['category_name'],
            'page_id' => $row['page_id'],
            'sort_order' => $row['category_sort']
        ];
        
        // Initialize empty array for this category
        $bookmarksByCategory[$categoryId] = [];
    }
    
    // Add bookmark if exists
    if ($row['bookmark_id']) {
        $bookmarksByCategory[$categoryId][] = [
            'id' => $row['bookmark_id'],
            'title' => $row['bookmark_title'],
            'url' => $row['bookmark_url'],
            'description' => $row['bookmark_description'],
            'favicon_url' => $row['favicon_url'],
            'category_id' => $categoryId,
            'sort_order' => $row['bookmark_sort']
        ];
    }
}

// Convert categories array to indexed array for compatibility
$categories = array_values($categories);

// Get all available pages for the dropdown
$stmt = $pdo->prepare('SELECT id, name FROM pages WHERE user_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$currentUserId]);
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories grouped by page for dropdowns (separate query for dropdown)
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.page_id, p.name as page_name 
    FROM categories c 
    JOIN pages p ON c.page_id = p.id AND p.user_id = ?
    WHERE c.user_id = ?
    ORDER BY p.sort_order ASC, p.id ASC, c.sort_order ASC, c.id ASC
');
$stmt->execute([$currentUserId, $currentUserId]);
$allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group categories by page
$categoriesByPage = [];
foreach ($allCategories as $cat) {
    $pageId = $cat['page_id'];
    if (!isset($categoriesByPage[$pageId])) {
        $categoriesByPage[$pageId] = [
            'page_name' => $cat['page_name'],
            'categories' => []
        ];
    }
    $categoriesByPage[$pageId]['categories'][] = $cat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Start Page</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
   
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
    <script src="../assets/js/app.js" defer onerror="console.error('Failed to load app.js')" onload="console.log('app.js loaded successfully')"></script>
 
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        .section-content {
            min-height: 245px;
            max-height: 245px;
            overflow-y: auto;
            overflow-x: hidden;
            transition: max-height 0.3s ease-in-out;
            
            /* Firefox: make the scrollbar thinner */
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.3) transparent;
        }
        
        .section-content::-webkit-scrollbar {
            width: 6px;
        }

        .section-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .section-content::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.3);
            border-radius: 3px;
        }
        
        .section-content.expanded {
            max-height: 700px;
        }
        
        /* Ensure categories maintain their individual heights */
        section[data-category-id] {
            align-self: flex-start;
        }
        
        .expand-indicator {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(209, 213, 219, 0.5);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.7;
            z-index: 10;
        }
        
        .expand-indicator:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 1);
            border-color: rgba(59, 130, 246, 0.5);
        }
        
        .expand-indicator svg {
            width: 12px;
            height: 12px;
            transition: transform 0.2s ease;
        }
        
        .expand-indicator.expanded svg {
            transform: rotate(180deg);
        }
        
        /* Ensure proper text truncation for bookmark titles */
        .bookmark-title {
            max-width: 24ch;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Drag handle styling */
        .drag-handle {
            cursor: move;
        }
        
        /* Prevent dragging on non-drag areas */
        .no-drag {
            cursor: default;
        }
        
        /* Ensure the entire bookmark item doesn't show drag cursor by default */
        li.draggable {
            cursor: default;
        }

        /* URL input styling for edit dialog */
        #edit-url {
            max-width: 100%;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            word-break: break-all;
            overflow-wrap: break-word;
        }
        
        /* Ensure edit dialog doesn't get too wide */
        #editModal .bg-white {
            max-width: 28rem;
            width: 100%;
        }
        
        /* URL text truncation in edit dialog */
        #edit-url::placeholder {
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        /* Favicon URL display styling */
        #edit-favicon-url {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.75rem;
            color: #6b7280;
        }

        body {
            /* background-image: url('assets/images/seamless_texture_800px_150dpi_spots_gray.png'); */
            background-image: linear-gradient(rgba(100, 0, 0, 0.05), rgba(0, 0, 0, 0.05)), url('../assets/images/seamless_texture_800px_150dpi_spots_gray.jpg');
            background-repeat: repeat;
            background-size: auto; /* or 'contain' if you want exact tile size */
            background-attachment: fixed; /* keeps it static when scrolling */
            background-color: #ffffff; /* fallback color */
            margin: 0;
            padding: 0;
            font-size: 14px;
        }

        /* Mobile Responsive Improvements */
        @media (max-width: 768px) {
            /* Increase base font size for mobile */
            body {
                font-size: 16px;
            }
            
            /* Larger category headers on mobile */
            section[data-category-id] h2 {
                font-size: 1.25rem !important;
                font-weight: 600;
            }
            
            /* Larger bookmark titles on mobile */
            .bookmark-title {
                font-size: 1rem;
                max-width: none;
                white-space: normal;
                line-height: 1.4;
            }
            
            /* Larger favicon on mobile */
            .drag-handle {
                width: 2rem !important;
                height: 2rem !important;
                margin-top: 0.25rem;
            }
            
            /* Better spacing for bookmark items on mobile */
            li.draggable {
                padding: 0.75rem !important;
                margin-bottom: 0.5rem;
            }
            
            /* Larger text in modals on mobile */
            .modal-content h3 {
                font-size: 1.5rem;
            }
            
            .modal-content label {
                font-size: 1rem;
            }
            
            .modal-content input,
            .modal-content textarea,
            .modal-content select {
                font-size: 1rem;
                padding: 0.75rem;
            }
            
            /* Better button sizing on mobile */
            button {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
            
            /* Adjust category section width for mobile */
            section[data-category-id] {
                max-width: 100% !important;
                width: 100% !important;
                margin-bottom: 1rem;
            }
            
            /* Better header layout on mobile */
            header .max-w-8xl {
                padding: 0.5rem 1rem;
            }
            
            /* Adjust search box for mobile */
            #globalSearch {
                font-size: 1rem;
                padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            }
            
            /* Better footer text on mobile */
            footer {
                font-size: 0.875rem;
                padding: 1rem;
            }
        }

        /* Extra small mobile devices */
        @media (max-width: 480px) {
            body {
                font-size: 18px;
            }
            
            section[data-category-id] h2 {
                font-size: 1.5rem !important;
            }
            
            .bookmark-title {
                font-size: 1.125rem;
            }
            
            .drag-handle {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
            
            /* Single column layout for very small screens */
            #categories-container {
                flex-direction: column;
            }
            
            section[data-category-id] {
                margin-bottom: 1.5rem;
            }
        }
    </style>

</head>
<body>

    <!-- Flash Message Container -->
    <div id="flashMessage" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 hidden">
        <div class="bg-white border rounded-lg shadow-lg px-6 py-4 flex items-center gap-3">
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
    <header class="bg-gradient-to-b from-gray-300 to-gray-50 shadow sticky top-0 z-10">
        <div class="max-w-8xl mx-auto px-4 py-1 flex items-center">
            <!-- Left side: Environment indicator and Page dropdown -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="relative">
                    <div class="flex items-center gap-2 text-2xl font-bold text-blue-500">
                        <button id="pageDropdown" class="flex items-center gap-2 hover:text-blue-600 transition-colors">
                            <span><img src="../public/favicon-32x32.png" alt="favicon" class="w-6 h-6 transition-transform duration-200" id="pageDropdownIcon"></span>
                        </button>
                        <button title="Edit Page Name" id="pageEditButton" class="hover:text-blue-600 transition-colors" data-page-id="<?= $currentPageId ?>" data-page-name="<?= htmlspecialchars($currentPageName) ?>">
                            <?= htmlspecialchars($currentPageName) ?>
                        </button>
                    </div>
                    <div id="pageDropdownMenu" style="min-width:200px;" class="hidden absolute top-full left-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                        <?php foreach ($allPages as $page): ?>
                            <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 page-option" data-page-id="<?= $page['id'] ?>">
                                <?php if ($page['id'] == $currentPageId): ?>
                                    <span class="text-blue-500">✓</span>
                                <?php else: ?>
                                    <span class="text-gray-400">○</span>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($page['name']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Center: Search Box -->
            <div class="flex-1 flex justify-center mr-20">
                <div style="max-width:200px;" class="relative w-full">
                    <input 
                        type="text" 
                        id="globalSearch" 
                        placeholder="Search all bookmarks..." 
                        class="w-full px-4 py-1 pl-10 pr-4 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Right side: User info -->

              <?php if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false): ?>
                
                <div class="bg-red-100 ml-8 border border-red-300 text-red-800 px-2 py-1 rounded-md text-xs font-medium flex items-center gap-1">
                    <span class="ml-2 mr-2 text-base"> ⚠️  <?= htmlspecialchars(getCurrentUsername()) ?>@Localhost</span>
                </div>

            <?php else: ?>
            
                <div class="flex-shrink-0">
                    <span class="text-blue-400 text-sm">Welcome, <?= htmlspecialchars(getCurrentUsername()) ?></span>
                </div>

            <?php endif; ?>

        </div>
    </header>

    <main class="max-w-8xl mx-auto px-4 py-4">
        <div id="categories-container" class="flex flex-wrap gap-3">
            <?php foreach ($categories as $cat): ?>
                <?php $bookmarkCount = count($bookmarksByCategory[$cat['id']]); ?>

                <!-- Header: Bookmark Category -->
                <section style="max-width:274px;background-color:rgba(240, 247, 255, 0.75);" class="rounded-2xl shadow-lg pt-1 p-2 relative border border-gray-400 cursor-move w-full" data-category-id="<?= $cat['id'] ?>">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="text-gray-400 cursor-move flex-shrink-0">⋮⋮</span>
                            <h2 title="Edit Catergory" class="opacity-90 text-lg font-semibold text-gray-600 cursor-pointer hover:text-blue-600 hover:opacity-100 transition-colors truncate min-w-0 flex-1" data-action="edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" data-page-id="<?= $cat['page_id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </h2>
                        </div>
                        <?php if (!empty($bookmarksByCategory[$cat['id']])): ?>
                            <button 
                                class="open-all-category-btn opacity-40 hover:opacity-100 transition-opacity duration-200 text-gray-500 hover:text-blue-600 p-1 rounded"
                                data-category-id="<?= $cat['id'] ?>"
                                title="Open all bookmarks in this category"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Bookmark List -->
                    <div class="section-content">
                        <ul class="space-y-1" data-category-id="<?= $cat['id'] ?>" class="bookmark-list">
                            <?php if (empty($bookmarksByCategory[$cat['id']])): ?>
                                <li class="text-gray-400 text-sm italic py-3 px-2 text-center border border-dashed border-gray-200 rounded-lg bg-gray-50">
                                    <span class="opacity-60">📭 No bookmarks yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($bookmarksByCategory[$cat['id']] as $bm): ?>
                                    
                                    <li class="opacity-90 hover:opacity-100 border border-gray-300 bg-gray-50 hover:bg-yellow-100 transition pl-2 pr-2 pb-1 pt-1 rounded-lg shadow-sm flex items-start gap-3 draggable" 
                                        data-id="<?= $bm['id'] ?>" 
                                        data-title="<?= htmlspecialchars($bm['title']) ?>" 
                                        data-url="<?= htmlspecialchars($bm['url']) ?>" 
                                        data-description="<?= htmlspecialchars($bm['description'] ?? '') ?>"
                                        data-category-id="<?= $bm['category_id'] ?>"
                                        data-favicon-url="<?= htmlspecialchars($bm['favicon_url'] ?? '') ?>">
                                        <!-- Bookmark icon -->
                                        <img src="<?= htmlspecialchars($bm['favicon_url'] ? ($bm['favicon_url'] && strpos($bm['favicon_url'], 'cache/') === 0 ? '../' . $bm['favicon_url'] : $bm['favicon_url']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIxNSIgZmlsbD0iIzRBOTBFMiIgc3Ryb2tlPSIjMkM1QUEwIiBzdHJva2Utd2lkdGg9IjIiLz4KICA8cGF0aCBkPSJNMTYgMUM3LjcxNiAxIDEgNy43MTYgMSAxNnM2LjcxNiAxNSAxNSAxNSAxNS02LjcxNiAxNS0xNVMyNC4yODQgMSAxNiAxeiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMkM1QUEwIiBzdHJva2Utd2lkdGg9IjIiLz4KICA8cGF0aCBkPSJNMSAxNmgzME0xNiAxYzUuNTIzIDAgMTAgNC40NzcgMTAgMTBzLTQuNDc3IDEwLTEwIDEwUzYgMjYuNTIzIDYgMjFzNC40NzctMTAgMTAtMTB6IiBzdHJva2U9IiNGRkZGRkYiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KICA8Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIzIiBmaWxsPSIjRkZGRkZGIi8+CiAgPHBhdGggZD0iTTE2IDEzdjZNMTMgMTZoNiIgc3Ryb2tlPSIjNEE5MEUyIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=') ?>" alt="favicon" class="w-6 h-6 mt-1 rounded flex-shrink-0 cursor-move drag-handle">
                                        <div class="min-w-0 flex-1 no-drag">
                                            <!-- Bookmark title -->
                                            <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline block bookmark-title" title="Open: <?= htmlspecialchars($bm['title']) ?>">
                                                <?= htmlspecialchars($bm['title']) ?>
                                                <!-- Bookmark description -->
                                                <?php if (!empty($bm['description'])): ?>
                                                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($bm['description']) ?></p>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <!-- Bookmark edit -->
                                        <div class="flex gap-2 text-sm text-gray-500 flex-shrink-0 no-drag">
                                            <button title="Edit Bookmark" data-action="edit" data-id="<?= $bm['id'] ?>" style="font-size:9px;" class="opacity-40 hover:opacity-100 transition-opacity duration-200">✏️</button>
                                        </div>
                                    </li>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($bookmarkCount > 5): ?>
                        <div class="expand-indicator" data-section-id="<?= $cat['id'] ?>">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    <?php endif; ?>

                </section>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <div class="text-center text-gray-600 text-sm mt-12 pb-0 opacity-60 hover:opacity-100 transition-opacity duration-300">
            <a href="../tools/cache-manager.php" class="text-black-600 hover:text-blue-600 transition-colors">Cache Manager</a> | 
            <a href="../tools/bookmarklet.php" class="text-gray-600 hover:text-blue-600 transition-colors">Get Bookmarklet</a> | 
            <a href="#" id="changePasswordLink" class="text-gray-600 hover:text-blue-600 transition-colors">Change password</a> | 
            <?php if ($currentUserId === 1): ?>
                <a href="admin.php" class="text-gray-600 hover:text-blue-600 transition-colors">Admin</a> | 
            <?php endif; ?> 
            <a href="logout.php" id="changePasswordLink" class="text-gray-600 hover:text-blue-600 transition-colors">Logout <?= htmlspecialchars(getCurrentUsername()) ?></a>
        </div> 
        <div class="text-center text-gray-600 text-sm opacity-30 hover:opacity-80 transition-opacity duration-300">
            Made with ❤️ and using PHP, Tailwind, Cursor & OpenAI (July 2025).
        </div>
    </footer>

    <!-- Invalid URL Error Modal -->
    <?php if (!empty($urlError)): ?>
    <div id="invalidUrlModal" class="flex fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 w-full max-w-md mx-4 shadow-2xl">
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

    <!-- Quick Add Modal (via bookmarklet) -->
    <div id="quickAddModal" class="<?= $isAddingBookmark ? 'flex' : 'hidden' ?> fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">📌 Add Bookmark</h3>
            <form id="quickAddForm" class="space-y-4">
                <div>
                    <label for="quick-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" id="quick-title" value="<?= htmlspecialchars($prefillTitle) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="quick-url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                    <input type="url" id="quick-url" value="<?= htmlspecialchars($prefillUrl) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="quick-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <textarea id="quick-description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($prefillDesc) ?></textarea>
                </div>
                <div>
                    <label for="quick-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="quick-category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                        <?php foreach ($categoriesByPage as $pageId => $pageData): ?>
                            <optgroup label="📄 <?= htmlspecialchars($pageData['page_name']) ?>">
                                <?php foreach ($pageData['categories'] as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Bookmark</button>
                    <button type="button" id="quickAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="text-center">
                <!-- Warning Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Delete Item</h3>
                <p class="text-gray-600 mb-0">Are you sure you want to delete?</p>
                <p class="mt-3 mb-2"><span id="deleteBookmarkTitle" class="font-medium text-gray-900"></span></p>
                <p class="text-sm text-gray-500 mb-8">This action cannot be undone.</p>
                
                <div class="flex gap-4">
                    <button id="deleteConfirm" class="flex-1 bg-red-500 text-white py-3 px-6 rounded-lg hover:bg-red-600 transition font-medium">
                        Delete Item
                    </button>
                    <button id="deleteCancel" class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-300 transition font-medium">
                        Cancel
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Category Edit Modal -->
    <div id="categoryEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Edit Category</h3>
            <form id="categoryEditForm" class="space-y-4">
                <input type="hidden" id="category-edit-id">
                <div>
                    <label for="category-edit-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                    <input type="text" id="category-edit-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="category-edit-page" class="block text-sm font-medium text-gray-700 mb-1">Page</label>
                    <select id="category-edit-page" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                        <?php foreach ($allPages as $page): ?>
                            <option value="<?= $page['id'] ?>"><?= htmlspecialchars($page['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                    <button type="button" id="categoryEditDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                    <button type="button" id="categoryEditCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="hidden fixed bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50 min-w-[160px]">
        <button id="contextAddLink" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2">
            <span class="text-lg">🔗</span>
            <span>Add Link</span>
        </button>
        <div class="border-t border-gray-200 my-1"></div>
        <button id="contextAddCategory" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2">
            <span class="text-lg">📁</span>
            <span>Add Category</span>
        </button>
        <div class="border-t border-gray-200 my-1"></div>
        <button id="contextAddPage" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2">
            <span class="text-lg">📄</span>
            <span>Add Page</span>
        </button>
    </div>

    <!-- Category Add Modal -->
    <div id="categoryAddModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Add Category</h3>
            <form id="categoryAddForm" class="space-y-4">
                <div>
                    <label for="category-add-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                    <input type="text" id="category-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter category name..." required>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Category</button>
                    <button type="button" id="categoryAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Page Add Modal -->
    <div id="pageAddModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Add Page</h3>
            <form id="pageAddForm" class="space-y-4">
                <div>
                    <label for="page-add-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                    <input type="text" id="page-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter page name..." required>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Page</button>
                    <button type="button" id="pageAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Page Edit Modal -->
    <div id="pageEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Edit Page</h3>
            <form id="pageEditForm" class="space-y-4">
                <input type="hidden" id="page-edit-id">
                <div>
                    <label for="page-edit-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                    <input type="text" id="page-edit-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                    <button type="button" id="pageEditDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                    <button type="button" id="pageEditCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Edit Bookmark</h3>
            <form id="editForm" class="space-y-4">
                <input type="hidden" id="edit-id">
                <div>
                    <label for="edit-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" id="edit-title" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="edit-url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                    <input type="url" id="edit-url" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <textarea id="edit-description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                    <div class="flex items-center gap-3 p-3 border rounded-lg bg-gray-50">
                        <img id="edit-favicon" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIxNSIgZmlsbD0iIzRBOTBFMiIgc3Ryb2tlPSIjMkM1QUEwIiBzdHJva2Utd2lkdGg9IjIiLz4KICA8cGF0aCBkPSJNMTYgMUM3LjcxNiAxIDEgNy43MTYgMSAxNnM2LjcxNiAxNSAxNSAxNSAxNS02LjcxNiAxNS0xNVMyNC4yODQgMSAxNiAxeiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMkM1QUEwIiBzdHJva2Utd2lkdGg9IjIiLz4KICA8cGF0aCBkPSJNMSAxNmgzME0xNiAxYzUuNTIzIDAgMTAgNC40NzcgMTAgMTBzLTQuNDc3IDEwLTEwIDEwUzYgMjYuNTIzIDYgMjFzNC40NzctMTAgMTAtMTB6IiBzdHJva2U9IiNGRkZGRkYiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KICA8Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIzIiBmaWxsPSIjRkZGRkZGIi8+CiAgPHBhdGggZD0iTTE2IDEzdjZNMTMgMTZoNiIgc3Ryb2tlPSIjNEE5MEUyIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=" alt="Favicon" class="w-6 h-6 rounded flex-shrink-0">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600" id="edit-favicon-url">No favicon available</p>
                        </div>
                        <button type="button" id="edit-refresh-favicon" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
                <div>
                    <label for="edit-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="edit-category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                        <?php foreach ($categoriesByPage as $pageId => $pageData): ?>
                            <optgroup label="📄 <?= htmlspecialchars($pageData['page_name']) ?>">
                                <?php foreach ($pageData['categories'] as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                    <button type="button" id="editDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                    <button type="button" id="editCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordChangeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">🔐 Change Password</h3>
            <form id="passwordChangeForm" class="space-y-4">
                <div>
                    <label for="current-password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" id="current-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="new-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" id="confirm-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Change Password</button>
                    <button type="button" id="passwordChangeCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Search Results Overlay -->
    <div id="searchResults" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40">
        <div class="absolute top-20 left-1/2 transform -translate-x-1/2 w-full max-w-3xl mx-4">
            <div class="bg-white rounded-lg shadow-xl max-h-[70vh] overflow-hidden">
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
      sessionStorage.removeItem('pageReloaded'); // Clear the flag
    } else {
      console.log("Page loaded for the first time!");
    }

    // Set flag before page unloads
    window.addEventListener("beforeunload", function() {
      sessionStorage.setItem('pageReloaded', 'true');
    });
    </script>
</body>
</html>