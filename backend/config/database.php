<?php
/**
 * Database Configuration — MySQLi (Railway Ready Fixed)
 * Quality Assurance Management System
 * backend/config/database.php
 */

/* -------------------------------------------------
   Load .env file (simple loader, no dependencies)
-------------------------------------------------- */
function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        // Remove quotes safely
        $value = trim($value, "\"'");

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

loadEnv(__DIR__ . '/../.env');


/* -------------------------------------------------
   ENV HELPER
-------------------------------------------------- */
function envFirst(array $keys, $default = '') {
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') return $value;
    }
    return $default;
}


/* -------------------------------------------------
   RAILWAY MYSQL SUPPORT
-------------------------------------------------- */
$mysqlUrl = envFirst(['MYSQL_URL', 'MYSQL_PUBLIC_URL'], '');
$mysqlUrlParts = $mysqlUrl ? parse_url($mysqlUrl) : [];

/* Host */
define('DB_HOST', envFirst([
    'DB_HOST',
], $mysqlUrlParts['host'] ?? 'localhost'));

/* User */
define('DB_USER', envFirst([
    'DB_USER',
], $mysqlUrlParts['user'] ?? 'root'));

/* Password */
define('DB_PASS', envFirst([
    'DB_PASS',
], $mysqlUrlParts['pass'] ?? ''));

/* DB Name (FIXED PATH ISSUE) */
define('DB_NAME', envFirst([
    'DB_NAME',
], isset($mysqlUrlParts['path']) ? ltrim($mysqlUrlParts['path'], '/') : ''));

/* Port */
define('DB_PORT', (int) envFirst([
    'DB_PORT',
    'MYSQLPORT',
    'MYSQL_PORT'
], $mysqlUrlParts['port'] ?? 3306));

define('DB_CHARSET', envFirst(['DB_CHARSET'], 'utf8mb4'));


/* -------------------------------------------------
   LOGGING
-------------------------------------------------- */
error_log("DB CONFIG => " . DB_HOST . ":" . DB_PORT . " DB=" . DB_NAME);


/* -------------------------------------------------
   CONNECTION
-------------------------------------------------- */
function getDBConnection(): mysqli {

    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            $conn->set_charset(DB_CHARSET);

        } catch (Throwable $e) {
            error_log('[DB Connection Error] ' . $e->getMessage());

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]);

            exit;
        }
    }

    return $conn;
}


/* -------------------------------------------------
   JSON RESPONSE
-------------------------------------------------- */
function jsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));

    exit;
}


/* -------------------------------------------------
   SANITIZE
-------------------------------------------------- */
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}


/* -------------------------------------------------
   VALIDATION
-------------------------------------------------- */
function validateRequired(array $fields, array $source): array {
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($source[$field]) || trim((string)$source[$field]) === '') {
            $errors[$field] = ucwords(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    return $errors;
}


/* -------------------------------------------------
   FETCH ALL
-------------------------------------------------- */
function dbFetchAll(string $sql, string $types = '', array $params = []): array {
    $conn = getDBConnection();

    $stmt = $conn->prepare($sql);

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $stmt->close();

    return $data;
}


/* -------------------------------------------------
   FETCH ONE
-------------------------------------------------- */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array {
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}


/* -------------------------------------------------
   EXECUTE (FIXED bind_param BUG)
-------------------------------------------------- */
function dbExecute(string $sql, string $types = '', array $params = []) {
    $conn = getDBConnection();

    $stmt = $conn->prepare($sql);

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    if (stripos($sql, 'INSERT') === 0) {
        return $conn->insert_id;
    }

    return $stmt->affected_rows;
}


/* -------------------------------------------------
   TRANSACTIONS
-------------------------------------------------- */
function dbBegin(): void {
    getDBConnection()->begin_transaction();
}

function dbCommit(): void {
    getDBConnection()->commit();
}

function dbRollback(): void {
    getDBConnection()->rollback();
}