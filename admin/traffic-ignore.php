<?php
/**
 * Add current IP to ignored list — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAuth();

$db = getDb();
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

try {
    $stmt = $db->prepare("INSERT OR IGNORE INTO traffic_ignored_ips (ip) VALUES (?)");
    $stmt->execute([$ip]);
    setFlash('success', 'Your IP (' . $ip . ') is now ignored in traffic tracking.');
} catch (Exception $e) {
    error_log('traffic-ignore.php error: ' . $e->getMessage());
    setFlash('error', 'Failed to ignore IP. Please try again.');
}

header('Location: /admin/traffic.php');
exit;
