<?php
/**
 * Database — Pinion & Adams Admin
 * PDO SQLite connection with schema init and default seeding.
 */

declare(strict_types=1);

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = __DIR__ . '/../data/pa_admin.sqlite';
    $dataDir = dirname($dbPath);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    initSchema($pdo);

    return $pdo;
}

function initSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT    NOT NULL UNIQUE,
            password_hash TEXT   NOT NULL,
            created_at   INTEGER NOT NULL DEFAULT (strftime('%s','now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS enquiries (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            name            TEXT    NOT NULL,
            company         TEXT,
            email           TEXT    NOT NULL,
            phone           TEXT,
            service         TEXT,
            message         TEXT    NOT NULL,
            attachment_name TEXT,
            attachment_path TEXT,
            ip              TEXT,
            status          TEXT    NOT NULL DEFAULT 'new',
            notes           TEXT    NOT NULL DEFAULT '',
            submitted_at    INTEGER NOT NULL DEFAULT (strftime('%s','now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_posts (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT    NOT NULL,
            slug         TEXT    NOT NULL UNIQUE,
            excerpt      TEXT    NOT NULL DEFAULT '',
            content      TEXT    NOT NULL DEFAULT '',
            image_url    TEXT    NOT NULL DEFAULT '',
            category     TEXT    NOT NULL DEFAULT 'General',
            status       TEXT    NOT NULL DEFAULT 'draft',
            author       TEXT    NOT NULL DEFAULT 'P&A Admin',
            published_at INTEGER,
            created_at   INTEGER NOT NULL DEFAULT (strftime('%s','now')),
            updated_at   INTEGER NOT NULL DEFAULT (strftime('%s','now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_views (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            path       TEXT NOT NULL,
            referrer   TEXT NOT NULL DEFAULT '',
            user_agent TEXT NOT NULL DEFAULT '',
            ip         TEXT NOT NULL DEFAULT '',
            visited_at INTEGER NOT NULL DEFAULT (strftime('%s','now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS traffic_ignored_ips (
            ip TEXT PRIMARY KEY
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_subscribers (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            email           TEXT NOT NULL UNIQUE,
            name            TEXT NOT NULL DEFAULT '',
            status          TEXT NOT NULL DEFAULT 'active',
            source          TEXT NOT NULL DEFAULT 'footer',
            subscribed_at   INTEGER NOT NULL DEFAULT (strftime('%s','now')),
            unsubscribed_at INTEGER
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_sends (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            subject  TEXT NOT NULL,
            body     TEXT NOT NULL,
            sent_to  INTEGER NOT NULL DEFAULT 0,
            sent_at  INTEGER NOT NULL DEFAULT (strftime('%s','now'))
        )
    ");

    // Add permissions column if it doesn't exist (SQLite has no IF NOT EXISTS on ALTER)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT NOT NULL DEFAULT '[]'");
    } catch (\Exception $e) {
        // Column already exists — ignore
    }

    // Seed admin user if table is empty
    $count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $hash  = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $perms = '["enquiries","blog","traffic","emails","settings","users"]';
        $stmt  = $pdo->prepare("INSERT INTO users (username, password_hash, permissions) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, $perms]);
    }

    // Ensure the existing admin user has full permissions if they have none
    $pdo->exec("UPDATE users SET permissions = '[\"enquiries\",\"blog\",\"traffic\",\"emails\",\"settings\",\"users\"]' WHERE username = 'admin' AND (permissions = '[]' OR permissions = '')");

    // Seed default settings
    $defaults = [
        'contact_email'          => 'sales@pinionadams.co.za',
        'site_name'              => 'Pinion & Adams Fabricators',
        'site_url'               => 'https://www.pinionadams.co.za',
        'linkedin_client_id'     => '',
        'linkedin_client_secret' => '',
        'linkedin_company_id'    => '',
        'linkedin_access_token'  => '',
        'linkedin_cache_hours'   => '4',
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
}
