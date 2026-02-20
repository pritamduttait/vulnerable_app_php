<?php
session_start();
require 'config.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $userId = $_SESSION['user_id'];
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($fileName);

    // Vulnerable to Unrestricted File Upload
    if (move_uploaded_file($fileTmpName, $uploadFile)) {
        // Vulnerable to Path Traversal
        // Accept any file type and name
        $stmt = $pdo->prepare("INSERT INTO files (user_id, filename) VALUES (?, ?)");
        $stmt->execute([$userId, $fileName]);

        // Vulnerable to Reflected XSS
        $message = "File uploaded successfully! <script>alert('XSS Attack');</script>";
    } else {
        // Vulnerable to Stored XSS
        $message = "File upload failed! <script>alert('XSS Attack');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Upload File</h1>
        <?php if (isset($message)) : ?>
            <!-- Vulnerable to Stored XSS -->
            <p class="<?php echo strpos($message, 'failed') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="file">Choose file:</label>
            <input type="file" name="file" id="file" required>
            <input type="submit" value="Upload">
        </form>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
