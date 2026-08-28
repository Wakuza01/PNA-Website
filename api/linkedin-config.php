<?php
/**
 * LinkedIn API Configuration
 * Pinion & Adams Fabricators
 *
 * Fill in all values before deployment.
 * Do NOT commit this file to version control with real credentials.
 */

// LinkedIn OAuth 2.0 credentials
// Obtain from https://www.linkedin.com/developers/apps
define('LINKEDIN_CLIENT_ID', '');
define('LINKEDIN_CLIENT_SECRET', '');

// P&A's LinkedIn Company Page numeric ID
// Find it in the LinkedIn admin URL: linkedin.com/company/[ID]/admin/
define('LINKEDIN_COMPANY_ID', '');

// Long-lived OAuth 2.0 access token with r_organization_social scope
// Generate using the OAuth flow or LinkedIn Token Generator tool
define('LINKEDIN_ACCESS_TOKEN', '');

// Cache settings
define('CACHE_DURATION', 14400); // 4 hours in seconds
define('CACHE_FILE', __DIR__ . '/linkedin-cache.json');

// Secret token for admin force-refresh: /api/linkedin-feed.php?force_refresh=1&token=YOUR_TOKEN
define('ADMIN_REFRESH_TOKEN', '');

// Contact form settings
define('CONTACT_EMAIL', 'sales@pinionadams.co.za');
define('SITE_NAME', 'Pinion & Adams Fabricators');
define('SITE_URL', 'https://www.pinionadams.co.za');
