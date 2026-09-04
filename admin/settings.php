<?php
/**
 * Settings — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('settings');

$db = getDb();

// Load all settings into an associative array
$rawSettings = $db->query("SELECT key, value FROM settings")->fetchAll();
$settings    = [];
foreach ($rawSettings as $row) {
    $settings[$row['key']] = $row['value'];
}

function setting(array $s, string $key, string $default = ''): string {
    return $s[$key] ?? $default;
}

// LinkedIn cache file info
$cacheFile    = dirname(__DIR__) . '/api/linkedin-cache.json';
$cacheExists  = file_exists($cacheFile);
$cacheModTime = $cacheExists ? filemtime($cacheFile) : null;

adminHead('Settings');
adminSidebar('settings');
adminMain('Settings', 'Site configuration and integrations');
?>
  <div style="margin-top:0.5rem;"></div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

  <!-- Left column -->
  <div>

    <!-- General Settings -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-header"><h2>General Settings</h2></div>
      <div class="card-body padded">
        <form method="POST" action="/admin/settings-save.php">

          <div class="form-section">
            <h3>Site Details</h3>
            <div class="form-group">
              <label for="site_name">Site Name</label>
              <input type="text" id="site_name" name="site_name" value="<?= h(setting($settings,'site_name')) ?>">
            </div>
            <div class="form-group">
              <label for="site_url">Site URL</label>
              <input type="url" id="site_url" name="site_url" value="<?= h(setting($settings,'site_url')) ?>" placeholder="https://…">
            </div>
            <div class="form-group">
              <label for="contact_email">Contact Email</label>
              <input type="email" id="contact_email" name="contact_email" value="<?= h(setting($settings,'contact_email')) ?>">
              <p class="form-hint">Enquiries from the contact form are sent to this address.</p>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save General Settings</button>
        </form>
      </div>
    </div>

    <!-- LinkedIn API -->
    <div class="card">
      <div class="card-header"><h2>LinkedIn Integration</h2></div>
      <div class="card-body padded">
        <form method="POST" action="/admin/settings-save.php">
          <input type="hidden" name="section" value="linkedin">

          <div class="form-section">
            <h3>API Credentials</h3>
            <div class="form-group">
              <label for="linkedin_client_id">Client ID</label>
              <input type="text" id="linkedin_client_id" name="linkedin_client_id" value="<?= h(setting($settings,'linkedin_client_id')) ?>">
            </div>
            <div class="form-group">
              <label for="linkedin_client_secret">Client Secret</label>
              <input type="password" id="linkedin_client_secret" name="linkedin_client_secret" value="<?= h(setting($settings,'linkedin_client_secret')) ?>" autocomplete="new-password">
            </div>
            <div class="form-group">
              <label for="linkedin_company_id">Company ID</label>
              <input type="text" id="linkedin_company_id" name="linkedin_company_id" value="<?= h(setting($settings,'linkedin_company_id')) ?>">
            </div>
            <div class="form-group">
              <label for="linkedin_access_token">Access Token</label>
              <textarea id="linkedin_access_token" name="linkedin_access_token" class="short" style="min-height:80px;font-size:0.8rem;font-family:monospace;"><?= h(setting($settings,'linkedin_access_token')) ?></textarea>
            </div>
            <div class="form-group">
              <label for="linkedin_cache_hours">Cache Duration (hours)</label>
              <input type="number" id="linkedin_cache_hours" name="linkedin_cache_hours" value="<?= h(setting($settings,'linkedin_cache_hours','4')) ?>" min="1" max="168" style="max-width:120px;">
              <p class="form-hint">How long to cache the LinkedIn feed before fetching again.</p>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save LinkedIn Settings</button>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">

        <div class="form-section">
          <h3>Cache Control</h3>
          <div class="cache-info">
            <?php if ($cacheExists && $cacheModTime): ?>
              Cache file exists. Last updated: <strong><?= formatDate($cacheModTime) ?></strong>
            <?php else: ?>
              <span>No cache file found.</span>
            <?php endif; ?>
          </div>
          <form method="POST" action="/admin/linkedin-refresh.php">
            <button type="submit" class="btn btn-ghost">Clear LinkedIn Cache</button>
          </form>
        </div>

      </div>
    </div>

  </div><!-- /left col -->

  <!-- Right column -->
  <div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><h2>Change Password</h2></div>
      <div class="card-body padded">
        <form method="POST" action="/admin/settings-save.php?action=password">
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
            <p class="form-hint">Minimum 8 characters recommended.</p>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>

    <!-- Admin info -->
    <div class="card" style="margin-top:1.5rem;">
      <div class="card-header"><h2>Admin Info</h2></div>
      <div class="card-body padded">
        <div class="detail-grid">
          <span class="detail-label">Logged in as</span>
          <span class="detail-value"><?= h(currentUser()) ?></span>
          <span class="detail-label">PHP Version</span>
          <span class="detail-value text-small"><?= phpversion() ?></span>
          <span class="detail-label">SQLite Version</span>
          <span class="detail-value text-small"><?= $db->query("SELECT sqlite_version()")->fetchColumn() ?></span>
        </div>
      </div>
    </div>

  </div><!-- /right col -->

</div>

<?php adminFooter(); ?>
