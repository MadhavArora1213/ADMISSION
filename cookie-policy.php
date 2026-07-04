<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/news_seo_helpers.php';
$siteBase = getBaseUrl();
$navBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Cookie Policy | AdmissionSeason</title>
<meta name="robots" content="index, follow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
<style>
.cookie-page{max-width:860px;margin:0 auto;padding:100px 24px 60px}
.cookie-page h1{font-size:2.2rem;font-weight:900;color:#0B2447;margin-bottom:8px;font-family:'Space Grotesk',sans-serif}
.cookie-page .subtitle{font-size:1rem;color:#64748b;margin-bottom:40px;font-family:'Inter',sans-serif}
.cookie-page h2{font-size:1.4rem;font-weight:800;color:#0B2447;margin:36px 0 12px;padding-top:20px;border-top:1px solid #e2e8f0;font-family:'Space Grotesk',sans-serif}
.cookie-page h3{font-size:1.1rem;font-weight:700;color:#19376D;margin:24px 0 8px}
.cookie-page p,.cookie-page li{font-size:.95rem;color:#334155;line-height:1.8;font-family:'Inter',sans-serif}
.cookie-page ul{padding-left:20px;margin:8px 0}
.cookie-page li{margin-bottom:6px}
.cookie-page a{color:#19376D;font-weight:600;text-decoration:underline;text-underline-offset:2px}
.cookie-page a:hover{color:#3b82f6}
.cookie-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:.9rem}
.cookie-table th{text-align:left;padding:12px 16px;background:#f1f5f9;color:#0B2447;font-weight:700;border-bottom:2px solid #e2e8f0}
.cookie-table td{padding:12px 16px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:top}
.cookie-table tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
.badge-necessary{background:#dcfce7;color:#166534}
.badge-analytics{background:#dbeafe;color:#1e40af}
.badge-marketing{background:#fef3c7;color:#92400e}
.badge-preferences{background:#f3e8ff;color:#6b21a8}
.last-updated{font-size:.82rem;color:#94a3b8;margin-bottom:24px}
.highlight-box{background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid #c7d2fe;border-radius:12px;padding:20px 24px;margin:20px 0}
.highlight-box p{margin:0;color:#1e3a5f}

@media(max-width:768px){
  .cookie-page{padding:80px 16px 40px}
  .cookie-page h1{font-size:1.6rem}
  .cookie-page h2{font-size:1.15rem;margin:28px 0 10px;padding-top:16px}
  .cookie-page h3{font-size:1rem}
  .cookie-page p,.cookie-page li{font-size:.88rem;line-height:1.7}
  .cookie-table{font-size:.8rem;display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  .cookie-table th,.cookie-table td{padding:10px 12px}
  .highlight-box{padding:16px;border-radius:10px}
  .badge{font-size:.65rem;padding:2px 8px}
}
@media(max-width:480px){
  .cookie-page{padding:70px 12px 32px}
  .cookie-page h1{font-size:1.4rem}
  .cookie-page h2{font-size:1.05rem;margin:24px 0 8px}
  .cookie-page p,.cookie-page li{font-size:.84rem}
  .highlight-box{padding:14px}
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="cookie-page">
  <h1>Cookie Policy</h1>
  <p class="last-updated">Last updated: <?= date('F d, Y') ?></p>

  <div class="highlight-box">
    <p><strong>In short:</strong> We use cookies to make our website work, analyze how you use it, remember your preferences, and (with your permission) show you relevant content. You can accept all, reject all, or customize which cookies you allow.</p>
  </div>

  <h2>1. What Are Cookies?</h2>
  <p>Cookies are small text files that are placed on your computer or mobile device when you visit a website. They are widely used to make websites work efficiently and to provide information to website owners about how visitors use their sites.</p>

  <h2>2. How We Use Cookies</h2>
  <p>We use cookies for several purposes:</p>
  <ul>
    <li><strong>Essential website functionality</strong> — to keep you logged in, remember your session, and ensure the site works properly</li>
    <li><strong>Analytics & performance</strong> — to understand how visitors use our site (e.g., which pages are visited most, how long users stay)</li>
    <li><strong>Marketing & advertising</strong> — to deliver relevant ads and track the effectiveness of our marketing campaigns</li>
    <li><strong>Preferences & personalization</strong> — to remember your choices (like language, region, search filters) and provide a personalized experience</li>
  </ul>

  <h2>3. Types of Cookies We Use</h2>

  <table class="cookie-table">
    <thead>
      <tr>
        <th>Cookie Type</th>
        <th>Purpose</th>
        <th>Duration</th>
        <th>Consent Required</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="badge badge-necessary">Necessary</span></td>
        <td>Session management, CSRF protection, login state, security headers</td>
        <td>Session / 1 year</td>
        <td>No (always active)</td>
      </tr>
      <tr>
        <td><span class="badge badge-analytics">Analytics</span></td>
        <td>Google Analytics, page views tracking, user behavior analysis, performance monitoring</td>
        <td>Up to 2 years</td>
        <td>Yes</td>
      </tr>
      <tr>
        <td><span class="badge badge-marketing">Marketing</span></td>
        <td>Retargeting ads, social media pixels, conversion tracking, ad personalization</td>
        <td>Up to 1 year</td>
        <td>Yes</td>
      </tr>
      <tr>
        <td><span class="badge badge-preferences">Preferences</span></td>
        <td>Language settings, region selection, search filters, UI customizations</td>
        <td>Up to 1 year</td>
        <td>Yes</td>
      </tr>
    </tbody>
  </table>

  <h2>4. Detailed Cookie List</h2>

  <h3>Strictly Necessary Cookies</h3>
  <table class="cookie-table">
    <thead><tr><th>Cookie Name</th><th>What It Does</th><th>Expiry</th></tr></thead>
    <tbody>
      <tr><td><code>PHPSESSID</code></td><td>Keeps you logged in and maintains your session across pages</td><td>Session</td></tr>
      <tr><td><code>csrf_token</code></td><td>Protects against Cross-Site Request Forgery attacks on forms</td><td>Session</td></tr>
      <tr><td><code>cookie_consent</code></td><td>Remembers your cookie consent choice so we don't ask again</td><td>1 year</td></tr>
      <tr><td><code>__cf_bm</code></td><td>Cloudflare bot management — protects against malicious traffic</td><td>30 minutes</td></tr>
    </tbody>
  </table>

  <h3>Analytics Cookies</h3>
  <table class="cookie-table">
    <thead><tr><th>Cookie Name</th><th>What It Does</th><th>Expiry</th></tr></thead>
    <tbody>
      <tr><td><code>_ga</code></td><td>Google Analytics — distinguishes unique users</td><td>2 years</td></tr>
      <tr><td><code>_ga_*</code></td><td>Google Analytics 4 — maintains session state</td><td>2 years</td></tr>
      <tr><td><code>_gid</code></td><td>Google Analytics — distinguishes users</td><td>24 hours</td></tr>
      <tr><td><code>_gat</code></td><td>Google Analytics — throttles request rate</td><td>1 minute</td></tr>
    </tbody>
  </table>

  <h3>Marketing Cookies</h3>
  <table class="cookie-table">
    <thead><tr><th>Cookie Name</th><th>What It Does</th><th>Expiry</th></tr></thead>
    <tbody>
      <tr><td><code>_fbp</code></td><td>Facebook Pixel — tracks visits across websites for ad targeting</td><td>3 months</td></tr>
      <tr><td><code>_gcl_au</code></td><td>Google Ads — stores conversion data for ad optimization</td><td>3 months</td></tr>
      <tr><td><code>IDE</code></td><td>Google DoubleClick — serves targeted advertisements</td><td>1 year</td></tr>
    </tbody>
  </table>

  <h3>Preference Cookies</h3>
  <table class="cookie-table">
    <thead><tr><th>Cookie Name</th><th>What It Does</th><th>Expiry</th></tr></thead>
    <tbody>
      <tr><td><code>user_pref</code></td><td>Remembers your search filters (state, course, budget)</td><td>1 year</td></tr>
      <tr><td><code>lang</code></td><td>Stores your preferred language setting</td><td>1 year</td></tr>
      <tr><td><code>recent_searches</code></td><td>Keeps track of your recent searches for quick access</td><td>30 days</td></tr>
    </tbody>
  </table>

  <h2>5. What Data Do We Collect via Cookies?</h2>
  <p>Through cookies, we may collect:</p>
  <ul>
    <li><strong>Device information</strong> — browser type, operating system, screen resolution, device type (mobile/desktop/tablet)</li>
    <li><strong>Usage data</strong> — pages visited, time spent on pages, click patterns, scroll depth, search queries</li>
    <li><strong>Location data</strong> — approximate geographic location (city/country level, not precise GPS)</li>
    <li><strong>Referral source</strong> — how you found our website (search engine, social media, direct, etc.)</li>
    <li><strong>Session data</strong> — login state, form progress, cart/shortlist contents</li>
    <li><strong>Interaction data</strong> — button clicks, form submissions, college/course saves, comparisons</li>
  </ul>

  <h2>6. Why Do We Collect This Data?</h2>
  <ul>
    <li><strong>To improve our website</strong> — understanding which features are used helps us prioritize improvements</li>
    <li><strong>To personalize your experience</strong> — showing relevant colleges, courses, and exams based on your interests</li>
    <li><strong>To ensure security</strong> — detecting and preventing fraud, bots, and unauthorized access</li>
    <li><strong>To measure performance</strong> — ensuring pages load fast and work across all devices</li>
    <li><strong>To run targeted advertising</strong> — showing you relevant ads on our site and other platforms (only with your consent)</li>
    <li><strong>To comply with legal obligations</strong> — maintaining records as required by law</li>
  </ul>

  <h2>7. Third-Party Cookies</h2>
  <p>Some cookies are placed by third-party services that appear on our pages:</p>
  <ul>
    <li><strong>Google Analytics</strong> — website usage analysis (Google Privacy Policy: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>)</li>
    <li><strong>Google Ads</strong> — advertising and conversion tracking</li>
    <li><strong>Facebook/Meta</strong> — social media integration and ad targeting</li>
    <li><strong>Cloudflare</strong> — security and performance optimization</li>
  </ul>

  <h2>8. How to Manage Your Cookie Preferences</h2>
  <p>You can change your cookie preferences at any time by:</p>
  <ul>
    <li>Clicking the <strong>cookie settings</strong> icon in the bottom-left corner of any page</li>
    <li>Adjusting your browser settings to block or delete cookies</li>
    <li>Using your browser's private/incognito mode</li>
  </ul>

  <h3>Browser-Specific Instructions</h3>
  <ul>
    <li><strong>Chrome:</strong> Settings → Privacy and security → Cookies</li>
    <li><strong>Firefox:</strong> Settings → Privacy & Security → Cookies and Site Data</li>
    <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
    <li><strong>Edge:</strong> Settings → Cookies and site permissions</li>
  </ul>

  <h2>9. Your Rights</h2>
  <p>Under applicable data protection laws (including India's Digital Personal Data Protection Act, 2023), you have the right to:</p>
  <ul>
    <li><strong>Know</strong> what data we collect and why</li>
    <li><strong>Access</strong> the personal data we hold about you</li>
    <li><strong>Withdraw consent</strong> at any time (without affecting previous processing)</li>
    <li><strong>Request deletion</strong> of your personal data</li>
    <li><strong>Lodge a complaint</strong> with the relevant data protection authority</li>
  </ul>

  <h2>10. Updates to This Policy</h2>
  <p>We may update this Cookie Policy from time to time. Any changes will be posted on this page with an updated "Last updated" date. We encourage you to review this policy periodically.</p>

  <h2>11. Contact Us</h2>
  <p>If you have any questions about our use of cookies, please contact us:</p>
  <ul>
    <li><strong>Email:</strong> privacy@admissionseason.com</li>
    <li><strong>Website:</strong> <a href="<?= $navBase ?>/contact">Contact Page</a></li>
  </ul>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
