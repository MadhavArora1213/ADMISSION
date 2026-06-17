<?php
// Function to load .env variables
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load Environment variables from root .env
loadEnv(__DIR__ . '/../.env');

define('SHOUTOUT_RAPIDAPI_KEY', getenv('SHOUTOUT_RAPIDAPI_KEY') ?: $_ENV['SHOUTOUT_RAPIDAPI_KEY'] ?? '2917291f48msh5c4c65f9ebd43f5p1bbedcjsnb413d8df2179');
define('SHOUTOUT_RAPIDAPI_HOST', getenv('SHOUTOUT_RAPIDAPI_HOST') ?: $_ENV['SHOUTOUT_RAPIDAPI_HOST'] ?? 'shoutout-otp1.p.rapidapi.com');
define('SHOUTOUT_API_KEY', getenv('SHOUTOUT_API_KEY') ?: $_ENV['SHOUTOUT_API_KEY'] ?? '');

/**
 * Initiates/Sends an OTP via ShoutOUT on RapidAPI.
 * Returns the referenceId on success or false on failure.
 */
function sendShoutoutOtp(string $recipient): string|false {
    $apiKey = SHOUTOUT_RAPIDAPI_KEY;
    $host = SHOUTOUT_RAPIDAPI_HOST;
    $shoutoutKey = SHOUTOUT_API_KEY;
    if (empty($apiKey)) {
        return false;
    }

    // Clean recipient phone number (remove "+" prefix if present, ShoutOUT destination usually expects digits only)
    $cleanRecipient = ltrim($recipient, '+');

    $url = "https://" . $host . "/send";
    $payload = json_encode([
        'content' => [
            'sms' => 'Your AdmissionSeason verification code is {{code}}'
        ],
        'destination' => $cleanRecipient,
        'source' => 'ShoutDEMO',
        'transport' => 'sms'
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-rapidapi-key: ' . $apiKey,
        'x-rapidapi-host: ' . $host,
        'Content-Type: application/json',
        'Authorization: Apikey ' . $shoutoutKey,
        'Content-Length: ' . strlen($payload)
    ]);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (!empty($data['referenceId'])) {
            return $data['referenceId'];
        }
    }
    return false;
}

/**
 * Verifies the 5-digit OTP code entered by the user via ShoutOUT on RapidAPI.
 * Returns true if verified, false otherwise.
 */
function verifyShoutoutOtp(string $referenceId, string $otpCode): bool {
    $apiKey = SHOUTOUT_RAPIDAPI_KEY;
    $host = SHOUTOUT_RAPIDAPI_HOST;
    $shoutoutKey = SHOUTOUT_API_KEY;
    if (empty($apiKey) || empty($referenceId) || empty($otpCode)) {
        return false;
    }

    $url = "https://" . $host . "/verify";
    $payload = json_encode([
        'referenceId' => $referenceId,
        'code'        => $otpCode
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-rapidapi-key: ' . $apiKey,
        'x-rapidapi-host: ' . $host,
        'Content-Type: application/json',
        'Authorization: Apikey ' . $shoutoutKey,
        'Content-Length: ' . strlen($payload)
    ]);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['statusCode']) && (string)$data['statusCode'] === '1000') {
            return true;
        }
    }
    return false;
}
