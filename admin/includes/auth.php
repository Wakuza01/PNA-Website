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
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([trim($u)]);
        $user = $stmt->fetch();

        if ($user && password_verify($p, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['pa_admin_logged_in'] = true;
            $_SESSION['pa_admin_user']      = $user['username'];
            $_SESSION['pa_admin_uid']       = $user['id'];
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
