<?php
/**
 * Send email to all active subscribers — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('emails');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/emails.php');
    exit;
}

$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['body'] ?? '');

if ($subject === '' || $body === '') {
    setFlash('error', 'Subject and body are required.');
    header('Location: /admin/emails.php');
    exit;
}

$db = getDb();

try {
    $settings = $db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $fromName  = $settings['site_name'] ?? 'Pinion & Adams Fabricators';
    $fromEmail = $settings['contact_email'] ?? 'sales@pinionadams.co.za';

    $subscribers = $db->query(
        "SELECT id, email, name FROM email_subscribers WHERE status = 'active'"
    )->fetchAll();

    $sentCount = 0;

    foreach ($subscribers as $sub) {
        $to      = $sub['email'];
        $headers = implode("\r\n", [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);

        if (mail($to, $subject, $body, $headers)) {
            $sentCount++;
        }
    }

    // Record the send
    $stmt = $db->prepare(
        "INSERT INTO email_sends (subject, body, sent_to) VALUES (?, ?, ?)"
    );
    $stmt->execute([$subject, $body, $sentCount]);

    setFlash('success', 'Email sent to ' . $sentCount . ' subscriber' . ($sentCount !== 1 ? 's' : '') . '.');
} catch (Exception $e) {
    error_log('email-send.php error: ' . $e->getMessage());
    setFlash('error', 'An error occurred while sending. Please try again.');
}

header('Location: /admin/emails.php');
exit;
