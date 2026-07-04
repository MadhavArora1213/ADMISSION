<?php
/**
 * Upload Sync to GitHub
 * Called after any file upload in admin panel.
 * Uses GitHub API (no shell/SSH needed — works on Hostinger shared hosting).
 *
 * Setup:
 *   1. Set GITHUB_TOKEN below (Personal Access Token with repo scope)
 *   2. Set GITHUB_REPO below (owner/repo format)
 *   3. Call sync_to_github($filePath) after move_uploaded_file()
 */

// ─── CONFIG ───
define('GITHUB_TOKEN', '');   // Put your GitHub PAT here
define('GITHUB_REPO', 'MadhavArora1213/ADMISSION');
define('GITHUB_BRANCH', 'main');
define('SYNC_ENABLED', true); // Set false to disable

/**
 * Sync a single file to GitHub
 * @param string $relativePath  Path relative to project root, e.g. "uploads/college_logo.png"
 * @return bool
 */
function sync_to_github(string $relativePath): bool
{
    if (!SYNC_ENABLED || empty(GITHUB_TOKEN)) return false;

    $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');

    if (!file_exists($fullPath)) {
        error_log("[upload_sync] File not found: $fullPath");
        return false;
    }

    // Skip very large files (>5MB)
    if (filesize($fullPath) > 5 * 1024 * 1024) {
        error_log("[upload_sync] Skipped (too large): $relativePath");
        return false;
    }

    $content = file_get_contents($fullPath);
    if ($content === false) {
        error_log("[upload_sync] Cannot read: $fullPath");
        return false;
    }

    $b64 = base64_encode($content);
    $encodedPath = str_replace('+', '-', str_replace('/', '_', $relativePath));
    $message = "uploads: add " . basename($relativePath) . " [" . date('Y-m-d H:i') . "]";

    // Check if file exists on GitHub (need SHA for update)
    $sha = null;
    $checkUrl = "https://api.github.com/repos/" . GITHUB_REPO . "/contents/" . $relativePath . "?ref=" . GITHUB_BRANCH;
    $checkResponse = github_api($checkUrl);
    if ($checkResponse && isset($checkResponse['sha'])) {
        $sha = $checkResponse['sha'];
    }

    // Create or update file
    $payload = [
        'message'  => $message,
        'content'  => $b64,
        'branch'   => GITHUB_BRANCH,
    ];
    if ($sha) {
        $payload['sha'] = $sha; // Update existing
    }

    $url = "https://api.github.com/repos/" . GITHUB_REPO . "/contents/" . $relativePath;
    $result = github_api($url, $payload, 'PUT');

    if ($result && isset($result['commit']['sha'])) {
        error_log("[upload_sync] Synced: $relativePath");
        return true;
    }

    error_log("[upload_sync] Failed: $relativePath — " . json_encode($result));
    return false;
}

/**
 * Delete a file from GitHub
 * @param string $relativePath  e.g. "uploads/old_image.jpg"
 * @return bool
 */
function delete_from_github(string $relativePath): bool
{
    if (!SYNC_ENABLED || empty(GITHUB_TOKEN)) return false;

    // Get current SHA
    $checkUrl = "https://api.github.com/repos/" . GITHUB_REPO . "/contents/" . $relativePath . "?ref=" . GITHUB_BRANCH;
    $checkResponse = github_api($checkUrl);

    if (!$checkResponse || !isset($checkResponse['sha'])) {
        error_log("[upload_sync] File not found on GitHub: $relativePath");
        return false;
    }

    $url = "https://api.github.com/repos/" . GITHUB_REPO . "/contents/" . $relativePath;
    $payload = [
        'message' => "uploads: delete " . basename($relativePath) . " [" . date('Y-m-d H:i') . "]",
        'sha'     => $checkResponse['sha'],
        'branch'  => GITHUB_BRANCH,
    ];

    $result = github_api($url, $payload, 'DELETE');
    return ($result && isset($result['commit']['sha']));
}

/**
 * Sync multiple files at once (batch)
 * @param string[] $relativePaths
 * @return int  Number of successful syncs
 */
function sync_batch_to_github(array $relativePaths): int
{
    $synced = 0;
    foreach ($relativePaths as $path) {
        if (sync_to_github($path)) $synced++;
    }
    return $synced;
}

// ─── Internal: GitHub API helper ───
function github_api(string $url, ?array $payload = null, string $method = 'GET'): ?array
{
    $ch = curl_init($url);
    $headers = [
        'Authorization: token ' . GITHUB_TOKEN,
        'User-Agent: AdmissionSeason-UploadSync',
        'Accept: application/vnd.github.v3+json',
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => $method,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }

    error_log("[upload_sync] API $method $url returned $httpCode: $response");
    return null;
}
