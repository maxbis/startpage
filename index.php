<?php
// Redirect to the main application using an absolute path so bad relative asset
// requests cannot recurse into .../app/app/... redirect chains.
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $basePath === '/' || $basePath === '.' ? '' : rtrim($basePath, '/');

header('Location: ' . $basePath . '/app/');
exit; 
