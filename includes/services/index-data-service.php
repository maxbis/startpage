<?php
/**
 * Index Data Service
 * Handles all database operations and data processing for the main index page
 */

class IndexDataService {
    private $pdo;
    private $currentUserId;
    private $currentPageId;
    
    // Category width configuration - easily changeable in one place
    private $CATEGORY_WIDTHS = [
        1 => 200,  // Very Small
        2 => 240,  // Small  
        3 => 274,  // Normal (default)
        4 => 300   // Large
    ];
    
    public function __construct($pdo, $currentUserId) {
        $this->pdo = $pdo;
        $this->currentUserId = $currentUserId;
    }
    
    /**
     * Get or create default page for user
     */
    public function getCurrentPageId() {
        // Get user's pages to determine default page
        $stmt = $this->pdo->prepare('SELECT id FROM pages WHERE user_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
        $stmt->execute([$this->currentUserId]);
        $userPage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Handle page selection via cookie
        $currentPageId = $userPage ? $userPage['id'] : null; // Use user's first page as default
        
        // Check if page cookie exists and belongs to current user
        if (isset($_COOKIE['startpage_current_page_id'])) {
            $cookiePageId = (int)$_COOKIE['startpage_current_page_id'];
            
            // Verify the page belongs to the current user
            $stmt = $this->pdo->prepare('SELECT id FROM pages WHERE id = ? AND user_id = ?');
            $stmt->execute([$cookiePageId, $this->currentUserId]);
            if ($stmt->fetch()) {
                $currentPageId = $cookiePageId;
            }
        }
        
        // If user has no pages, create a default page
        if (!$currentPageId) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO pages (user_id, name, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$this->currentUserId, 'My Startpage', 0]);
                $currentPageId = $this->pdo->lastInsertId();
                
                // Create some default categories
                $defaultCategories = [
                    ['Work', 0],
                    ['Personal', 1],
                    ['Tools', 2]
                ];
                
                foreach ($defaultCategories as $category) {
                    $stmt = $this->pdo->prepare("INSERT INTO categories (user_id, name, page_id, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$this->currentUserId, $category[0], $currentPageId, $category[1]]);
                }
            } catch (Exception $e) {
                // Handle error - could log this
                $currentPageId = 1; // Fallback to admin page if creation fails
            }
        }
        
        // Set default page cookie if it doesn't exist or is invalid
        if (!isset($_COOKIE['startpage_current_page_id']) || $_COOKIE['startpage_current_page_id'] != $currentPageId) {
            setcookie('startpage_current_page_id', $currentPageId, time() + (86400 * 365), '/'); // 1 year expiry
        }
        
        $this->currentPageId = $currentPageId;
        return $currentPageId;
    }
    
    /**
     * Get bookmarklet data and validate URL
     */
    public function getBookmarkletData() {
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
        
        return [
            'isAddingBookmark' => $isAddingBookmark,
            'prefillUrl' => $prefillUrl,
            'prefillTitle' => $prefillTitle,
            'prefillDesc' => $prefillDesc,
            'isValidUrl' => $isValidUrl,
            'urlError' => $urlError
        ];
    }
    
    /**
     * Get all categories and bookmarks for current page
     */
    public function getCategoriesAndBookmarks() {
        // Get all data in one optimized query
        $stmt = $this->pdo->prepare('
            SELECT 
                c.id as category_id,
                c.name as category_name,
                c.page_id,
                c.sort_order as category_sort,
                c.preferences,
                p.name as page_name,
                p.sort_order as page_sort,
                b.id as bookmark_id,
                b.title as bookmark_title,
                b.url as bookmark_url,
                b.description as bookmark_description,
                b.favicon_url,
                b.sort_order as bookmark_sort,
                b.color as bookmark_color
            FROM categories c 
            JOIN pages p ON c.page_id = p.id AND p.user_id = ?
            LEFT JOIN bookmarks b ON c.id = b.category_id AND b.user_id = ?
            WHERE c.page_id = ? AND c.user_id = ?
            ORDER BY c.sort_order ASC, c.id ASC, b.sort_order ASC, b.id ASC
        ');
        $stmt->execute([$this->currentUserId, $this->currentUserId, $this->currentPageId, $this->currentUserId]);
        $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process the data
        $categories = [];
        $bookmarksByCategory = [];
        
        foreach ($allData as $row) {
            $categoryId = $row['category_id'];
            
            // Add category if not already added
            if (!isset($categories[$categoryId])) {
                // Parse preferences JSON
                $preferences = json_decode($row['preferences'] ?? '{"cat_width": 3, "no_descr": 0, "show_fav": 1}', true);
                $catWidth = $preferences['cat_width'] ?? 3;
                $noUrlDescription = $preferences['no_descr'] ?? 0;
                $showFavicon = $preferences['show_fav'] ?? 1;
                
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'page_id' => $row['page_id'],
                    'sort_order' => $row['category_sort'],
                    'preferences' => $preferences,
                    'width' => $this->CATEGORY_WIDTHS[$catWidth] ?? $this->CATEGORY_WIDTHS[3],
                    'no_url_description' => $noUrlDescription,
                    'show_favicon' => $showFavicon
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
                    'sort_order' => $row['bookmark_sort'],
                    'color' => $row['bookmark_color']
                ];
            }
        }
        
        // Convert categories array to indexed array for compatibility
        $categories = array_values($categories);
        
        return [
            'categories' => $categories,
            'bookmarksByCategory' => $bookmarksByCategory
        ];
    }
    
    /**
     * Get current page name
     */
    public function getCurrentPageName() {
        $stmt = $this->pdo->prepare('SELECT name FROM pages WHERE id = ? AND user_id = ?');
        $stmt->execute([$this->currentPageId, $this->currentUserId]);
        $pageResult = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pageResult ? $pageResult['name'] : 'My Start Page';
    }
    
    /**
     * Get all available pages for dropdown
     */
    public function getAllPages() {
        $stmt = $this->pdo->prepare('SELECT id, name FROM pages WHERE user_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$this->currentUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all categories grouped by page for dropdowns
     */
    public function getCategoriesByPage() {
        $stmt = $this->pdo->prepare('
            SELECT c.id, c.name, c.page_id, p.name as page_name 
            FROM categories c 
            JOIN pages p ON c.page_id = p.id AND p.user_id = ?
            WHERE c.user_id = ?
            ORDER BY p.sort_order ASC, p.id ASC, c.sort_order ASC, c.id ASC
        ');
        $stmt->execute([$this->currentUserId, $this->currentUserId]);
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
        
        return $categoriesByPage;
    }
}
?>
