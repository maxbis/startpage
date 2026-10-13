<?php
// Keep application code and the favicon cache outside the document root.
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$basePath = $basePath === '/' || $basePath === '.' ? '' : rtrim($basePath, '/');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = str_starts_with($requestPath, $basePath . '/')
    ? substr($requestPath, strlen($basePath))
    : $requestPath;

if ($path === '/' || $path === '/index.php') {
    header('Location: ' . $basePath . '/app/');
    exit;
}

if (preg_match('~^/cache/favicons/([a-zA-Z0-9._-]+\.(?:ico|png|jpe?g|gif|svg|webp))$~', $path, $matches)) {
    $cacheDir = dirname(__DIR__) . '/cache/favicons';
    $file = $cacheDir . '/' . $matches[1];
    $realFile = realpath($file);
    if ($realFile !== false && is_file($realFile) && dirname($realFile) === realpath($cacheDir)) {
        $types = [
            'ico' => 'image/x-icon', 'png' => 'image/png',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
        ];
        $extension = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        header('Content-Type: ' . $types[$extension]);
        header('X-Content-Type-Options: nosniff');
        if ($extension === 'svg') {
            header('Content-Security-Policy: sandbox');
        }
        header('Cache-Control: public, max-age=86400');
        readfile($realFile);
        exit;
    }
}

http_response_code(404);
echo 'Not found';
