<?php
/**
 * Blog post save action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/blog.php');
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$title   = trim($_POST['title']     ?? '');
$slug    = trim($_POST['slug']      ?? '');
$excerpt = trim($_POST['excerpt']   ?? '');
$content = trim($_POST['content']   ?? '');
$imageUrl= trim($_POST['image_url'] ?? '');
$category= trim($_POST['category']  ?? 'General');
$status  = trim($_POST['status']    ?? 'draft');
$author  = trim($_POST['author']    ?? 'P&A Admin');

$validStatuses   = ['draft', 'published'];
$validCategories = ['General', 'Industry News', 'Company Updates', 'Training', 'Safety', 'Technology'];

if ($title === '') {
    setFlash('error', 'Title is required.');
    $redir = $id > 0 ? "/admin/blog-edit.php?id=$id" : '/admin/blog-edit.php';
    header('Location: ' . $redir);
    exit;
}

if (!in_array($status, $validStatuses, true)) {
    $status = 'draft';
}
if (!in_array($category, $validCategories, true)) {
    $category = 'General';
}

// Generate slug if empty
if ($slug === '') {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = trim($slug);
    $slug = preg_replace('/[\s]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
}

if ($slug === '') {
    setFlash('error', 'Could not generate a valid slug from the title.');
    $redir = $id > 0 ? "/admin/blog-edit.php?id=$id" : '/admin/blog-edit.php';
    header('Location: ' . $redir);
    exit;
}

try {
    $db = getDb();

    // Check slug uniqueness
    $slugCheck = $db->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        setFlash('error', 'That slug is already in use. Please choose a different one.');
        $redir = $id > 0 ? "/admin/blog-edit.php?id=$id" : '/admin/blog-edit.php';
        header('Location: ' . $redir);
        exit;
    }

    $now = time();

    if ($id > 0) {
        // Update existing
        $existing = $db->prepare("SELECT status, published_at FROM blog_posts WHERE id = ? LIMIT 1");
        $existing->execute([$id]);
        $row = $existing->fetch();

        if (!$row) {
            setFlash('error', 'Post not found.');
            header('Location: /admin/blog.php');
            exit;
        }

        $publishedAt = $row['published_at'];
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = $now;
        }

        $stmt = $db->prepare("
            UPDATE blog_posts
            SET title=?, slug=?, excerpt=?, content=?, image_url=?, category=?, status=?, author=?, published_at=?, updated_at=?
            WHERE id=?
        ");
        $stmt->execute([$title, $slug, $excerpt, $content, $imageUrl, $category, $status, $author, $publishedAt, $now, $id]);
        setFlash('success', 'Post updated successfully.');
    } else {
        // Insert new
        $publishedAt = ($status === 'published') ? $now : null;

        $stmt = $db->prepare("
            INSERT INTO blog_posts (title, slug, excerpt, content, image_url, category, status, author, published_at, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$title, $slug, $excerpt, $content, $imageUrl, $category, $status, $author, $publishedAt, $now, $now]);
        $id = (int)$db->lastInsertId();
        setFlash('success', 'Post created successfully.');
    }

    header('Location: /admin/blog.php');
    exit;

} catch (Exception $e) {
    error_log('Blog save error: ' . $e->getMessage());
    setFlash('error', 'Failed to save post: ' . $e->getMessage());
    $redir = $id > 0 ? "/admin/blog-edit.php?id=$id" : '/admin/blog-edit.php';
    header('Location: ' . $redir);
    exit;
}
