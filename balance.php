<?php
session_start();
require 'config.php';
include 'header.php';

$userId = $_SESSION['user_id'];

// Vulnerable to Host Header Poisoning
$hostHeader = $_SERVER['HTTP_HOST'] ?? 'default.host';
$redirectUrl = "http://$hostHeader/redirect.php?user_id=" . urlencode($userId);

// Fetch user balance
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Open URL Redirection
if (isset($_GET['redirect'])) {
    // Redirect to an arbitrary URL specified by the attacker
    $url = $_GET['redirect'];
    header("Location: $url");
    exit;
}

// HTTP Response Splitting
header("Content-Type: text/html; charset=UTF-8");
header("X-Custom-Header: Value\r\nAnother-Header: Attacker-Value");

// Suppress all errors and warnings
error_reporting(0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Balance</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Account Balance</h1>
        <p>Your balance: $<?php echo htmlspecialchars($user['balance']); ?></p>
        <!-- Open URL Redirection -->
        <a href="<?php echo htmlspecialchars($redirectUrl); ?>">Redirect</a>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
