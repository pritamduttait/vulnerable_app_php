<?php
session_start();
require 'config.php';

// No user authentication check for exporting
// Allow any user to export transactions by manipulating the user_id parameter

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    die("User ID is required");
}

// Vulnerable to SQL Injection
$sql = "SELECT t.id, u1.email AS sender_email, u2.email AS recipient_email, t.amount, t.note, t.created_at 
        FROM transfers t 
        JOIN users u1 ON t.sender_id = u1.id
        JOIN users u2 ON t.recipient_id = u2.id
        WHERE t.sender_id = '$userId' OR t.recipient_id = '$userId'
        ORDER BY t.created_at DESC";

$transactions = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Create CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=transactions.csv');

// Vulnerable to XSS in CSV content
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Sender Email', 'Recipient Email', 'Amount', 'Note', 'Date']);

foreach ($transactions as $transaction) {
    // Note field is not sanitized, so it can contain XSS payloads
    fputcsv($output, [
        $transaction['id'],
        $transaction['sender_email'],
        $transaction['recipient_email'],
        $transaction['amount'],
        $transaction['note'],  // Vulnerable to XSS
        $transaction['created_at']
    ]);
}

fclose($output);
exit;
?>
