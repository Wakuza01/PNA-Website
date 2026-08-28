<?php
/**
 * LinkedIn Feed API — Pinion & Adams Fabricators
 * Returns cached/live LinkedIn posts as JSON.
 *
 * GET /api/linkedin-feed.php?limit=3         — last 3 posts
 * GET /api/linkedin-feed.php?limit=all       — all cached posts
 * GET /api/linkedin-feed.php?force_refresh=1&token=TOKEN — bypass cache
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: https://www.pinionadams.co.za');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/linkedin-config.php';

// ============================================================
// FORCE REFRESH CHECK (admin only)
// ============================================================
$forceRefresh = false;
if (!empty($_GET['force_refresh']) && $_GET['force_refresh'] === '1') {
    if (
        empty(ADMIN_REFRESH_TOKEN) ||
        empty($_GET['token']) ||
        $_GET['token'] !== ADMIN_REFRESH_TOKEN
    ) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $forceRefresh = true;
}

// ============================================================
// SERVE FROM CACHE IF FRESH
// ============================================================
if (!$forceRefresh && file_exists(CACHE_FILE)) {
    $cacheAge = time() - filemtime(CACHE_FILE);
    if ($cacheAge < CACHE_DURATION) {
        $cached = file_get_contents(CACHE_FILE);
        echo outputFiltered($cached);
        exit;
    }
}

// ============================================================
// FETCH LIVE FROM LINKEDIN API
// ============================================================
$posts = fetchLinkedInPosts();

if ($posts === null) {
    // API failed — serve stale cache if it exists
    if (file_exists(CACHE_FILE)) {
        $cached = file_get_contents(CACHE_FILE);
        echo outputFiltered($cached);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Save fresh results to cache
$encoded = json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents(CACHE_FILE, $encoded, LOCK_EX);

echo outputFiltered($encoded);

// ============================================================
// FUNCTIONS
// ============================================================

/**
 * Apply limit filter and return JSON string.
 */
function outputFiltered(string $json): string
{
    $posts = json_decode($json, true);
    if (!is_array($posts)) {
        return $json;
    }

    $limit = $_GET['limit'] ?? '3';
    if ($limit !== 'all') {
        $limit = max(1, (int) $limit);
        $posts = array_slice($posts, 0, $limit);
    }

    return json_encode($posts, JSON_UNESCAPED_UNICODE);
}

/**
 * Fetch posts from LinkedIn Organization Share API v2.
 * Returns array of normalized post objects, or null on failure.
 */
function fetchLinkedInPosts(): ?array
{
    if (empty(LINKEDIN_ACCESS_TOKEN) || empty(LINKEDIN_COMPANY_ID)) {
        error_log('LinkedIn API: credentials not configured');
        return null;
    }

    $companyId   = urlencode(LINKEDIN_COMPANY_ID);
    $accessToken = LINKEDIN_ACCESS_TOKEN;

    $url = "https://api.linkedin.com/v2/shares"
         . "?q=owners"
         . "&owners=urn%3Ali%3Aorganization%3A{$companyId}"
         . "&sortBy=LAST_MODIFIED"
         . "&count=20";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$accessToken}",
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: 202305',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'PinionAdams-Website/1.0',
    ]);

    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("LinkedIn API cURL error: {$curlError}");
        return null;
    }

    if ($httpCode !== 200 || !$response) {
        error_log("LinkedIn API HTTP error: {$httpCode}");
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['elements']) || !is_array($data['elements'])) {
        error_log('LinkedIn API: unexpected response structure');
        return null;
    }

    $posts = [];
    foreach ($data['elements'] as $element) {
        $post = parseLinkedInPost($element);
        if ($post !== null) {
            $posts[] = $post;
        }
    }

    return $posts;
}

/**
 * Normalize a single LinkedIn share element into our post structure.
 */
function parseLinkedInPost(array $element): ?array
{
    $text = trim($element['text']['text'] ?? '');
    if ($text === '') {
        return null;
    }

    // Title: first 80 chars, break at word boundary
    $titleRaw = substr($text, 0, 80);
    if (strlen($text) > 80) {
        $lastSpace = strrpos($titleRaw, ' ');
        $titleRaw  = ($lastSpace !== false ? substr($titleRaw, 0, $lastSpace) : $titleRaw) . '...';
    }
    $title = $titleRaw;

    // Excerpt: first 200 chars
    $excerptRaw = substr($text, 0, 200);
    if (strlen($text) > 200) {
        $lastSpace  = strrpos($excerptRaw, ' ');
        $excerptRaw = ($lastSpace !== false ? substr($excerptRaw, 0, $lastSpace) : $excerptRaw) . '...';
    }
    $excerpt = $excerptRaw;

    // Dates
    $timestamp = isset($element['created']['time'])
        ? (int) ($element['created']['time'] / 1000)
        : time();
    $date      = date('Y-m-d', $timestamp);
    $dateHuman = date('j F Y', $timestamp);

    // Image URL (if media attached)
    $imageUrl = $element['content']['contentEntities'][0]['thumbnails'][0]['resolvedUrl'] ?? null;

    // LinkedIn post URL
    $postId     = $element['id'] ?? '';
    $linkedinUrl = "https://www.linkedin.com/feed/update/{$postId}/";

    // Category classification
    $category = classifyPost($text);

    // URL-friendly slug
    $slug = slugify(substr($title, 0, 70));

    return [
        'id'          => $postId ?: md5($text . $timestamp),
        'title'       => $title,
        'excerpt'     => $excerpt,
        'content'     => $text,
        'date'        => $date,
        'date_human'  => $dateHuman,
        'image_url'   => $imageUrl,
        'linkedin_url'=> $linkedinUrl,
        'category'    => $category,
        'slug'        => $slug,
    ];
}

/**
 * Simple keyword-based category classification.
 */
function classifyPost(string $text): string
{
    $lower = strtolower($text);

    if (preg_match('/\b(award|certif|iso|accredit|milestone|achievement|proud|announce|welcome|hired|team)\b/', $lower)) {
        return 'Company Updates';
    }

    if (preg_match('/\b(training|academy|apprentice|learner|skill|qcto|merseta|student|graduate)\b/', $lower)) {
        return 'Training';
    }

    if (preg_match('/\b(fabricat|manufactur|machine|weld|coat|cnc|press brake|sheet metal|steel|precision|engineering)\b/', $lower)) {
        return 'Technical Articles';
    }

    return 'Industry News';
}

/**
 * Convert a string to a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}
