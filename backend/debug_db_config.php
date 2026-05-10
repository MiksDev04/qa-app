<?php
/**
 * Temporary DB config diagnostic page.
 * Remove this file after Railway configuration is verified.
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$revealToken = envValue('DEBUG_CONFIG_TOKEN', '');
$requestToken = $_GET['token'] ?? '';
$shouldRevealPassword = $revealToken !== '' && hash_equals($revealToken, (string)$requestToken);

$connection = [
    'ok' => false,
    'message' => null,
];

try {
    $db = getDBConnection();
    $connection['ok'] = true;
    $connection['message'] = 'Connected successfully';
    $connection['server_info'] = $db->server_info;
} catch (Throwable $e) {
    $connection['message'] = $e->getMessage();
}

echo json_encode(
    [
        'environment' => [
            'APP_DEBUG' => envValue('APP_DEBUG', ''),
            'RAILWAY_ENVIRONMENT' => envValue('RAILWAY_ENVIRONMENT', ''),
            'RAILWAY_SERVICE_NAME' => envValue('RAILWAY_SERVICE_NAME', ''),
        ],
        'db_config' => [
            'DB_HOST' => DB_HOST,
            'DB_USER' => DB_USER,
            'DB_PASS' => $shouldRevealPassword ? DB_PASS : maskSecret(DB_PASS),
            'DB_NAME' => DB_NAME,
            'DB_PORT' => DB_PORT,
            'DB_CHARSET' => DB_CHARSET,
        ],
        'connection' => $connection,
        'password_revealed' => $shouldRevealPassword,
        'note' => 'Delete backend/debug_db_config.php after debugging.',
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

function maskSecret(string $value): string {
    if ($value === '') {
        return '';
    }

    $length = strlen($value);
    if ($length <= 8) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, 3) . str_repeat('*', $length - 6) . substr($value, -3);
}
