<?php
/**
 * Contact Form Handler — Pinion & Adams Fabricators
 * Accepts POST, validates, sanitizes, sends email, returns JSON.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/linkedin-config.php';

// ============================================================
// METHOD CHECK
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ============================================================
// RATE LIMITING (file-based, per IP, max 5 per hour)
// ============================================================
$ip            = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/pa_rl_' . md5($ip) . '.json';

if (!checkRateLimit($rateLimitFile)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again in an hour.']);
    exit;
}

// ============================================================
// HONEYPOT CHECK
// ============================================================
if (!empty($_POST['website'])) {
    // Silently accept to fool bots
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// SANITIZE & VALIDATE
// ============================================================
function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$name    = sanitize($_POST['name']    ?? '');
$company = sanitize($_POST['company'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = sanitize($_POST['phone']   ?? '');
$service = sanitize($_POST['service'] ?? '');
$message = sanitize($_POST['message'] ?? '');

$errors = [];

if (strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if (!$email) {
    $errors[] = 'Please enter a valid email address.';
}

if ($phone !== '' && !preg_match('/^[0-9\s\+\-\(\)]{7,20}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}

if (strlen($message) < 20) {
    $errors[] = 'Please provide more detail in your message (at least 20 characters).';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// ============================================================
// FILE ATTACHMENT VALIDATION
// ============================================================
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file         = $_FILES['attachment'];
    $maxSize      = 5 * 1024 * 1024; // 5MB
    $allowedExts  = ['pdf', 'jpg', 'jpeg', 'png', 'dwg'];
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum 5MB allowed.']);
        exit;
    }

    if (!in_array($ext, $allowedExts, true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Upload PDF, JPG, PNG, or DWG.']);
        exit;
    }

    // MIME check (skip for DWG as finfo may not know it)
    if ($ext !== 'dwg' && function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimes, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file content. Upload PDF, JPG, or PNG.']);
            exit;
        }
    }

    $attachment = [
        'tmp_name' => $file['tmp_name'],
        'name'     => basename($file['name']),
        'size'     => $file['size'],
        'mime'     => $mimeType ?? 'application/octet-stream',
    ];
}

// ============================================================
// BUILD & SEND EMAIL
// ============================================================
$subject  = 'New Enquiry from ' . $name;
$subject .= $company ? ' - ' . $company : '';
$subject .= ' | P&A Website';

$htmlBody = buildEmailHtml($name, $company, (string) $email, $phone, $service, $message);

$sent = sendEmail(CONTACT_EMAIL, $subject, $htmlBody, (string) $email, $name, $attachment);

// Log to rate limiter only after successful validation
logRateLimit($rateLimitFile);

// Save to admin database
try {
    $dbFile = __DIR__ . '/../admin/includes/db.php';
    if (file_exists($dbFile)) {
        require_once $dbFile;
        $db = getDb();

        // Handle attachment storage
        $savedAttachmentName = null;
        $savedAttachmentPath = null;
        if ($attachment) {
            $uploadDir = __DIR__ . '/../admin/uploads/enquiries/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $safeExt      = pathinfo($attachment['name'], PATHINFO_EXTENSION);
            $savedFilename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $safeExt;
            $destPath      = $uploadDir . $savedFilename;
            if (@move_uploaded_file($attachment['tmp_name'], $destPath)) {
                $savedAttachmentName = $attachment['name'];
                $savedAttachmentPath = $savedFilename;
            }
        }

        $stmt = $db->prepare("INSERT INTO enquiries (name, company, email, phone, service, message, attachment_name, attachment_path, ip) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $company ?: null, (string)$email, $phone ?: null, $service ?: null, $message, $savedAttachmentName, $savedAttachmentPath, $ip]);
    }
} catch (Exception $e) {
    error_log('Admin DB save failed: ' . $e->getMessage());
}

if ($sent) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to send your message. Please email us directly at ' . CONTACT_EMAIL,
    ]);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function buildEmailHtml(
    string $name,
    string $company,
    string $email,
    string $phone,
    string $service,
    string $message
): string {
    $companyHtml = $company
        ? '<tr><td style="padding:6px 0;width:140px;color:#666;font-weight:600;">Company:</td><td style="padding:6px 0;">' . $company . '</td></tr>'
        : '';
    $phoneHtml = $phone
        ? '<tr><td style="padding:6px 0;color:#666;font-weight:600;">Phone:</td><td style="padding:6px 0;">' . $phone . '</td></tr>'
        : '';
    $serviceHtml = $service
        ? '<tr><td style="padding:6px 0;color:#666;font-weight:600;">Service:</td><td style="padding:6px 0;">' . $service . '</td></tr>'
        : '';
    $messageFmt = nl2br($message);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Enquiry | P&amp;A Website</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;color:#333;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.1);">
          <!-- Header -->
          <tr>
            <td style="background:#1a1a1a;padding:28px 32px;">
              <p style="margin:0;font-size:20px;font-weight:700;color:#fff;letter-spacing:0.05em;text-transform:uppercase;">P&amp;A Fabricators</p>
              <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,0.5);letter-spacing:0.15em;text-transform:uppercase;">New Website Enquiry</p>
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td style="padding:32px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;">
                <tr>
                  <td style="padding:6px 0;width:140px;color:#666;font-weight:600;font-size:14px;">Name:</td>
                  <td style="padding:6px 0;font-size:14px;">{$name}</td>
                </tr>
                {$companyHtml}
                <tr>
                  <td style="padding:6px 0;color:#666;font-weight:600;font-size:14px;">Email:</td>
                  <td style="padding:6px 0;font-size:14px;"><a href="mailto:{$email}" style="color:#C8102E;">{$email}</a></td>
                </tr>
                {$phoneHtml}
                {$serviceHtml}
              </table>
              <hr style="border:none;border-top:1px solid #eee;margin:0 0 24px;">
              <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#1a1a1a;text-transform:uppercase;letter-spacing:0.05em;">Message</p>
              <div style="background:#f9f9f9;border-left:3px solid #C8102E;padding:16px 20px;border-radius:0 4px 4px 0;font-size:14px;line-height:1.75;color:#333;">{$messageFmt}</div>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background:#f0f0f0;padding:16px 32px;font-size:11px;color:#999;">
              Sent from <a href="https://www.pinionadams.co.za" style="color:#999;">pinionadams.co.za</a> contact form.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

function sendEmail(
    string $to,
    string $subject,
    string $htmlBody,
    string $replyTo,
    string $replyName,
    ?array $attachment
): bool {
    // Try PHPMailer if available
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return sendWithPHPMailer($to, $subject, $htmlBody, $replyTo, $replyName, $attachment);
        }
    }

    // Fallback: PHP mail() — no attachment support
    return sendWithMail($to, $subject, $htmlBody, $replyTo, $replyName);
}

function sendWithMail(
    string $to,
    string $subject,
    string $htmlBody,
    string $replyTo,
    string $replyName
): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: P&A Website <noreply@pinionadams.co.za>',
        'Reply-To: ' . $replyName . ' <' . $replyTo . '>',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    return (bool) @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}

function sendWithPHPMailer(
    string $to,
    string $subject,
    string $htmlBody,
    string $replyTo,
    string $replyName,
    ?array $attachment
): bool {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('noreply@pinionadams.co.za', 'P&A Website');
        $mail->addAddress($to, SITE_NAME);
        $mail->addReplyTo($replyTo, $replyName);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        if ($attachment) {
            $mail->addAttachment(
                $attachment['tmp_name'],
                $attachment['name'],
                'base64',
                $attachment['mime']
            );
        }

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

function checkRateLimit(string $file): bool
{
    if (!file_exists($file)) return true;

    $data = json_decode(file_get_contents($file), true);
    if (!isset($data['submissions']) || !is_array($data['submissions'])) return true;

    $oneHourAgo = time() - 3600;
    $recent     = array_filter($data['submissions'], fn($t) => $t > $oneHourAgo);

    return count($recent) < 5;
}

function logRateLimit(string $file): void
{
    $data = ['submissions' => []];

    if (file_exists($file)) {
        $existing = json_decode(file_get_contents($file), true);
        if (isset($existing['submissions'])) {
            $data = $existing;
        }
    }

    $data['submissions'][] = time();

    // Prune old entries
    $oneHourAgo          = time() - 3600;
    $data['submissions'] = array_values(
        array_filter($data['submissions'], fn($t) => $t > $oneHourAgo)
    );

    file_put_contents($file, json_encode($data), LOCK_EX);
}
