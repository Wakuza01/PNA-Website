<?php
/**
 * Enquiries list — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('enquiries');

$db = getDb();

$statusFilter = $_GET['status'] ?? 'all';
$q            = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

// Count per status for tabs
$counts = ['all' => 0, 'new' => 0, 'read' => 0, 'replied' => 0, 'archived' => 0];
$counts['all'] = (int) $db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
foreach (['new','read','replied','archived'] as $s) {
    $counts[$s] = (int) $db->query("SELECT COUNT(*) FROM enquiries WHERE status = '$s'")->fetchColumn();
}

// Build query
$conditions = [];
$params     = [];

if ($statusFilter !== 'all') {
    $conditions[] = "status = ?";
    $params[]     = $statusFilter;
}

if ($q !== '') {
    $conditions[] = "(name LIKE ? OR company LIKE ? OR email LIKE ? OR service LIKE ? OR message LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$totalStmt = $db->prepare("SELECT COUNT(*) FROM enquiries $where");
$totalStmt->execute($params);
$total     = (int) $totalStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare("SELECT id, name, company, email, service, status, submitted_at FROM enquiries $where ORDER BY submitted_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$enquiries = $stmt->fetchAll();

// Build pagination URL helper
function pageUrl(int $p): string {
    $args = $_GET;
    $args['page'] = $p;
    return '/admin/enquiries.php?' . http_build_query($args);
}

adminHead('Enquiries');
adminSidebar('enquiries');
adminMain('Enquiries', 'All contact form submissions');
?>
  </div><!-- /page-header -->

<?= flashHtml() ?>

<!-- Filter tabs -->
<div class="filter-tabs">
  <?php foreach ($counts as $s => $cnt): ?>
    <?php
    $args = $_GET;
    unset($args['page']);
    $args['status'] = $s;
    if ($s === 'all') unset($args['status']);
    $href = '/admin/enquiries.php' . ($args ? '?' . http_build_query($args) : '');
    $isActive = ($statusFilter === $s) || ($s === 'all' && $statusFilter === 'all');
    ?>
    <a href="<?= h($href) ?>" class="filter-tab<?= $isActive ? ' active' : '' ?>">
      <?= h(ucfirst($s)) ?>
      <span class="tab-count"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Search -->
<form method="GET" action="/admin/enquiries.php" class="search-row">
  <?php if ($statusFilter !== 'all'): ?>
    <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
  <?php endif; ?>
  <input type="text" name="q" placeholder="Search name, company, email, service…" value="<?= h($q) ?>">
  <button type="submit" class="btn btn-ghost">Search</button>
  <?php if ($q !== ''): ?>
    <a href="/admin/enquiries.php<?= $statusFilter !== 'all' ? '?status=' . h($statusFilter) : '' ?>" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<div class="card">
  <div class="card-body">
    <?php if (empty($enquiries)): ?>
      <div class="empty-state">
        <p><?= $q ? 'No enquiries match your search.' : 'No enquiries found.' ?></p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Company</th>
              <th>Email</th>
              <th>Service</th>
              <th>Date</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enquiries as $e): ?>
              <tr>
                <td class="text-muted text-small"><?= (int)$e['id'] ?></td>
                <td><?= h($e['name']) ?></td>
                <td><?= $e['company'] ? h($e['company']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-small"><?= h($e['email']) ?></td>
                <td class="text-small"><?= $e['service'] ? h($e['service']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-muted text-small"><?= formatDate((int)$e['submitted_at']) ?></td>
                <td><span class="badge badge-<?= h($e['status']) ?>"><?= h($e['status']) ?></span></td>
                <td><a href="/admin/enquiry-view.php?id=<?= (int)$e['id'] ?>" class="btn btn-ghost btn-xs">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="<?= h(pageUrl($page - 1)) ?>">&laquo;</a>
          <?php else: ?>
            <span class="disabled">&laquo;</span>
          <?php endif; ?>

          <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <?php if ($i === $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= h(pageUrl($i)) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="<?= h(pageUrl($page + 1)) ?>">&raquo;</a>
          <?php else: ?>
            <span class="disabled">&raquo;</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<p class="text-muted text-small"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?><?= $q ? ' for "' . h($q) . '"' : '' ?></p>

<?php adminFooter(); ?>
