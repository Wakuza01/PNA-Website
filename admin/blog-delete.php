<?php
/**
 * Blog post delete action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/blog.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id < 1) {
    setFlash('error', 'Invalid post ID.');
    header('Location: /admin/blog.php');
    exit;
}

try {
    $db   = getDb();
    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setFlash('success', 'Post deleted successfully.');
    } else {
        setFlash('error', 'Post not found.');
    }
} catch (Exception $e) {
    error_log('Blog delete error: ' . $e->getMessage());
    setFlash('error', 'Failed to delete post.');
}

header('Location: /admin/blog.php');
exit;
