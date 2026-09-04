<?php
/**
 * Page view tracker — Pinion & Adams Admin
 * Public endpoint: no auth required.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$path      = trim($_POST['path'] ?? '');
$referrer  = trim($_POST['referrer'] ?? '');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip        = $_SERVER['REMOTE_ADDR'] ?? '';

if ($path === '') {
    echo json_encode(['ok' => false, 'error' => 'path required']);
    exit;
}

try {
    $db = getDb();

    // Check if IP is ignored
    $stmt = $db->prepare("SELECT ip FROM traffic_ignored_ips WHERE ip = ? LIMIT 1");
    $stmt->execute([$ip]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => true, 'ignored' => true]);
        exit;
    }

    // Record the page view
    $stmt = $db->prepare(
        "INSERT INTO page_views (path, referrer, user_agent, ip) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$path, $referrer, $userAgent, $ip]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    error_log('track.php error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'db error']);
}
