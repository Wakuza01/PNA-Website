<?php
/**
 * Enquiry update action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/enquiries.php');
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes  = trim($_POST['notes'] ?? '');

$validStatuses = ['new', 'read', 'replied', 'archived'];

if ($id < 1 || !in_array($status, $validStatuses, true)) {
    setFlash('error', 'Invalid request.');
    header('Location: /admin/enquiries.php');
    exit;
}

try {
    $db   = getDb();
    $stmt = $db->prepare("UPDATE enquiries SET status = ?, notes = ? WHERE id = ?");
    $stmt->execute([$status, $notes, $id]);

    if ($stmt->rowCount() === 0) {
        setFlash('error', 'Enquiry not found.');
        header('Location: /admin/enquiries.php');
        exit;
    }

    setFlash('success', 'Enquiry updated successfully.');
} catch (Exception $e) {
    error_log('Enquiry update error: ' . $e->getMessage());
    setFlash('error', 'Failed to update enquiry.');
}

header('Location: /admin/enquiry-view.php?id=' . $id);
exit;
