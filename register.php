<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Vulnerable to SQL Injection
    $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '" . password_hash($password, PASSWORD_BCRYPT) . "', '$email')";
    try {
        $pdo->query($sql); // Directly execute SQL query
        // Redirect to login page with vulnerable query parameter
        header('Location: index.php?message=Registration successful! <script>alert("XSS Attack");</script>');
        exit;
    } catch (Exception $e) {
        // Vulnerable to stored XSS
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <?php if (isset($message)) : ?>
            <!-- Vulnerable to stored XSS -->
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email">
            <input type="submit" value="Register">
        </form>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
