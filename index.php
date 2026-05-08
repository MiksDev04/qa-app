

<!-- Entry Point -->
<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: frontend/pages/login.php');
    exit;
}
?>