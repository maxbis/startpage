<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
requireAuth($pdo);
require_once '../includes/favicon/favicon-cache.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['url']) || !isset($input['category_id'])) {
        throw new Exception('URL and category_id are required');
    }
    
    $url = trim($input['url']);
    $categoryId = (int)$input['category_id'];
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }
    
    // Get the highest sort_order for this category
    $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM bookmarks WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    $nextOrder = ($result['max_order'] ?? -1) + 1;
    
    // Extract domain for favicon and cache it
    $domain = parse_url($url, PHP_URL_HOST);
    # $faviconCache = new FaviconCache();
    $faviconCache = new FaviconCache('../cache/favicons/', 86400 * 30, true);
    $faviconUrl = $faviconCache->getFaviconUrl($domain);
    
    // Use provided title/description or fetch from page
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    
    // If title/description not provided, try to fetch from the page
    if (empty($title) || empty($description)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Mozilla/5.0 (compatible; StartPage/1.0)'
            ]
        ]);
        
        $html = @file_get_contents($url, false, $context);
        if ($html !== false) {
            // Extract title if not provided
            if (empty($title)) {
                if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
                    $title = trim($matches[1]);
                } else {
                    $title = $domain; // Default to domain name
                }
            }
            
            // Extract meta description if not provided
            if (empty($description)) {
                if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $matches)) {
                    $description = trim($matches[1]);
                }
            }
        } else {
            // If we can't fetch the page, use domain as title
            if (empty($title)) {
                $title = $domain;
            }
        }
    }
    
    // Insert the bookmark
    $currentUserId = getCurrentUserId();
    $stmt = $pdo->prepare("
        INSERT INTO bookmarks (user_id, title, url, description, favicon_url, category_id, sort_order, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([$currentUserId, $title, $url, $description, $faviconUrl, $categoryId, $nextOrder]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 