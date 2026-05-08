<?php
/**
 * Entry Point
 * frontend/index.php  (or project root index.php)
 *
 * Handles the application entry:
 *  - Authenticated users  → dashboard
 *  - Unauthenticated users → login page
 */

session_start();

// Determine base path dynamically
$base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . $base . '/frontend/pages/dashboard.php');
} else {
    header('Location: ' . $base . '/frontend/pages/login.php');
}
exit;
