<?php
require_once 'includes/db.php';

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

// Load categories
$stmt = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            max-height: 300px;
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
    </style>

</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-300 text-gray-800 min-h-screen font-sans">

    <header class="bg-white shadow sticky top-0 z-10">
        <div class="max-w-8xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-blue-600">🌐 My Start Page</h1>
            <div class="flex gap-3">
                <a href="bookmarklet.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition">
                    📌 Get Bookmarklet
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-8xl mx-auto px-4 py-8">
        <div id="categories-container" class="flex flex-wrap gap-4">
            <?php foreach ($categories as $cat): ?>
                <?php $bookmarkCount = count($bookmarksByCategory[$cat['id']]); ?>
                <section style="max-width:280px;" class="bg-white rounded-2xl shadow-lg p-3 relative border border-gray-200 cursor-move w-full" data-category-id="<?= $cat['id'] ?>">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 cursor-move">⋮⋮</span>
                            <h2 class="text-xl font-semibold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors" data-action="edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </h2>
                        </div>
                        <!-- <button class="text-sm text-gray-400 hover:text-blue-600" data-action="edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">✏️</button> -->
                    </div>

                    <div class="section-content">
                        <ul class="space-y-1" data-category-id="<?= $cat['id'] ?>" class="bookmark-list">
                            <?php foreach ($bookmarksByCategory[$cat['id']] as $bm): ?>
                                                            <li class="bg-gray-50 hover:bg-gray-100 transition p-2 rounded-lg shadow-sm flex items-start gap-3 draggable" 
                                data-id="<?= $bm['id'] ?>" 
                                data-title="<?= htmlspecialchars($bm['title']) ?>" 
                                data-url="<?= htmlspecialchars($bm['url']) ?>" 
                                data-description="<?= htmlspecialchars($bm['description'] ?? '') ?>">
                                <img src="<?= htmlspecialchars($bm['favicon_url']) ?>" alt="favicon" class="w-6 h-6 rounded flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline block bookmark-title" title="<?= htmlspecialchars($bm['title']) ?>">
                                        <?= htmlspecialchars($bm['title']) ?>
                                    </a>
                                    <?php if (!empty($bm['description'])): ?>
                                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($bm['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex gap-2 text-sm text-gray-500 flex-shrink-0">
                                    <button data-action="edit" data-id="<?= $bm['id'] ?>" class="hover:text-blue-600">✏️</button>
                                    <!-- <button data-action="delete" data-id="<?= $bm['id'] ?>" class="hover:text-red-600">🗑</button> -->
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if ($bookmarkCount > 4): ?>
                        <div class="expand-indicator" data-section-id="<?= $cat['id'] ?>">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- <form data-category="<?= $cat['id'] ?>" class="add-bookmark-form flex gap-2 mt-4">
                        <input type="url" name="url" placeholder="https://example.com" class="min-w-0 flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition">Add</button>
                    </form> -->
                </section>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="text-center text-gray-400 text-sm mt-12 pb-6">
        Made with ❤️ using PHP, Tailwind, Cursur & OpenAI.
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
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
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
            <span class="text-lg">+</span>
            <span>Add Category</span>
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