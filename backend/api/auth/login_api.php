<?php
/**
 * Auth API Endpoint (MySQLi version)
 * POST /backend/api/auth/login.php
 */

session_start();

require_once '../../config/database.php';


header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

// ── Input ─────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

$username = sanitize($input['username'] ?? $_POST['username'] ?? '');
$password = $input['password'] ?? $_POST['password'] ?? '';

// ── Validation ────────────────────────────────────────
$errors = [];

if (empty($username)) {
    $errors['username'] = 'Username is required.';
}

if (empty($password)) {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors], 422);
}

if (strlen($username) > 50) {
    jsonResponse(false, 'Invalid credentials.', [], 401);
}

// ── Database lookup (MYSQLi) ──────────────────────────
try {

    $sql = "SELECT user_id, username, password_hash, full_name, email, role, is_active
            FROM qa_users
            WHERE username = ?
            LIMIT 1";

    $user = dbFetchOne($sql, 's', [$username]);

    // Fake hash to prevent timing attacks
    if (!$user) {
        password_verify('dummy', '$2y$10$dummyhashtopreventtimingattacks00000000000000');
        jsonResponse(false, 'Invalid username or password.', [], 401);
    }

    if (!$user['is_active']) {
        jsonResponse(false, 'Your account has been deactivated. Please contact the administrator.', [], 403);
    }

    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(false, 'Invalid username or password.', [], 401);
    }

    // ── Session Security ───────────────────────────────
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_at']  = time();

    jsonResponse(true, 'Login successful. Redirecting…', [
        'user' => [
            'user_id'   => $user['user_id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ],
        'redirect' => '../../frontend/pages/dashboard.php'
    ]);

} catch (Throwable $e) {
    error_log('[Auth Login] ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again later.', [], 500);
}