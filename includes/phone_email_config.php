<?php
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
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

loadEnv(__DIR__ . '/../.env');

define('PE_APP_ID', getenv('PE_APP_ID') ?: $_ENV['PE_APP_ID'] ?? 'YOUR_PHONE_EMAIL_APP_ID');

function verifyPhoneEmail(string $userJsonUrl): array|false {
    $json_data = @file_get_contents($userJsonUrl);
    if ($json_data === false) return false;
    $data = json_decode($json_data, true);
    if (empty($data['user_phone_number'])) return false;
    return [
        'country_code' => ltrim($data['user_country_code'] ?? '', '+'),
        'phone' => '+' . ltrim($data['user_country_code'] ?? '', '+') . $data['user_phone_number'],
        'phone_number' => $data['user_phone_number'] ?? '',
        'first_name' => $data['user_first_name'] ?? '',
        'last_name' => $data['user_last_name'] ?? '',
    ];
}
