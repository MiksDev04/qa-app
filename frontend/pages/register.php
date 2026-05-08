<?php
// DATABASE CONNECTION
include '../../backend/config/database.php';

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $full = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    // Hash password (SECURE)
    $password_hash = password_hash($pass, PASSWORD_BCRYPT);

    try {
        $conn = getDBConnection();

        $sql = "INSERT INTO qa_users 
                (username, password_hash, full_name, email, role, is_active)
                VALUES (?, ?, ?, ?, ?, 1)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // bind_param types:
        // s = string
        $stmt->bind_param(
            "sssss",
            $user,
            $password_hash,
            $full,
            $email,
            $role
        );

        $stmt->execute();

        echo "<p style='color:green;'>User created successfully!</p>";

        $stmt->close();

    } catch (Throwable $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!-- HTML FORM -->
<!DOCTYPE html>
<html>
<head>
    <title>Create QA User</title>
</head>
<body>

<h2>Create User Account</h2>

<form method="POST">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Full Name:</label><br>
    <input type="text" name="full_name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Role:</label><br>
    <select name="role">
        <option value="admin">Admin</option>
        <option value="qa_officer">QA Officer</option>
        <option value="viewer">Viewer</option>
    </select><br><br>

    <button type="submit">Create User</button>
</form>

</body>
</html>