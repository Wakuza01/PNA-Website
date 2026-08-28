<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Serve existing static files directly (js, css, images, etc.) — no cache headers
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
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
