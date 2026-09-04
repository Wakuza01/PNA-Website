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

// Daily views — last 30 days for bar chart
$dailyRows = $db->prepare(
    "SELECT date(visited_at, 'unixepoch') AS day, COUNT(*) AS views
     FROM page_views
     WHERE visited_at >= ?
     GROUP BY day
     ORDER BY day ASC"
);
$dailyRows->execute([$since30]);
$dailyData = $dailyRows->fetchAll();

// Build a full 30-day array (fill gaps with 0)
$chartLabels = [];
$chartValues = [];
$dailyMap = [];
foreach ($dailyData as $row) {
    $dailyMap[$row['day']] = (int)$row['views'];
}
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('d M', strtotime($day));
    $chartValues[] = $dailyMap[$day] ?? 0;
}
$chartLabelsJson = json_encode($chartLabels);
$chartValuesJson = json_encode($chartValues);

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

<!-- Daily Views Chart -->
<div class="card">
  <div class="card-header">
    <h2>Daily Views — Last 30 Days</h2>
  </div>
  <div class="card-body" style="padding:1.25rem 1.5rem;">
    <canvas id="trafficChart" height="90"></canvas>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
  var labels = <?= $chartLabelsJson ?>;
  var values = <?= $chartValuesJson ?>;
  var ctx = document.getElementById('trafficChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Page Views',
        data: values,
        backgroundColor: 'rgba(38, 157, 204, 0.55)',
        borderColor:     'rgba(38, 157, 204, 0.9)',
        borderWidth: 1,
        borderRadius: 3,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1c2333',
          borderColor:     'rgba(255,255,255,0.08)',
          borderWidth: 1,
          titleColor: '#e6edf3',
          bodyColor:  '#7d8590',
          callbacks: {
            label: function(ctx) { return ' ' + ctx.parsed.y + ' views'; }
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#7d8590', font: { size: 11 }, maxTicksLimit: 10 },
          grid:  { color: 'rgba(255,255,255,0.04)' }
        },
        y: {
          beginAtZero: true,
          ticks: { color: '#7d8590', font: { size: 11 }, precision: 0 },
          grid:  { color: 'rgba(255,255,255,0.06)' }
        }
      }
    }
  });
})();
</script>
<?php adminFooter(); ?>
