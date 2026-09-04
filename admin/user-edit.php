<?php
/**
 * Create / edit user — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('users');

$db        = getDb();
$id        = (int)($_GET['id'] ?? 0);
$isEditing = false;

$user = [
    'id'          => 0,
    'username'    => '',
    'permissions' => '[]',
];

if ($id > 0) {
    $stmt = $db->prepare("SELECT id, username, permissions FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        setFlash('error', 'User not found.');
        header('Location: /admin/users.php');
        exit;
    }

    $user      = $row;
    $isEditing = true;
}

$currentPerms = json_decode($user['permissions'] ?? '[]', true) ?? [];

$allPermissions = [
    'enquiries' => ['label' => 'Enquiries',  'desc' => 'View and manage contact enquiries'],
    'blog'      => ['label' => 'Blog Posts', 'desc' => 'Create, edit and publish blog posts'],
    'traffic'   => ['label' => 'Traffic',    'desc' => 'View website traffic analytics'],
    'emails'    => ['label' => 'Emails',     'desc' => 'Manage subscribers and send emails'],
    'settings'  => ['label' => 'Settings',   'desc' => 'Change site settings and integrations'],
    'users'     => ['label' => 'Users',      'desc' => 'Manage admin accounts (gives full access)'],
];

$pageTitle = $isEditing ? 'Edit User' : 'New User';
$pageSub   = $isEditing ? h($user['username']) : 'Create a new admin account';

adminHead($pageTitle);
adminSidebar('users');
adminMain($pageTitle, $isEditing ? $user['username'] : 'Create a new admin account');
?>
  <div style="margin-top:0.5rem;">
    <a href="/admin/users.php" class="btn btn-ghost btn-sm">&larr; All Users</a>
  </div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<form method="POST" action="/admin/user-save.php">
  <?php if ($isEditing): ?>
    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

    <!-- Left: account fields -->
    <div>
      <div class="card">
        <div class="card-header"><h2>Account Details</h2></div>
        <div class="card-body padded">

          <div class="form-group">
            <label for="username">Username *</label>
            <input
              type="text"
              id="username"
              name="username"
              required
              autocomplete="off"
              value="<?= h($user['username']) ?>"
              placeholder="e.g. john"
            >
          </div>

          <div class="form-group">
            <label for="password">Password<?= $isEditing ? ' <span class="form-hint" style="display:inline;">(leave blank to keep current)</span>' : ' *' ?></label>
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="new-password"
              <?= $isEditing ? '' : 'required' ?>
              placeholder="<?= $isEditing ? 'Leave blank to keep current password' : 'Enter password' ?>"
            >
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              autocomplete="new-password"
              placeholder="Repeat password"
            >
          </div>

        </div>
      </div>
    </div>

    <!-- Right: permissions -->
    <div>
      <div class="card">
        <div class="card-header"><h2>Permissions</h2></div>
        <div class="card-body padded">

          <p class="form-hint" style="margin-bottom:0.75rem;">Select which sections this user can access.</p>

          <div class="perm-grid">
            <?php foreach ($allPermissions as $key => $info): ?>
              <label class="perm-item">
                <input
                  type="checkbox"
                  name="permissions[]"
                  value="<?= h($key) ?>"
                  <?= in_array($key, $currentPerms, true) ? 'checked' : '' ?>
                >
                <div>
                  <span class="perm-item-label"><?= h($info['label']) ?></span>
                  <span class="perm-item-desc"><?= h($info['desc']) ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="alert alert-error" style="margin-top:1rem;font-size:0.8rem;">
            Users with the &lsquo;Users&rsquo; permission can manage all other accounts.
          </div>

        </div>
      </div>
    </div>

  </div>

  <div style="margin-top:1rem;">
    <button type="submit" class="btn btn-primary">
      <?= $isEditing ? 'Save Changes' : 'Create User' ?>
    </button>
    <a href="/admin/users.php" class="btn btn-ghost" style="margin-left:0.5rem;">Cancel</a>
  </div>

</form>

<?php adminFooter(); ?>
