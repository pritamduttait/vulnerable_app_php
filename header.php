<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="navbar">
    <a href="profile.php">Profile</a>
    <a href="upload.php">Upload</a>
    <a href="add_funds.php">Add Funds</a>
    <a href="transfer.php">Transfer Funds</a>
    <a href="balance.php">Account Balance</a>
    <a href="change_password.php">Change Password</a>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<style>
.navbar {
    background-color: #333;
    overflow: hidden;
}

.navbar a {
    float: left;
    display: block;
    color: #f2f2f2;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
    font-size: 17px;
}

.navbar a:hover {
    background-color: #ddd;
    color: black;
}

.logout-btn {
    float: right;
    background-color: #ff4d4d;
    color: white;
}

.logout-btn:hover {
    background-color: #ff6666;
}
</style>
