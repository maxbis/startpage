<?php
// Router for PHP's built-in server. Nginx uses try_files instead.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = realpath(__DIR__ . '/public' . $path);
$publicRoot = realpath(__DIR__ . '/public');

if ($file !== false && str_starts_with($file, $publicRoot . DIRECTORY_SEPARATOR)
    && (is_file($file) || (is_dir($file) && is_file($file . '/index.php')))) {
    return false;
}

// PHP's server leaves SCRIPT_NAME set to the missing requested file. Match
// the value supplied by Nginx/Apache when they rewrite to public/index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php';
