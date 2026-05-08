<?php
/**
 * Database Configuration — MySQLi
 * Quality Assurance Management System
 * backend/config/database.php
 */

/**
 * Unified environment reader (Railway + Local support)
 */
function env(string $key, $default = null) {
    static $localEnv = null;

    // Load .env only for local development
    if ($localEnv === null) {
        $envPath = __DIR__ . '/../.env';
        $localEnv = file_exists($envPath) ? loadEnvFile($envPath) : [];
    }

    // 1. Railway / server environment (priority)
    $value = getenv($key);
    if ($value !== false && $value !== null && $value !== '') {
        return $value;
    }

    // 2. Local .env fallback
    if (isset($localEnv[$key])) {
        return $localEnv[$key];
    }

    // 3. Default fallback
    return $default;
}

/**
 * Load .env file (LOCAL ONLY)
 */
function loadEnvFile(string $path): array {
    if (!file_exists($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$key, $value] = $parts;

        $values[trim($key)] = trim($value, "\"' ");
    }

    return $values;
}

/**
 * DATABASE CONFIG (Railway STANDARD VARIABLES)
 */
define('DB_HOST', env('MYSQLHOST', 'localhost'));
define('DB_USER', env('MYSQLUSER', 'root'));
define('DB_PASS', env('MYSQLPASSWORD', ''));
define('DB_NAME', env('MYSQLDATABASE', 'qa_system'));
define('DB_PORT', (int) env('MYSQLPORT', 3306));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

/**
 * Get DB Connection (Singleton)
 */
function getDBConnection(): mysqli {
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conn->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            error_log('[DB Connection] ' . $e->getMessage());
            jsonResponse(false, 'Database connection failed.', [], 500);
        }
    }

    return $conn;
}

/**
 * JSON RESPONSE HELPER
 */
function jsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * SANITIZE
 */
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * VALIDATE REQUIRED FIELDS
 */
function validateRequired(array $fields, array $source): array {
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($source[$field]) || trim((string)$source[$field]) === '') {
            $label = ucwords(str_replace('_', ' ', $field));
            $errors[$field] = "{$label} is required.";
        }
    }

    return $errors;
}

/**
 * FETCH ALL
 */
function dbFetchAll(string $sql, string $types = '', array $params = []): array {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($types !== '' && $params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;

    } catch (mysqli_sql_exception $e) {
        error_log('[dbFetchAll] ' . $e->getMessage());
        return [];
    }
}

/**
 * FETCH ONE
 */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array {
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}

/**
 * EXECUTE (INSERT / UPDATE / DELETE)
 */
function dbExecute(string $sql, string $types = '', array $params = []): int|false {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($types !== '' && $params) {
            $bindParams = [$types];

            foreach ($params as &$param) {
                $bindParams[] = &$param;
            }

            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }

        $stmt->execute();

        $result = str_starts_with(strtoupper(ltrim($sql)), 'INSERT')
            ? (int)$conn->insert_id
            : (int)$stmt->affected_rows;

        $stmt->close();

        return $result;

    } catch (mysqli_sql_exception $e) {
        error_log('[dbExecute] ' . $e->getMessage());
        return false;
    }
}

/**
 * TRANSACTIONS
 */
function dbBegin(): void {
    getDBConnection()->begin_transaction();
}

function dbCommit(): void {
    getDBConnection()->commit();
}

function dbRollback(): void {
    getDBConnection()->rollback();
}