<?php
/**
 * Database Configuration — MySQLi (Railway Ready)
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
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) continue;

        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

/* Load env from project root */
loadEnv(__DIR__ . '/../.env');


/* -------------------------------------------------
   DB CONFIG (Railway uses env variables)
-------------------------------------------------- */
function envFirst(array $keys, $default = '') {
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }
    return $default;
}

$mysqlUrl = envFirst(['MYSQL_URL', 'MYSQL_PUBLIC_URL'], '');
$mysqlUrlParts = $mysqlUrl !== '' ? parse_url($mysqlUrl) : [];

define('DB_HOST', envFirst([
    'DB_HOST',
    'MYSQLHOST',
    'MYSQL_HOST'
], $mysqlUrlParts['host'] ?? 'localhost'));

define('DB_USER', envFirst([
    'DB_USER',
    'MYSQLUSER',
    'MYSQL_USER'
], $mysqlUrlParts['user'] ?? 'root'));

define('DB_PASS', envFirst([
    'DB_PASS',
    'MYSQLPASSWORD',
    'MYSQL_PASSWORD',
    'MYSQL_ROOT_PASSWORD'
], $mysqlUrlParts['pass'] ?? ''));

define('DB_NAME', envFirst([
    'DB_NAME',
    'MYSQLDATABASE',
    'MYSQL_DATABASE'
], isset($mysqlUrlParts['path']) ? ltrim($mysqlUrlParts['path'], '/') : ''));

define('DB_PORT', (int) envFirst([
    'DB_PORT',
    'MYSQLPORT',
    'MYSQL_PORT'
], $mysqlUrlParts['port'] ?? 3306));

define('DB_CHARSET', envFirst(['DB_CHARSET'], 'utf8mb4'));


/* -------------------------------------------------
   LOGGING (optional debug)
-------------------------------------------------- */
error_log("Database config loaded: " . DB_HOST . ":" . DB_PORT);


/* -------------------------------------------------
   DB CONNECTION (Singleton)
-------------------------------------------------- */
function getDBConnection(): mysqli {

    echo 'Connection: ' . DB_HOST . ':' . DB_PORT . '/' . DB_NAME . '.' . DB_USER . "\n";
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

            jsonResponse(false, 'Database connection failed.', [], 500);
        }
    }

    return $conn;
}


/* -------------------------------------------------
   JSON RESPONSE HELPER
-------------------------------------------------- */
function jsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* -------------------------------------------------
   SANITIZE (display only)
-------------------------------------------------- */
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}


/* -------------------------------------------------
   VALIDATION
-------------------------------------------------- */
function validateRequired(array $fields, array $source): array {
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($source[$field]) || trim((string)$source[$field]) === '') {
            $label = ucwords(str_replace('_', ' ', $field));
            $errors[$field] = "$label is required.";
        }
    }

    return $errors;
}


/* -------------------------------------------------
   FETCH ALL
-------------------------------------------------- */
function dbFetchAll(string $sql, string $types = '', array $params = []): array {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $rows = [];

        // Use mysqlnd path when available.
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            if ($result !== false) {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                $stmt->close();
                return $rows;
            }
        }

        // Fallback for environments where get_result is unavailable.
        $meta = $stmt->result_metadata();
        if ($meta === false) {
            $stmt->close();
            return [];
        }

        $fields = [];
        $row = [];
        $bind = [];

        while ($field = $meta->fetch_field()) {
            $fields[] = $field->name;
            $row[$field->name] = null;
            $bind[] = &$row[$field->name];
        }

        call_user_func_array([$stmt, 'bind_result'], $bind);

        while ($stmt->fetch()) {
            $record = [];
            foreach ($fields as $name) {
                $record[$name] = $row[$name];
            }
            $rows[] = $record;
        }

        $meta->free();
        $stmt->close();
        return $rows;

    } catch (Throwable $e) {
        error_log('[dbFetchAll] ' . $e->getMessage());
        return [];
    }
}


/* -------------------------------------------------
   FETCH ONE
-------------------------------------------------- */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array {
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}


/* -------------------------------------------------
   EXECUTE (INSERT / UPDATE / DELETE)
-------------------------------------------------- */
function dbExecute(string $sql, string $types = '', array $params = []) {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare($sql);

        if ($types && $params) {
            $bind = [$types];

            foreach ($params as &$param) {
                $bind[] = &$param;
            }

            call_user_func_array([$stmt, 'bind_param'], $bind);
        }

        $stmt->execute();

        if (strpos(ltrim(strtoupper($sql)), 'INSERT') === 0) {
            return (int)$conn->insert_id;
        }

        return (int)$stmt->affected_rows;

    } catch (Throwable $e) {
        error_log('[dbExecute] ' . $e->getMessage());
        return false;
    }
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