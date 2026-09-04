<?php
/**
 * Users list — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('users');

$db      = getDb();
$myUid   = (int)($_SESSION['pa_admin_uid'] ?? 0);
$users   = $db->query("SELECT id, username, permissions, created_at FROM users ORDER BY created_at ASC")->fetchAll();

adminHead('Users');
adminSidebar('users');
adminMain('Users', 'Manage admin accounts');
?>
  <div style="margin-top:0.5rem;">
    <a href="/admin/user-edit.php" class="btn btn-primary btn-sm">+ New User</a>
  </div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<div class="card">
  <div class="card-body">
    <?php if (empty($users)): ?>
      <div class="empty-state">
        <p>No users found.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Username</th>
              <th>Permissions</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <?php
              $perms    = json_decode($u['permissions'] ?? '[]', true) ?? [];
              $isMe     = ((int)$u['id'] === $myUid);
              $permLabels = [
                  'enquiries' => 'Enquiries',
                  'blog'      => 'Blog',
                  'traffic'   => 'Traffic',
                  'emails'    => 'Emails',
                  'settings'  => 'Settings',
                  'users'     => 'Users',
              ];
              $permNames = array_filter(
                  array_map(fn(string $p) => $permLabels[$p] ?? null, $perms)
              );
              ?>
              <tr>
                <td>
                  <?= h($u['username']) ?>
                  <?php if ($isMe): ?>
                    <span class="badge-you">You</span>
                  <?php endif; ?>
                </td>
                <td class="text-small text-muted">
                  <?= $permNames ? h(implode(', ', $permNames)) : '<span class="text-muted">None</span>' ?>
                </td>
                <td class="text-muted text-small"><?= formatDate((int)$u['created_at']) ?></td>
                <td>
                  <div class="action-row">
                    <a href="/admin/user-edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-xs">Edit</a>
                    <?php if (!$isMe): ?>
                      <form method="POST" action="/admin/user-delete.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button
                          type="submit"
                          class="btn btn-danger btn-xs"
                          data-confirm="Delete user &quot;<?= h(addslashes($u['username'])) ?>&quot;? This cannot be undone."
                        >Delete</button>
                      </form>
                    <?php else: ?>
                      <button class="btn btn-danger btn-xs" disabled title="Cannot delete your own account">Delete</button>
                    <?php endif; ?>
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
