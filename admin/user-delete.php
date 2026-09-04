<?php
/**
 * User delete action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requirePermission('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

$id    = (int)($_POST['id'] ?? 0);
$myUid = (int)($_SESSION['pa_admin_uid'] ?? 0);

if ($id < 1) {
    setFlash('error', 'Invalid user ID.');
    header('Location: /admin/users.php');
    exit;
}

if ($id === $myUid) {
    setFlash('error', 'You cannot delete your own account.');
    header('Location: /admin/users.php');
    exit;
}

try {
    $db = getDb();

    // Prevent deletion if only 1 user remains
    $total = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($total <= 1) {
        setFlash('error', 'Cannot delete the only remaining user.');
        header('Location: /admin/users.php');
        exit;
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setFlash('success', 'User deleted successfully.');
    } else {
        setFlash('error', 'User not found.');
    }

} catch (\Exception $e) {
    error_log('User delete error: ' . $e->getMessage());
    setFlash('error', 'Failed to delete user. Please try again.');
}

header('Location: /admin/users.php');
exit;
