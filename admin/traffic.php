<?php
/**
 * Traffic analytics — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAuth();

$db = getDb();

// Stat cards
$totalViews  = (int) $db->query("SELECT COUNT(*) FROM page_views")->fetchColumn();
$viewsToday  = (int) $db->query("SELECT COUNT(*) FROM page_views WHERE visited_at >= " . strtotime('today'))->fetchColumn();
$viewsWeek   = (int) $db->query("SELECT COUNT(*) FROM page_views WHERE visited_at >= " . strtotime('-7 days'))->fetchColumn();
$uniquePages = (int) $db->query("SELECT COUNT(DISTINCT path) FROM page_views")->fetchColumn();

// Top pages — last 30 days
$since30 = strtotime('-30 days');
$total30  = (int) $db->query("SELECT COUNT(*) FROM page_views WHERE visited_at >= {$since30}")->fetchColumn();

$topPages = $db->prepare(
    "SELECT path, COUNT(*) AS views
     FROM page_views
     WHERE visited_at >= ?
     GROUP BY path
     ORDER BY views DESC
     LIMIT 20"
);
$topPages->execute([$since30]);
$topPages = $topPages->fetchAll();

// Recent visits — last 50
$recentVisits = $db->query(
    "SELECT path, referrer, ip, visited_at
     FROM page_views
     ORDER BY visited_at DESC
     LIMIT 50"
)->fetchAll();

adminHead('Traffic');
adminSidebar('traffic');
adminMain('Traffic', 'Page view analytics');
?>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem;">
      <form method="POST" action="/admin/traffic-ignore.php" style="margin:0;">
        <button type="submit" class="btn btn-ghost btn-sm">Ignore My IP</button>
      </form>
    </div>
  </div><!-- /page-header -->

<?= flashHtml() ?>

<!-- Stat cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Views</div>
    <div class="stat-num"><?= $totalViews ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Views Today</div>
    <div class="stat-num highlight"><?= $viewsToday ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Views This Week</div>
    <div class="stat-num"><?= $viewsWeek ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Unique Pages</div>
    <div class="stat-num"><?= $uniquePages ?></div>
  </div>
</div>

<!-- Top Pages -->
<div class="card">
  <div class="card-header">
    <h2>Top Pages — Last 30 Days</h2>
  </div>
  <div class="card-body">
    <?php if (empty($topPages)): ?>
      <div class="empty-state">
        <p>No page views recorded yet.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Page</th>
              <th>Views</th>
              <th>% of Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topPages as $row): ?>
              <?php $pct = $total30 > 0 ? round(($row['views'] / $total30) * 100, 1) : 0; ?>
              <tr>
                <td><?= h($row['path']) ?></td>
                <td><?= (int)$row['views'] ?></td>
                <td class="text-muted text-small"><?= $pct ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Visits -->
<div class="card">
  <div class="card-header">
    <h2>Recent Visits — Last 50</h2>
  </div>
  <div class="card-body">
    <?php if (empty($recentVisits)): ?>
      <div class="empty-state">
        <p>No visits recorded yet.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Page</th>
              <th>Referrer</th>
              <th>IP</th>
              <th>Date / Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentVisits as $v): ?>
              <?php
              // Mask IP: show first 2 octets + ***
              $ipParts  = explode('.', (string)$v['ip']);
              $maskedIp = count($ipParts) >= 2
                  ? h($ipParts[0]) . '.' . h($ipParts[1]) . '.***'
                  : '***';
              ?>
              <tr>
                <td><?= h($v['path']) ?></td>
                <td class="text-muted text-small"><?= $v['referrer'] !== '' ? h($v['referrer']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-muted text-small"><?= $maskedIp ?></td>
                <td class="text-muted text-small"><?= formatDate((int)$v['visited_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php adminFooter(); ?>
