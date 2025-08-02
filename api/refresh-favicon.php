<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon-discoverer.php';

// Require authentication
if (!isAuthenticated($pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['url'])) {
        throw new Exception('URL is required');
    }
    
    $url = trim($input['url']);
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }
    
    // Extract domain for favicon discovery
    $domain = parse_url($url, PHP_URL_HOST);
    
    // Use FaviconDiscoverer to find the best favicon
    $discoverer = new FaviconDiscoverer(32, 'StartPage Favicon Refresh');
    $faviconUrl = $discoverer->getFaviconUrl($url);
    
    if ($faviconUrl) {
        // Download and cache the favicon
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; StartPage/1.0)'
            ]
        ]);
        
        $faviconData = @file_get_contents($faviconUrl, false, $context);
        
        if ($faviconData !== false && strlen($faviconData) > 0) {
            // Save to cache
            $cacheDir = '../cache/favicons/';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            $filename = preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain) . '.ico';
            $cachePath = $cacheDir . $filename;
            file_put_contents($cachePath, $faviconData);
            
            $cachedUrl = 'cache/favicons/' . $filename;
            
            echo json_encode([
                'success' => true,
                'favicon_url' => $cachedUrl,
                'original_url' => $faviconUrl
            ]);
        } else {
            throw new Exception('Failed to download favicon');
        }
    } else {
        throw new Exception('No favicon found for this URL');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 