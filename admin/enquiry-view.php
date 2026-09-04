<?php
/**
 * Enquiry view — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('enquiries');

$db = getDb();
$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    header('Location: /admin/enquiries.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM enquiries WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$e = $stmt->fetch();

if (!$e) {
    http_response_code(404);
    adminHead('Not Found');
    adminSidebar('enquiries');
    adminMain('Enquiry Not Found');
    echo '</div>';
    echo '<p class="text-muted">The requested enquiry could not be found. <a href="/admin/enquiries.php">Back to enquiries</a></p>';
    adminFooter();
    exit;
}

// Auto-mark as read
if ($e['status'] === 'new') {
    $db->prepare("UPDATE enquiries SET status = 'read' WHERE id = ?")->execute([$id]);
    $e['status'] = 'read';
}

$emailSubject = rawurlencode('Re: Enquiry from ' . $e['name']);
$emailBody    = rawurlencode("Dear " . $e['name'] . ",\n\nThank you for contacting Pinion & Adams Fabricators.\n\n");
$replyLink    = 'mailto:' . $e['email'] . '?subject=' . $emailSubject . '&body=' . $emailBody;

adminHead('Enquiry #' . $id);
adminSidebar('enquiries');
adminMain('Enquiry #' . $id, formatDate((int)$e['submitted_at']));
?>
  <div style="display:flex;gap:0.5rem;margin-top:0.5rem;">
    <a href="/admin/enquiries.php" class="btn btn-ghost btn-sm">&larr; Back</a>
    <a href="<?= h($replyLink) ?>" class="btn btn-primary btn-sm">Reply via Email</a>
  </div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

  <!-- Left: Details -->
  <div>
    <div class="card">
      <div class="card-header">
        <h2>Contact Details</h2>
        <span class="badge badge-<?= h($e['status']) ?>"><?= h($e['status']) ?></span>
      </div>
      <div class="enquiry-detail">
        <div class="detail-grid">
          <span class="detail-label">Name</span>
          <span class="detail-value"><?= h($e['name']) ?></span>

          <?php if ($e['company']): ?>
            <span class="detail-label">Company</span>
            <span class="detail-value"><?= h($e['company']) ?></span>
          <?php endif; ?>

          <span class="detail-label">Email</span>
          <span class="detail-value"><a href="mailto:<?= h($e['email']) ?>"><?= h($e['email']) ?></a></span>

          <?php if ($e['phone']): ?>
            <span class="detail-label">Phone</span>
            <span class="detail-value"><a href="tel:<?= h($e['phone']) ?>"><?= h($e['phone']) ?></a></span>
          <?php endif; ?>

          <?php if ($e['service']): ?>
            <span class="detail-label">Service</span>
            <span class="detail-value"><?= h($e['service']) ?></span>
          <?php endif; ?>

          <span class="detail-label">IP Address</span>
          <span class="detail-value text-muted text-small"><?= h($e['ip'] ?? '—') ?></span>

          <span class="detail-label">Submitted</span>
          <span class="detail-value text-small"><?= formatDate((int)$e['submitted_at']) ?></span>
        </div>

        <div class="form-section">
          <h3>Message</h3>
          <div class="message-box"><?= h($e['message']) ?></div>
        </div>

        <?php if ($e['attachment_name']): ?>
          <div class="form-section">
            <h3>Attachment</h3>
            <a href="/admin/uploads/enquiries/<?= h($e['attachment_path']) ?>" class="btn btn-ghost btn-sm" target="_blank">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 10v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-3M8 10V2M4 6l4 4 4-4"/></svg>
              <?= h($e['attachment_name']) ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: Update form -->
  <div>
    <div class="card">
      <div class="card-header">
        <h2>Update Status</h2>
      </div>
      <div class="card-body padded">
        <form method="POST" action="/admin/enquiry-update.php">
          <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
              <?php foreach (['new','read','replied','archived'] as $s): ?>
                <option value="<?= h($s) ?>"<?= $e['status'] === $s ? ' selected' : '' ?>><?= ucfirst(h($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="notes">Internal Notes</label>
            <textarea id="notes" name="notes" class="short" style="min-height:120px;"><?= h($e['notes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Save Changes</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>Quick Actions</h2></div>
      <div class="card-body padded" style="display:flex;flex-direction:column;gap:0.5rem;">
        <a href="<?= h($replyLink) ?>" class="btn btn-success btn-sm">
          Reply via Email
        </a>
        <a href="/admin/enquiries.php" class="btn btn-ghost btn-sm">
          &larr; Back to Enquiries
        </a>
      </div>
    </div>
  </div>

</div>

<?php adminFooter(); ?>
