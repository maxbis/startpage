<?php
require_once 'includes/db.php';

// Handle page selection via cookie
$currentPageId = 1; // Default page ID

// Check if page cookie exists
if (isset($_COOKIE['current_page_id'])) {
    $currentPageId = (int)$_COOKIE['current_page_id'];
} else {
    // Set default page cookie if it doesn't exist
    setcookie('current_page_id', '1', time() + (86400 * 365), '/'); // 1 year expiry
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

// Load categories for the current page
$stmt = $pdo->prepare('SELECT * FROM categories WHERE page_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$currentPageId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current page name
$stmt = $pdo->prepare('SELECT name FROM pages WHERE id = ?');
$stmt->execute([$currentPageId]);
$currentPage = $stmt->fetch(PDO::FETCH_ASSOC);
$currentPageName = $currentPage ? $currentPage['name'] : 'My Start Page';

// Get all available pages for the dropdown
$stmt = $pdo->query('SELECT id, name FROM pages ORDER BY sort_order ASC, id ASC');
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories grouped by page for dropdowns
$stmt = $pdo->query('
    SELECT c.id, c.name, c.page_id, p.name as page_name 
    FROM categories c 
    JOIN pages p ON c.page_id = p.id 
    ORDER BY p.sort_order ASC, p.id ASC, c.sort_order ASC, c.id ASC
');
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

// Load bookmarks per category
$bookmarksByCategory = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare('SELECT * FROM bookmarks WHERE category_id = ? ORDER BY sort_order ASC');
    $stmt->execute([$cat['id']]);
    $bookmarksByCategory[$cat['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📌 My Start Page</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
    <script src="assets/js/app.js" defer onerror="console.error('Failed to load app.js')" onload="console.log('app.js loaded successfully')"></script>
 
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        .section-content {
            max-height: 250px;
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
            max-width: 18ch;
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
    </style>

</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-300 text-gray-800 min-h-screen font-sans">

    <!-- Menu Bar -->
    <header class="bg-white shadow sticky top-0 z-10">
        <div class="max-w-8xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="flex items-center gap-2 text-2xl font-bold text-blue-500">
                        <button id="pageDropdown" class="flex items-center gap-2 hover:text-blue-600 transition-colors">
                            <span>📌</span>
                        </button>
                        <button id="pageEditButton" class="hover:text-blue-600 transition-colors" data-page-id="<?= $currentPageId ?>" data-page-name="<?= htmlspecialchars($currentPageName) ?>">
                            <?= htmlspecialchars($currentPageName) ?>
                        </button>
                    </div>
                    <div id="pageDropdownMenu" class="hidden absolute top-full left-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50 min-w-[200px]">
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
            <div class="flex gap-3">
                <!-- <a href="bookmarklet.php" class="opacity-50 hover:opacity-100 transition-opacity duration-300 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition">
                    📌 Get Bookmarklet
                </a> -->
            </div>
        </div>
    </header>

    <main class="max-w-8xl mx-auto px-4 py-4">
        <div id="categories-container" class="flex flex-wrap gap-4">
            <?php foreach ($categories as $cat): ?>
                <?php $bookmarkCount = count($bookmarksByCategory[$cat['id']]); ?>

                <!-- Header: Bookmark Category -->
                <section style="max-width:270px;font-size:13px;" class="bg-white rounded-2xl shadow-lg pt-1 p-3 relative border border-gray-200 cursor-move w-full" data-category-id="<?= $cat['id'] ?>">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 cursor-move">⋮⋮</span>
                            <h2 class="opacity-90 text-lg font-semibold text-gray-600 cursor-pointer hover:text-blue-600 hover:opacity-100 transition-colors" data-action="edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </h2>
                        </div>
                    </div>

                    <!-- Bookmark List -->
                    <div class="section-content">
                        <ul class="space-y-1" data-category-id="<?= $cat['id'] ?>" class="bookmark-list">
                            <?php foreach ($bookmarksByCategory[$cat['id']] as $bm): ?>
                                
                                <li class="opacity-90 hover:opacity-100 border border-gray-300 bg-gray-50 hover:bg-yellow-100 transition pl-2 pr-2 pb-1 pt-1 rounded-lg shadow-sm flex items-start gap-3 draggable" 
                                    data-id="<?= $bm['id'] ?>" 
                                    data-title="<?= htmlspecialchars($bm['title']) ?>" 
                                    data-url="<?= htmlspecialchars($bm['url']) ?>" 
                                    data-description="<?= htmlspecialchars($bm['description'] ?? '') ?>"
                                    data-category-id="<?= $bm['category_id'] ?>">
                                    <!-- Bookmark icon -->
                                    <img src="<?= htmlspecialchars($bm['favicon_url']) ?>" alt="favicon" class="w-6 h-6 mt-1 rounded flex-shrink-0 cursor-move drag-handle">
                                    <div class="min-w-0 flex-1 no-drag">
                                        <!-- Bookmark title -->
                                        <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline block bookmark-title" title="<?= htmlspecialchars($bm['title']) ?>">
                                            <?= htmlspecialchars($bm['title']) ?>
                                            <!-- Bookmark description -->
                                            <?php if (!empty($bm['description'])): ?>
                                                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($bm['description']) ?></p>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <!-- Bookmark edit -->
                                    <div class="flex gap-2 text-sm text-gray-500 flex-shrink-0 no-drag">
                                        <button data-action="edit" data-id="<?= $bm['id'] ?>" style="font-size:9px;" class="opacity-40 hover:opacity-100 transition-opacity duration-200">✏️</button>
                                    </div>
                                </li>

                            <?php endforeach; ?>
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
            <a href="cache-manager.php" class="text-black-600 hover:text-blue-600 transition-colors">Cache Manager</a> | 
            <a href="bookmarklet.php" class="text-gray-600 hover:text-blue-600 transition-colors">Get Bookmarklet</a>
        </div>
        <div class="text-center text-gray-600 text-sm opacity-30 hover:opacity-80 transition-opacity duration-300">
            Made with ❤️ using PHP, Tailwind, Cursor & OpenAI.
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
            <button onclick="window.close()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
            <button id="quickAddClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
                <p class="text-gray-600 mb-6">Are you sure you want to delete "<span id="deleteBookmarkTitle" class="font-medium text-gray-900"></span>"?</p>
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
            <button id="deleteClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                    <button type="button" id="categoryEditDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                    <button type="button" id="categoryEditCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>
            <button id="categoryEditClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
            <button id="categoryAddClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
            <button id="pageAddClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
            <button id="pageEditClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
            <button id="editClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
    </div>

</body>
</html>