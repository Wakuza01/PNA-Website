<?php
/**
 * Login — Pinion & Adams Admin
 */

declare(strict_types=1);

session_name('pa_admin');
session_start();

require_once __DIR__ . '/includes/auth.php';

// Already logged in?
if (!empty($_SESSION['pa_admin_logged_in'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($u === '' || $p === '') {
        $error = 'Please enter your username and password.';
    } elseif (attemptLogin($u, $p)) {
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Sign In | P&amp;A Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>

<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <span class="lm">P&amp;A</span>
      <span class="ls">Pinion &amp; Adams Fabricators</span>
    </div>

    <p class="login-title">Administration Panel</p>

    <?php if ($error !== ''): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php">
      <div class="form-group">
        <label for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          autocomplete="username"
          autofocus
          required
          value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div style="position:relative;">
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
            style="padding-right:2.75rem;"
          >
          <button type="button" id="pw-toggle" aria-label="Show password" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7d8590;padding:0;line-height:1;">
            <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:0.7rem;">
        Sign In
      </button>
    </form>

    <div class="login-back">
      <a href="/">&larr; Return to website</a>
    </div>
  </div>
</div>

<script src="/admin/assets/admin.js"></script>
<script>
  (function () {
    var pwToggle = document.getElementById('pw-toggle');
    var pwInput = document.getElementById('password');
    if (pwToggle && pwInput) {
      pwToggle.addEventListener('click', function () {
        var show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwToggle.style.color = show ? '#269dcc' : '#7d8590';
      });
    }
  })();
</script>
</body>
</html>
