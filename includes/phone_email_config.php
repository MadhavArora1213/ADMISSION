<?php
// Phone.Email Configuration
// Get your App ID from https://admin.phone.email
define('PE_APP_ID', getenv('PE_APP_ID') ?: $_ENV['PE_APP_ID'] ?? 'YOUR_PHONE_EMAIL_APP_ID');

/**
 * Verifies phone number by fetching user data from Phone.Email user_json_url.
 * Returns array with verified user data on success, false on failure.
 */
function verifyPhoneEmail(string $userJsonUrl): array|false {
    $json_data = @file_get_contents($userJsonUrl);
    if ($json_data === false) {
        return false;
    }
    $data = json_decode($json_data, true);
    if (empty($data['user_phone_number'])) {
        return false;
    }
    return [
        'country_code' => ltrim($data['user_country_code'] ?? '', '+'),
        'phone' => '+' . ltrim($data['user_country_code'] ?? '', '+') . $data['user_phone_number'],
        'phone_number' => $data['user_phone_number'] ?? '',
        'first_name' => $data['user_first_name'] ?? '',
        'last_name' => $data['user_last_name'] ?? '',
    ];
}
