<?php
/**
 * Blog posts list — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('blog');

$db           = getDb();
$statusFilter = $_GET['status'] ?? 'all';

$counts = [
    'all'       => (int) $db->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn(),
    'published' => (int) $db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn(),
    'draft'     => (int) $db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn(),
];

$where  = $statusFilter !== 'all' ? "WHERE status = " . $db->quote($statusFilter) : '';
$posts  = $db->query("SELECT id, title, slug, category, status, author, published_at, created_at FROM blog_posts $where ORDER BY created_at DESC")->fetchAll();

adminHead('Blog Posts');
adminSidebar('blog');
adminMain('Blog Posts', 'Manage your website articles');
?>
  <div style="margin-top:0.5rem;">
    <a href="/admin/blog-edit.php" class="btn btn-primary btn-sm">+ New Post</a>
  </div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<!-- Filter tabs -->
<div class="filter-tabs">
  <?php foreach ($counts as $s => $cnt): ?>
    <?php
    $href = '/admin/blog.php' . ($s !== 'all' ? '?status=' . urlencode($s) : '');
    $isActive = $statusFilter === $s || ($s === 'all' && $statusFilter === 'all');
    ?>
    <a href="<?= h($href) ?>" class="filter-tab<?= $isActive ? ' active' : '' ?>">
      <?= h(ucfirst($s)) ?>
      <span class="tab-count"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-body">
    <?php if (empty($posts)): ?>
      <div class="empty-state">
        <p>No posts found. <a href="/admin/blog-edit.php">Create your first post</a>.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Status</th>
              <th>Author</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($posts as $p): ?>
              <tr>
                <td>
                  <a href="/admin/blog-edit.php?id=<?= (int)$p['id'] ?>" style="color:var(--text);text-decoration:none;font-weight:500;"><?= h($p['title']) ?></a>
                  <br><span class="text-muted text-small">/blog-post.php?slug=<?= h($p['slug']) ?></span>
                </td>
                <td class="text-small text-muted"><?= h($p['category']) ?></td>
                <td><span class="badge badge-<?= h($p['status']) ?>"><?= h($p['status']) ?></span></td>
                <td class="text-small text-muted"><?= h($p['author']) ?></td>
                <td class="text-muted text-small">
                  <?php
                  $ts = $p['published_at'] ?? $p['created_at'];
                  echo formatDate((int)$ts);
                  ?>
                </td>
                <td>
                  <div class="action-row">
                    <a href="/admin/blog-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-xs">Edit</a>

                    <form method="POST" action="/admin/blog-toggle.php" style="display:inline;">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="btn btn-xs <?= $p['status'] === 'published' ? 'btn-warning' : 'btn-success' ?>">
                        <?= $p['status'] === 'published' ? 'Unpublish' : 'Publish' ?>
                      </button>
                    </form>

                    <form method="POST" action="/admin/blog-delete.php" style="display:inline;">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <button
                        type="submit"
                        class="btn btn-danger btn-xs"
                        data-confirm="Delete &quot;<?= h(addslashes($p['title'])) ?>&quot;? This cannot be undone."
                      >Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php adminFooter(); ?>
