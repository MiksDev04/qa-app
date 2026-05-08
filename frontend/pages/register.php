<?php
// DATABASE CONNECTION SETTINGS

include '../../backend/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = $_POST['username'];
    $pass = $_POST['password'];
    $full = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Hash password (SECURE - recommended)
    $password_hash = password_hash($pass, PASSWORD_BCRYPT);

    $sql = "INSERT INTO qa_users 
            (username, password_hash, full_name, email, role, is_active)
            VALUES 
            (:username, :password_hash, :full_name, :email, :role, 1)";

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':username' => $user,
            ':password_hash' => $password_hash,
            ':full_name' => $full,
            ':email' => $email,
            ':role' => $role
        ]);

        echo "<p style='color:green;'>User created successfully!</p>";

    } catch (PDOException $e) {
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