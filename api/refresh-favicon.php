<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-discoverer.php';

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
    
    // Debug: Log the domain and URL
    error_log("Refresh favicon - URL: $url, Domain: $domain");
    
    // Check if domain is valid
    if (!$domain) {
        throw new Exception('Could not extract domain from URL: ' . $url);
    }
    
    // Note: Removed special localhost handling to allow discovery of local favicons
    // Localhost URLs will now be processed normally through the favicon discoverer
    
    // Use FaviconDiscoverer to find the best favicon
    $discoverer = new FaviconDiscoverer(32, 'StartPage Favicon Refresh');
    $faviconUrl = $discoverer->getFaviconUrl($url);
    
    // Debug: Log the discovered favicon URL
    error_log("Refresh favicon - Discovered URL: $faviconUrl");
    
    // Check if it's a Google favicon service URL (these often fail)
    if ($faviconUrl && strpos($faviconUrl, 'google.com/s2/favicons') !== false) {
        error_log("Refresh favicon - Google favicon service URL detected, likely to fail");
    }
    
    if ($faviconUrl) {
        // Check if it's a data URI (default favicon)
        if (strpos($faviconUrl, 'data:image/svg+xml;base64,') === 0) {
            error_log("Refresh favicon - Default SVG favicon detected");
            echo json_encode([
                'success' => true,
                'favicon_url' => $faviconUrl,
                'original_url' => 'default',
                'cached' => false
            ]);
            return;
        }
        
        // Download and cache the favicon
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; StartPage/1.0)'
            ]
        ]);
        
        $faviconData = @file_get_contents($faviconUrl, false, $context);
        
        // Debug: Log the download attempt
        error_log("Refresh favicon - Download attempt: $faviconUrl, result: " . ($faviconData !== false ? "success, size: " . strlen($faviconData) : "failed"));
        
        if ($faviconData !== false && strlen($faviconData) > 0) {
            // Debug: Log the download success
            error_log("Refresh favicon - Download successful, size: " . strlen($faviconData));
            
            // Save to cache
            $cacheDir = '../cache/favicons/';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            // Determine the correct file extension based on the original favicon URL
            $pathInfo = pathinfo(parse_url($faviconUrl, PHP_URL_PATH));
            $extension = 'ico'; // default
            if (isset($pathInfo['extension'])) {
                $ext = strtolower($pathInfo['extension']);
                // Only allow safe image extensions
                if (in_array($ext, ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg'])) {
                    $extension = $ext;
                }
            }
            
            $filename = preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain) . '.' . $extension;
            $cachePath = $cacheDir . $filename;
            $saved = file_put_contents($cachePath, $faviconData);
            
            // Debug: Log the save attempt
            error_log("Refresh favicon - Save attempt: $cachePath, result: " . ($saved !== false ? "success" : "failed"));
            
            if ($saved !== false) {
                $cachedUrl = 'cache/favicons/' . $filename;
                
                // Debug: Log the response
                error_log("Refresh favicon - Returning cached URL: $cachedUrl");
                
                echo json_encode([
                    'success' => true,
                    'favicon_url' => $cachedUrl,
                    'original_url' => $faviconUrl
                ]);
            } else {
                throw new Exception('Failed to save favicon to cache');
            }
        } else {
            error_log("Refresh favicon - Download failed for URL: $faviconUrl");
            // Fallback: return the original URL if download fails
            echo json_encode([
                'success' => true,
                'favicon_url' => $faviconUrl,
                'original_url' => $faviconUrl,
                'cached' => false
            ]);
            return;
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