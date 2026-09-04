<?php
/**
 * User save action — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requirePermission('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

$validPerms  = ['enquiries', 'blog', 'traffic', 'emails', 'settings', 'users'];
$rawPerms    = (array)($_POST['permissions'] ?? []);
$permissions = array_values(array_filter($rawPerms, fn(string $p) => in_array($p, $validPerms, true)));

$redir = $id > 0 ? "/admin/user-edit.php?id={$id}" : '/admin/user-edit.php';

// Validate username
if ($username === '') {
    setFlash('error', 'Username is required.');
    header('Location: ' . $redir);
    exit;
}

// Validate password
if ($id === 0 && $password === '') {
    setFlash('error', 'Password is required for new users.');
    header('Location: ' . $redir);
    exit;
}

if ($password !== '' && $password !== $confirm) {
    setFlash('error', 'Passwords do not match.');
    header('Location: ' . $redir);
    exit;
}

if ($password !== '' && strlen($password) < 6) {
    setFlash('error', 'Password must be at least 6 characters.');
    header('Location: ' . $redir);
    exit;
}

$permsJson = json_encode($permissions);
$myUid     = (int)($_SESSION['pa_admin_uid'] ?? 0);

try {
    $db = getDb();

    // Check username uniqueness
    $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check->execute([$username, $id]);
    if ($check->fetch()) {
        setFlash('error', 'That username is already taken.');
        header('Location: ' . $redir);
        exit;
    }

    if ($id > 0) {
        // Edit existing user
        $existing = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $existing->execute([$id]);
        if (!$existing->fetch()) {
            setFlash('error', 'User not found.');
            header('Location: /admin/users.php');
            exit;
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE users SET username = ?, password_hash = ?, permissions = ? WHERE id = ?")
               ->execute([$username, $hash, $permsJson, $id]);
        } else {
            $db->prepare("UPDATE users SET username = ?, permissions = ? WHERE id = ?")
               ->execute([$username, $permsJson, $id]);
        }

        // Clear cached permissions if editing current user
        if ($id === $myUid) {
            unset($_SESSION['pa_admin_perms']);
            $_SESSION['pa_admin_user'] = $username;
        }

        setFlash('success', 'User updated successfully.');

    } else {
        // Create new user
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("INSERT INTO users (username, password_hash, permissions) VALUES (?, ?, ?)")
           ->execute([$username, $hash, $permsJson]);

        setFlash('success', 'User created successfully.');
    }

} catch (\Exception $e) {
    error_log('User save error: ' . $e->getMessage());
    setFlash('error', 'Failed to save user. Please try again.');
    header('Location: ' . $redir);
    exit;
}

header('Location: /admin/users.php');
exit;
