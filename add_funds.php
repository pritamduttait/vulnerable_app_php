<?php
session_start();
require 'config.php';
include 'header.php'; // Include the header with the logout button

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];

    // Vulnerable to SQL Injection
    // Construct SQL query directly with user input
    $sql = "SELECT balance FROM users WHERE id = '$userId'";
    $result = $pdo->query($sql);
    $user = $result->fetch();

    if ($user) {
        // Vulnerable to Improper Input Validation
        if (!is_numeric($amount) || $amount <= 0) {
            $message = "Please enter a valid amount.";
        } else {
            // Update balance without proper validation
            $newBalance = $user['balance'] + $amount;
            $sql = "UPDATE users SET balance = '$newBalance' WHERE id = '$userId'";
            if ($pdo->query($sql)) {
                // Vulnerable to Reflected XSS
                $message = "Funds added successfully! <script>alert('XSS Attack');</script>";
            } else {
                $message = "Error adding funds. Please try again.";
            }
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Funds</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Add Funds</h1>
            <?php if (isset($message)): ?>
                <!-- Vulnerable to Stored XSS -->
                <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <form method="post" action="add_funds.php">
                <label for="amount">Enter Amount to Add:</label>
                <input type="number" name="amount" id="amount" step="0.01" required>

                <input type="submit" value="Add Funds">
            </form>
        </div>
    </div>
</body>
</html>
