<?php
/**
 * Unsubscribe a subscriber — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('emails');

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Invalid subscriber ID.');
    header('Location: /admin/emails.php');
    exit;
}

$db = getDb();

try {
    $stmt = $db->prepare(
        "UPDATE email_subscribers
         SET status = 'unsubscribed', unsubscribed_at = strftime('%s','now')
         WHERE id = ?"
    );
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        setFlash('error', 'Subscriber not found.');
    } else {
        setFlash('success', 'Subscriber has been unsubscribed.');
    }
} catch (Exception $e) {
    error_log('subscriber-unsubscribe.php error: ' . $e->getMessage());
    setFlash('error', 'Failed to unsubscribe. Please try again.');
}

header('Location: /admin/emails.php');
exit;
