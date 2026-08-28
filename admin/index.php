<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireAuth();
header('Location: /admin/dashboard.php');
exit;
