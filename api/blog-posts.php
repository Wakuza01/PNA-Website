<?php
/**
 * Public Blog Posts API — Pinion & Adams
 * Returns published posts as JSON.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../admin/includes/db.php';

try {
    $db    = getDb();
    $limit = min((int)($_GET['limit'] ?? 10), 50);

    $stmt = $db->prepare("
        SELECT id, title, slug, excerpt, image_url, category, author, published_at
        FROM blog_posts
        WHERE status = 'published'
        ORDER BY published_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $posts = $stmt->fetchAll();

    foreach ($posts as &$p) {
        $p['url']            = '/blog-post.php?slug=' . urlencode($p['slug']);
        $p['date_formatted'] = $p['published_at'] ? date('d M Y', (int)$p['published_at']) : '';
    }
    unset($p);

    echo json_encode(['success' => true, 'posts' => $posts], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
