<?php
/**
 * Auth helpers — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function requireAuth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pa_admin');
        session_start();
    }
    if (empty($_SESSION['pa_admin_logged_in'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function attemptLogin(string $u, string $p): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pa_admin');
        session_start();
    }

    try {
        $db   = getDb();
        $stmt = $db->prepare("SELECT id, username, password_hash, permissions FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([trim($u)]);
        $user = $stmt->fetch();

        if ($user && password_verify($p, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['pa_admin_logged_in'] = true;
            $_SESSION['pa_admin_user']      = $user['username'];
            $_SESSION['pa_admin_uid']       = $user['id'];
            $_SESSION['pa_admin_perms']     = $user['permissions'] ?? '[]';
            return true;
        }
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
    }

    return false;
}

function logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pa_admin');
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

function currentUser(): string
{
    return $_SESSION['pa_admin_user'] ?? 'Admin';
}

function setFlash(string $type, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pa_admin');
        session_start();
    }
    $_SESSION['pa_admin_flash'] = compact('type', 'msg');
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pa_admin');
        session_start();
    }
    if (!isset($_SESSION['pa_admin_flash'])) {
        return null;
    }
    $flash = $_SESSION['pa_admin_flash'];
    unset($_SESSION['pa_admin_flash']);
    return $flash;
}

function currentPermissions(): array
{
    if (isset($_SESSION['pa_admin_perms'])) {
        return json_decode($_SESSION['pa_admin_perms'], true) ?? [];
    }
    $uid = $_SESSION['pa_admin_uid'] ?? 0;
    if (!$uid) {
        return [];
    }
    try {
        $db  = getDb();
        $row = $db->prepare("SELECT permissions FROM users WHERE id = ? LIMIT 1");
        $row->execute([$uid]);
        $r    = $row->fetch();
        $perms = json_decode($r['permissions'] ?? '[]', true) ?? [];
        $_SESSION['pa_admin_perms'] = json_encode($perms);
        return $perms;
    } catch (\Exception $e) {
        return [];
    }
}

function hasPermission(string $perm): bool
{
    return in_array($perm, currentPermissions(), true);
}

function requirePermission(string $perm): void
{
    requireAuth();
    if (!hasPermission($perm)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Denied</title><link rel="stylesheet" href="/admin/assets/admin.css"></head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;"><div style="text-align:center;"><h1 style="font-family:\'Barlow Condensed\',sans-serif;font-size:3rem;color:var(--accent-light);">Access Denied</h1><p style="color:var(--muted);margin:1rem 0;">You do not have permission to view this page.</p><a href="/admin/dashboard.php" class="btn btn-ghost">&larr; Back to Dashboard</a></div></body></html>';
        exit;
    }
}
