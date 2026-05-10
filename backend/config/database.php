<?php
/**
 * Database Configuration — MySQLi
 * Quality Assurance Management System
 * backend/config/database.php
 */

loadEnvFile(__DIR__ . '/../.env');

$databaseUrl = envValue('DATABASE_URL', envValue('MYSQL_URL', ''));
$databaseConfig = $databaseUrl !== '' ? parseDatabaseUrl($databaseUrl) : [];

define('DB_HOST',    $databaseConfig['host']    ?? envValue('DB_HOST', envValue('MYSQLHOST', 'localhost')));
define('DB_USER',    $databaseConfig['user']    ?? envValue('DB_USER', envValue('MYSQLUSER', 'root')));
define('DB_PASS',    $databaseConfig['pass']    ?? envValue('DB_PASS', envValue('MYSQLPASSWORD', '')));
define('DB_NAME',    $databaseConfig['name']    ?? envValue('DB_NAME', envValue('MYSQLDATABASE', 'qa_system')));
define('DB_PORT',    (int)($databaseConfig['port'] ?? envValue('DB_PORT', envValue('MYSQLPORT', '3306'))));
define('DB_CHARSET', envValue('DB_CHARSET', 'utf8mb4'));

/**
 * Load local .env values for XAMPP/development.
 * Railway injects variables directly, so this is a local fallback only.
 */
function loadEnvFile(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = cleanEnvValue($value);

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function envValue(string $key, ?string $default = null): ?string {
    $value = getenv($key);

    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }

    if ($value === false && isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return cleanEnvValue((string)$value);
}

function cleanEnvValue(string $value): string {
    return trim(trim($value), "\"'");
}

function parseDatabaseUrl(string $url): array {
    $parts = parse_url($url);

    if ($parts === false) {
        return [];
    }

    $path = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

    return [
        'host' => $parts['host'] ?? null,
        'user' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
        'pass' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        'name' => $path !== '' ? $path : null,
        'port' => $parts['port'] ?? null,
    ];
}

/**
 * Get a shared MySQLi connection (singleton).
 * Terminates with a JSON error response if connection fails.
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
            jsonResponse(false, 'Database connection failed. Please contact the administrator.', [], 500);
        }
    }

    return $conn;
}

/**
 * Send a JSON response and stop execution.
 *
 * @param bool   $success
 * @param string $message
 * @param array  $data     Extra keys merged into the response object
 * @param int    $httpCode HTTP status code
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
 * Sanitize a scalar value for safe output / storage.
 * Always use prepared statements for DB queries — this is for display/logging only.
 */
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate that required fields are present and non-empty.
 *
 * @param string[] $fields  Field names to check
 * @param array    $source  Data array (e.g. $_POST)
 * @return array            Associative array of [ fieldName => errorMessage ]
 */
function validateRequired(array $fields, array $source): array {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($source[$field]) || trim((string) $source[$field]) === '') {
            $label          = ucwords(str_replace('_', ' ', $field));
            $errors[$field] = "{$label} is required.";
        }
    }
    return $errors;
}

/**
 * Execute a prepared MySQLi statement and return all rows.
 *
 * @param string $sql    SQL with ? placeholders
 * @param string $types  Binding type string (e.g. 'ssi')
 * @param array  $params Values matching the placeholders
 * @return array         Array of associative rows
 */
function dbFetchAll(string $sql, string $types = '', array $params = []): array {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception('Prepare failed: ' . $conn->error);
        }

        if ($types !== '' && count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;

    } catch (mysqli_sql_exception $e) {
        error_log('[dbFetchAll] ' . $e->getMessage() . ' | SQL: ' . $sql);
        return [];
    }
}

/**
 * Execute a prepared MySQLi statement and return a single row.
 *
 * @return array|null  Associative row or null if not found
 */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array {
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}

/**
 * Execute an INSERT / UPDATE / DELETE prepared statement.
 *
 * @return int|false  insert_id for INSERT, affected_rows for UPDATE/DELETE, false on error
 */
function dbExecute(string $sql, string $types = '', array $params = []): int|false {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception('Prepare failed: ' . $conn->error);
        }

        if ($types !== '' && count($params) > 0) {
            // Build proper reference array for bind_param
            $bindParams = [$types];
            foreach ($params as &$param) {
                $bindParams[] = &$param;
            }
            // Use call_user_func_array to properly pass references to bind_param
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }

        $stmt->execute();

        // Return insert_id for INSERT statements, affected_rows otherwise
        $result = str_starts_with(ltrim(strtoupper($sql)), 'INSERT')
            ? (int) $conn->insert_id
            : (int) $stmt->affected_rows;

        $stmt->close();
        return $result;

    } catch (mysqli_sql_exception $e) {
        error_log('[dbExecute] ' . $e->getMessage() . ' | SQL: ' . $sql);
        return false;
    }
}

/**
 * Begin a MySQLi transaction.
 */
function dbBegin(): void {
    getDBConnection()->begin_transaction();
}

/**
 * Commit the current transaction.
 */
function dbCommit(): void {
    getDBConnection()->commit();
}

/**
 * Roll back the current transaction.
 */
function dbRollback(): void {
    getDBConnection()->rollback();
}
