<?php
/**
 * Settings save action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/settings.php');
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'password') {
    // ── Change password ──
    $currentPw  = $_POST['current_password'] ?? '';
    $newPw      = $_POST['new_password']     ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';

    if ($newPw !== $confirmPw) {
        setFlash('error', 'New passwords do not match.');
        header('Location: /admin/settings.php');
        exit;
    }

    if (strlen($newPw) < 6) {
        setFlash('error', 'New password must be at least 6 characters.');
        header('Location: /admin/settings.php');
        exit;
    }

    try {
        $db   = getDb();
        $uid  = $_SESSION['pa_admin_uid'] ?? 0;
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPw, $user['password_hash'])) {
            setFlash('error', 'Current password is incorrect.');
            header('Location: /admin/settings.php');
            exit;
        }

        $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $uid]);
        setFlash('success', 'Password updated successfully.');

    } catch (Exception $e) {
        error_log('Password change error: ' . $e->getMessage());
        setFlash('error', 'Failed to update password.');
    }

    header('Location: /admin/settings.php');
    exit;
}

// ── Save general or LinkedIn settings ──
$section = $_POST['section'] ?? 'general';

if ($section === 'linkedin') {
    $keys = [
        'linkedin_client_id'     => trim($_POST['linkedin_client_id']     ?? ''),
        'linkedin_client_secret' => trim($_POST['linkedin_client_secret'] ?? ''),
        'linkedin_company_id'    => trim($_POST['linkedin_company_id']    ?? ''),
        'linkedin_access_token'  => trim($_POST['linkedin_access_token']  ?? ''),
        'linkedin_cache_hours'   => (string) max(1, (int)($_POST['linkedin_cache_hours'] ?? 4)),
    ];
} else {
    $keys = [
        'site_name'     => trim($_POST['site_name']     ?? ''),
        'site_url'      => trim($_POST['site_url']      ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
    ];
}

try {
    $db   = getDb();
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");

    foreach ($keys as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    setFlash('success', 'Settings saved successfully.');

} catch (Exception $e) {
    error_log('Settings save error: ' . $e->getMessage());
    setFlash('error', 'Failed to save settings.');
}

header('Location: /admin/settings.php');
exit;
