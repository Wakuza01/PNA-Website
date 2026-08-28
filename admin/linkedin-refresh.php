<?php
/**
 * LinkedIn cache clear — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/settings.php');
    exit;
}

$cacheFile = dirname(__DIR__) . '/api/linkedin-cache.json';

if (file_exists($cacheFile)) {
    if (@unlink($cacheFile)) {
        setFlash('success', 'LinkedIn cache cleared. It will refresh on next page load.');
    } else {
        setFlash('error', 'Could not delete cache file. Check file permissions.');
    }
} else {
    setFlash('success', 'No cache file found — nothing to clear.');
}

header('Location: /admin/settings.php');
exit;
