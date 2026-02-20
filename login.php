<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Vulnerable to SQL Injection
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $pdo->query($sql);
    $user = $result->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];

        // Vulnerable to Reflected XSS
        $redirectMessage = htmlspecialchars($_GET['message'] ?? 'Welcome back!');
        header("Location: profile.php?message=$redirectMessage");
        exit;
    } else {
        // Vulnerable to Stored XSS
        $message = "Invalid credentials! <script>alert('XSS Attack');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($message)) : ?>
            <!-- Vulnerable to Stored XSS -->
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <input type="submit" value="Login">
        </form>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
