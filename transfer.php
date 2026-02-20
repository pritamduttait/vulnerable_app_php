<?php
session_start();
require 'config.php';
include 'header.php'; // Include header with logout button

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle transfer form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient = $_POST['recipient'];
    $amount = $_POST['amount'];
    $note = $_POST['note'];

    // Vulnerable to SQL Injection
    $sql = "SELECT id, balance FROM users WHERE email = '$recipient'";
    $result = $pdo->query($sql);
    $recipientUser = $result->fetch();

    if (!$recipientUser) {
        $message = "Recipient not found.";
    } elseif ($amount <= 0) {
        $message = "Invalid transfer amount.";
    } else {
        // Check if sender has enough balance
        $sql = "SELECT balance FROM users WHERE id = '$userId'";
        $result = $pdo->query($sql);
        $sender = $result->fetch();

        if ($sender['balance'] < $amount) {
            $message = "Insufficient balance.";
        } else {
            // Deduct amount from sender
            $newSenderBalance = $sender['balance'] - $amount;
            $sql = "UPDATE users SET balance = '$newSenderBalance' WHERE id = '$userId'";
            $pdo->query($sql);

            // Add amount to recipient
            $newRecipientBalance = $recipientUser['balance'] + $amount;
            $sql = "UPDATE users SET balance = '$newRecipientBalance' WHERE id = '{$recipientUser['id']}'";
            $pdo->query($sql);

            // Insert transfer record into the 'transfers' table
            // Vulnerable to XSS in note
            $sql = "INSERT INTO transfers (sender_id, recipient_id, amount, note) VALUES ('$userId', '{$recipientUser['id']}', '$amount', '$note')";
            if ($pdo->query($sql)) {
                // Vulnerable to Reflected XSS
                $message = "Transfer successful! <script>alert('XSS Attack');</script>";
            } else {
                // Vulnerable to Stored XSS
                $message = "Error processing the transfer. <script>alert('XSS Attack');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Funds</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Transfer Funds</h1>
            
            <!-- Success/Error message -->
            <?php if (isset($message)): ?>
                <!-- Vulnerable to Stored XSS -->
                <p class="<?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <!-- Transfer form -->
            <form method="post" action="transfer.php">
                <label for="recipient">Recipient Email:</label>
                <input type="email" name="recipient" id="recipient" required>

                <label for="amount">Amount:</label>
                <input type="number" name="amount" id="amount" step="0.01" required>

                <!-- New Note Field -->
                <label for="note">Note (Optional):</label>
                <textarea name="note" id="note" rows="4" placeholder="Add a note for the recipient (optional)"></textarea>

                <input type="submit" value="Transfer">
            </form>

            <!-- Export Button -->
            <form method="get" action="export.php" style="margin-top: 20px;">
                <!-- Vulnerable to IDOR (Insecure Direct Object References) -->
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <input type="submit" value="Export Transactions" class="export-button">
            </form>
        </div>
    </div>
</body>
</html>
