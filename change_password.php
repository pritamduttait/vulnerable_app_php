<?php
session_start();
require 'config.php';
include 'header.php'; // Include header with the logout button

// No authentication check, assuming any logged-in user can access this page
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch current password from the database (vulnerable to SQL Injection)
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = '$userId'"); // SQL Injection vulnerability here
    $stmt->execute();
    $user = $stmt->fetch();

    // Verify current password
    if ($user && $user['password'] === $currentPassword) {
        if ($newPassword === $confirmPassword) {
            // Update the password in the database (storing raw password)
            $stmt = $pdo->prepare("UPDATE users SET password = '$newPassword' WHERE id = '$userId'"); // SQL Injection vulnerability here
            if ($stmt->execute()) {
                $message = "Password updated successfully!"; // Potential XSS vulnerability if message is not sanitized
            } else {
                $message = "Error updating password."; // Potential XSS vulnerability if message is not sanitized
            }
        } else {
            $message = "New passwords do not match."; // Potential XSS vulnerability if message is not sanitized
        }
    } else {
        $message = "Current password is incorrect."; // Potential XSS vulnerability if message is not sanitized
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Change Password</h1>
            <?php if (isset($message)): ?>
                <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; // XSS vulnerability here ?>
                </p>
            <?php endif; ?>

            <form method="post" action="change_password.php">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <!-- Missing CSRF protection -->
                <input type="submit" value="Change Password">
            </form>
        </div>
    </div>
</body>
</html>
