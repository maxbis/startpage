<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-cache.php';

// Require authentication
if (!isAuthenticated($pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
        throw new Exception('URL is required');
    }

    $url = trim($input['url']);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }

    $domain = parse_url($url, PHP_URL_HOST);
    error_log("Refresh favicon - URL: $url, Domain: $domain");

    if (!$domain) {
        throw new Exception('Could not extract domain from URL: ' . $url);
    }

    $faviconCache = new FaviconCache('../cache/favicons/', 86400 * 30, true);
    $result = $faviconCache->resolveForUrl($url, true);

    error_log('Refresh favicon - Resolved icon: ' . json_encode($result));

    echo json_encode([
        'success' => true,
        'favicon_url' => $result['favicon_url'],
        'original_url' => $result['source_url'] ?: 'generated',
        'source' => $result['source'],
        'cached' => $result['cached'],
        'normalized_url' => $result['normalized_url'],
        'final_url' => $result['final_url'],
        'failure_reason' => $result['failure_reason'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
