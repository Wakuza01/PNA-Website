<?php
/**
 * Layout helpers — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function adminHead(string $title): void
{
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="en">' . "\n";
    echo '<head>' . "\n";
    echo '  <meta charset="UTF-8">' . "\n";
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '  <meta name="robots" content="noindex, nofollow">' . "\n";
    echo '  <title>' . h($title) . ' | P&amp;A Admin</title>' . "\n";
    echo '  <link rel="stylesheet" href="/admin/assets/admin.css">' . "\n";
    echo '</head>' . "\n";
    echo '<body>' . "\n";
    echo '<div class="admin-wrap">' . "\n";
}

function adminSidebar(string $active): void
{
    // Count new enquiries for badge
    $newCount = 0;
    try {
        $db = getDb();
        $newCount = (int) $db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
    } catch (Exception $e) {
        // silently ignore
    }

    $nav = [
        'dashboard'  => ['label' => 'Dashboard',  'href' => '/admin/dashboard.php',  'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>'],
        'enquiries'  => ['label' => 'Enquiries',  'href' => '/admin/enquiries.php',  'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 10.667A1.333 1.333 0 0 1 12.667 12H4.667L2 14.667V3.333A1.333 1.333 0 0 1 3.333 2h9.334A1.333 1.333 0 0 1 14 3.333v7.334z"/></svg>'],
        'blog'       => ['label' => 'Blog Posts', 'href' => '/admin/blog.php',        'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13.333 2H2.667A.667.667 0 0 0 2 2.667v10.666A.667.667 0 0 0 2.667 14h10.666A.667.667 0 0 0 14 13.333V2.667A.667.667 0 0 0 13.333 2z"/><path d="M5 5.333h6M5 8h6M5 10.667h3.333"/></svg>'],
        'traffic'    => ['label' => 'Traffic',    'href' => '/admin/traffic.php',    'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="1,11 5,6 9,9 15,2"/><polyline points="11,2 15,2 15,6"/></svg>'],
        'emails'     => ['label' => 'Emails',     'href' => '/admin/emails.php',     'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="10" rx="1.5"/><polyline points="1,3 8,9 15,3"/></svg>'],
        'settings'   => ['label' => 'Settings',   'href' => '/admin/settings.php',   'icon' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="2"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.05 3.05l1.414 1.414M11.536 11.536l1.414 1.414M3.05 12.95l1.414-1.414M11.536 4.464l1.414-1.414"/></svg>'],
    ];

    echo '<button class="mobile-toggle" id="sidebar-toggle" aria-label="Toggle menu">' . "\n";
    echo '  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 4h14M2 9h14M2 14h14"/></svg>' . "\n";
    echo '</button>' . "\n";
    echo '<div class="sidebar-overlay"></div>' . "\n";
    echo '<aside class="sidebar">' . "\n";
    echo '  <div class="sidebar-logo">' . "\n";
    echo '    <span class="logo-mark">P&amp;A</span>' . "\n";
    echo '    <span class="logo-sub">Admin Panel</span>' . "\n";
    echo '  </div>' . "\n";
    echo '  <nav class="sidebar-nav">' . "\n";

    foreach ($nav as $key => $item) {
        $cls = ($active === $key) ? ' class="active"' : '';
        echo '    <a href="' . h($item['href']) . '"' . $cls . '>';
        echo $item['icon'];
        echo h($item['label']);
        if ($key === 'enquiries' && $newCount > 0) {
            echo '<span class="sidebar-badge">' . $newCount . '</span>';
        }
        echo '</a>' . "\n";
    }

    echo '    <hr class="sidebar-divider">' . "\n";
    echo '    <a href="/admin/logout.php">' . "\n";
    echo '      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 14H3.333A1.333 1.333 0 0 1 2 12.667V3.333A1.333 1.333 0 0 1 3.333 2H6M10.667 11.333 14 8l-3.333-3.333M14 8H6"/></svg>' . "\n";
    echo '      Logout' . "\n";
    echo '    </a>' . "\n";
    echo '  </nav>' . "\n";
    echo '  <div class="sidebar-footer">' . "\n";
    echo '    <strong>' . h(currentUser()) . '</strong>' . "\n";
    echo '    <a href="/" target="_blank">View Website &rarr;</a>' . "\n";
    echo '  </div>' . "\n";
    echo '</aside>' . "\n";
}

function adminMain(string $pageTitle, string $pageSub = ''): void
{
    echo '<main class="main">' . "\n";
    echo '  <div class="page-header">' . "\n";
    echo '    <div class="page-header-left">' . "\n";
    echo '      <h1>' . h($pageTitle) . '</h1>' . "\n";
    if ($pageSub !== '') {
        echo '      <p class="page-sub">' . h($pageSub) . '</p>' . "\n";
    }
    echo '    </div>' . "\n";
    // Slot for header actions — callers echo them right after calling adminMain()
}

function adminFooter(): void
{
    echo '</main>' . "\n";
    echo '</div>' . "\n";
    echo '<script src="/admin/assets/admin.js"></script>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}

function flashHtml(): string
{
    $flash = getFlash();
    if ($flash === null) {
        return '';
    }
    $type = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
    return '<div class="alert ' . h($type) . '">' . h($flash['msg']) . '</div>' . "\n";
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDate(int $ts): string
{
    return date('d M Y, H:i', $ts);
}
