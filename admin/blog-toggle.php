<?php
/**
 * Blog post status toggle — Pinion & Adams Admin
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
    $stmt = $db->prepare("SELECT status, published_at FROM blog_posts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        setFlash('error', 'Post not found.');
        header('Location: /admin/blog.php');
        exit;
    }

    $newStatus   = $post['status'] === 'published' ? 'draft' : 'published';
    $publishedAt = $post['published_at'];
    $now         = time();

    if ($newStatus === 'published' && !$publishedAt) {
        $publishedAt = $now;
    }

    $db->prepare("UPDATE blog_posts SET status = ?, published_at = ?, updated_at = ? WHERE id = ?")
       ->execute([$newStatus, $publishedAt, $now, $id]);

    $label = $newStatus === 'published' ? 'published' : 'moved to draft';
    setFlash('success', 'Post ' . $label . ' successfully.');

} catch (Exception $e) {
    error_log('Blog toggle error: ' . $e->getMessage());
    setFlash('error', 'Failed to update post status.');
}

header('Location: /admin/blog.php');
exit;
