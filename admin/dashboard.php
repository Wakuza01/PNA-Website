<?php
/**
 * Dashboard — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAuth();

$db = getDb();

// Stats
$newEnquiries     = (int) $db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
$totalEnquiries   = (int) $db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$publishedPosts   = (int) $db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
$draftPosts       = (int) $db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn();
$viewsToday       = (int) $db->query("SELECT COUNT(*) FROM page_views WHERE visited_at >= " . strtotime('today'))->fetchColumn();
$activeSubscribers = (int) $db->query("SELECT COUNT(*) FROM email_subscribers WHERE status = 'active'")->fetchColumn();

// Recent data
$recentEnquiries = $db->query(
    "SELECT id, name, company, service, status, submitted_at FROM enquiries ORDER BY submitted_at DESC LIMIT 5"
)->fetchAll();

$recentPosts = $db->query(
    "SELECT id, title, category, status, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

adminHead('Dashboard');
adminSidebar('dashboard');
adminMain('Dashboard', 'Welcome back, ' . currentUser());
?>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem;"></div>
  </div><!-- /page-header -->

<?= flashHtml() ?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">New Enquiries</div>
    <div class="stat-num highlight"><?= $newEnquiries ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Enquiries</div>
    <div class="stat-num"><?= $totalEnquiries ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Published Posts</div>
    <div class="stat-num"><?= $publishedPosts ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Draft Posts</div>
    <div class="stat-num"><?= $draftPosts ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Page Views Today</div>
    <div class="stat-num"><?= $viewsToday ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Email Subscribers</div>
    <div class="stat-num"><?= $activeSubscribers ?></div>
  </div>
</div>

<!-- Recent Enquiries -->
<div class="card">
  <div class="card-header">
    <h2>Recent Enquiries</h2>
    <a href="/admin/enquiries.php" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="card-body">
    <?php if (empty($recentEnquiries)): ?>
      <div class="empty-state">
        <p>No enquiries yet.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Company</th>
              <th>Service</th>
              <th>Date</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentEnquiries as $e): ?>
              <tr>
                <td><?= h($e['name']) ?></td>
                <td><?= $e['company'] ? h($e['company']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $e['service'] ? h($e['service']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-muted text-small"><?= formatDate((int)$e['submitted_at']) ?></td>
                <td><span class="badge badge-<?= h($e['status']) ?>"><?= h($e['status']) ?></span></td>
                <td><a href="/admin/enquiry-view.php?id=<?= (int)$e['id'] ?>" class="btn btn-ghost btn-xs">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Blog Posts -->
<div class="card">
  <div class="card-header">
    <h2>Recent Blog Posts</h2>
    <div style="display:flex;gap:0.5rem;">
      <a href="/admin/blog-edit.php" class="btn btn-primary btn-sm">New Post</a>
      <a href="/admin/blog.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
  </div>
  <div class="card-body">
    <?php if (empty($recentPosts)): ?>
      <div class="empty-state">
        <p>No blog posts yet. <a href="/admin/blog-edit.php">Create your first post</a>.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentPosts as $p): ?>
              <tr>
                <td><?= h($p['title']) ?></td>
                <td class="text-muted text-small"><?= h($p['category']) ?></td>
                <td><span class="badge badge-<?= h($p['status']) ?>"><?= h($p['status']) ?></span></td>
                <td class="text-muted text-small"><?= formatDate((int)$p['created_at']) ?></td>
                <td><a href="/admin/blog-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-xs">Edit</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php adminFooter(); ?>
