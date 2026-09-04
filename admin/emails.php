<?php
/**
 * Email subscribers — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAuth();

$db = getDb();

// Stat counts
$totalSubs       = (int) $db->query("SELECT COUNT(*) FROM email_subscribers")->fetchColumn();
$activeSubs      = (int) $db->query("SELECT COUNT(*) FROM email_subscribers WHERE status = 'active'")->fetchColumn();
$unsubscribedSubs = (int) $db->query("SELECT COUNT(*) FROM email_subscribers WHERE status = 'unsubscribed'")->fetchColumn();

// All subscribers
$subscribers = $db->query(
    "SELECT id, email, name, source, status, subscribed_at
     FROM email_subscribers
     ORDER BY subscribed_at DESC"
)->fetchAll();

adminHead('Emails');
adminSidebar('emails');
adminMain('Emails', 'Newsletter subscriber management');
?>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem;"></div>
  </div><!-- /page-header -->

<?= flashHtml() ?>

<!-- Stat cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Subscribers</div>
    <div class="stat-num"><?= $totalSubs ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active</div>
    <div class="stat-num highlight"><?= $activeSubs ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Unsubscribed</div>
    <div class="stat-num"><?= $unsubscribedSubs ?></div>
  </div>
</div>

<!-- Subscribers table -->
<div class="card">
  <div class="card-header">
    <h2>All Subscribers</h2>
  </div>
  <div class="card-body">
    <?php if (empty($subscribers)): ?>
      <div class="empty-state">
        <p>No subscribers yet.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Email</th>
              <th>Name</th>
              <th>Source</th>
              <th>Status</th>
              <th>Subscribed</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscribers as $sub): ?>
              <tr>
                <td><?= h($sub['email']) ?></td>
                <td><?= $sub['name'] !== '' ? h($sub['name']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-muted text-small"><?= h($sub['source']) ?></td>
                <td>
                  <span class="badge badge-<?= $sub['status'] === 'active' ? 'replied' : 'archived' ?>">
                    <?= h($sub['status']) ?>
                  </span>
                </td>
                <td class="text-muted text-small"><?= formatDate((int)$sub['subscribed_at']) ?></td>
                <td>
                  <?php if ($sub['status'] === 'active'): ?>
                    <form method="POST" action="/admin/subscriber-unsubscribe.php" style="margin:0;">
                      <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-xs"
                              onclick="return confirm('Unsubscribe this address?')">
                        Unsubscribe
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted text-small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Compose & Send -->
<div class="card">
  <div class="card-header">
    <h2>Compose &amp; Send</h2>
  </div>
  <div class="card-body padded">
    <p class="text-muted text-small" style="margin-bottom:1.25rem;">
      This email will be sent to <strong style="color:var(--text);"><?= $activeSubs ?></strong>
      active subscriber<?= $activeSubs !== 1 ? 's' : '' ?>.
    </p>
    <form method="POST" action="/admin/email-send.php">
      <div class="form-group">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required placeholder="Email subject…">
      </div>
      <div class="form-group">
        <label for="body">Body</label>
        <textarea id="body" name="body" required placeholder="Email body text…"></textarea>
      </div>
      <button type="submit" class="btn btn-primary"
              onclick="return confirm('Send to <?= $activeSubs ?> subscriber<?= $activeSubs !== 1 ? 's' : '' ?>?')">
        Send to All Active
      </button>
    </form>
  </div>
</div>

<?php adminFooter(); ?>
