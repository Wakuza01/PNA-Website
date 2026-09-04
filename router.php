<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Execute PHP files — never return false for .php or it gets downloaded as raw text
if ($uri !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}

// Route PHP files in subdirectories (e.g. /admin/login.php)
if ($uri !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
    exit;
}

// Prevent browser caching for HTML pages only
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Try appending .html
if ($uri !== '/' && file_exists($file . '.html')) {
    include $file . '.html';
    exit;
}

// Serve index.html for root
include __DIR__ . '/index.html';
