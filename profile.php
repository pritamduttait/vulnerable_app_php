<?php
session_start();
require 'config.php';
include 'header.php'; // Include header with logout button

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT email, profile_picture, custom_link, serialized_data FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $profilePicture = $_FILES['profile_picture'];
    $customLink = $_POST['custom_link'];
    $url = $_POST['url'] ?? null;
    $normalData = $_POST['normal_data'] ?? null;
    $serializedData = $_POST['serialized_data'] ?? null;

    $uploadOk = true;
    $profilePictureName = $user['profile_picture'];

    // Handle profile picture upload
    if ($profilePicture['size'] > 0) {
        $targetDir = "uploads/";
        $profilePictureName = basename($profilePicture["name"]);
        $targetFile = $targetDir . $profilePictureName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if (move_uploaded_file($profilePicture["tmp_name"], $targetFile)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$profilePictureName, $userId]);
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $uploadOk = false;
        }
    }

    // Update email (vulnerable to XSS)
    if ($uploadOk) {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        if ($stmt->execute([$email, $userId])) {
            $message = "Profile updated successfully!";
        } else {
            $message = "Error updating profile.";
        }
    }

    // Log Injection: Append log entry to a file
    $logFile = 'logs/user_actions.log';
    $logMessage = "User $userId updated profile. Email: $email, Link: $customLink\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);

    // Link Injection: Allow user to specify a custom link
    $stmt = $pdo->prepare("UPDATE users SET custom_link = ? WHERE id = ?");
    if ($stmt->execute([$customLink, $userId])) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }

    // Handle SSRF
    if ($url) {
        $response = file_get_contents($url);
        $ssrfMessage = htmlspecialchars($response);
    }

    // Handle Insecure Deserialization
    if ($serializedData) {
        // Debugging: Output received serialized data
        $debugSerializedData = htmlspecialchars($serializedData);
        error_log("Received Serialized Data: $debugSerializedData"); // Log for debugging

        // Properly unserialize and serialize data before storing
        $data = @unserialize($serializedData); // Suppress errors with @
        if ($data !== false && $data !== null) {
            $newSerializedData = serialize($data);

            // Debugging: Output serialized data to be stored
            $debugNewSerializedData = htmlspecialchars($newSerializedData);
            error_log("Serialized Data to Store: $debugNewSerializedData"); // Log for debugging

            // Ensure SQL execution is correct
            $stmt = $pdo->prepare("UPDATE users SET serialized_data = ? WHERE id = ?");
            if ($stmt->execute([$newSerializedData, $userId])) {
                $deserializationMessage = htmlspecialchars(print_r($data, true));
            } else {
                $deserializationMessage = "Error updating serialized data.";
                error_log("SQL Error: " . implode(", ", $stmt->errorInfo())); // Log SQL errors
            }
        } else {
            $deserializationMessage = "Failed to unserialize data.";
        }
    }
}

// Fetch updated user data
$stmt = $pdo->prepare("SELECT email, profile_picture, custom_link, serialized_data FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Local File Inclusion (LFI): User can specify a file to include
$fileToInclude = $_GET['include'] ?? null;
if ($fileToInclude) {
    include($fileToInclude); // LFI vulnerability
}

// Remote File Inclusion (RFI): User can specify a URL to include
$remoteFileToInclude = $_GET['remote_include'] ?? null;
if ($remoteFileToInclude) {
    include($remoteFileToInclude); // RFI vulnerability
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Your Profile</h1>

            <!-- Show profile picture -->
            <div class="profile-picture">
                <?php if ($user['profile_picture']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" width="150" height="150"> <!-- XSS vulnerability -->
                <?php else: ?>
                    <img src="default-profile.png" alt="Default Profile Picture" width="150" height="150">
                <?php endif; ?>
            </div>

            <!-- Success/Error message -->
            <?php if (isset($message)): ?>
                <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?> <!-- XSS vulnerability -->
                </p>
            <?php endif; ?>

            <!-- Display SSRF, XXE, and Deserialization results -->
            <?php if (isset($ssrfMessage)): ?>
                <div class="response"><?php echo $ssrfMessage; ?></div>
            <?php endif; ?>

            <?php if (isset($xxeMessage)): ?>
                <div class="response"><?php echo $xxeMessage; ?></div>
            <?php endif; ?>

            <?php if (isset($deserializationMessage)): ?>
                <div class="response"><?php echo $deserializationMessage; ?></div>
            <?php endif; ?>

            <!-- Show custom link -->
<div class="custom-link">
    <label>Your Custom Link:</label>
    <?php if ($user['custom_link']): ?>
        <a href="<?php echo htmlspecialchars($user['custom_link']); ?>" target="_blank">
            <?php echo htmlspecialchars($user['custom_link']); ?> <!-- XSS vulnerability -->
        </a>
    <?php else: ?>
        <p>No custom link set.</p>
    <?php endif; ?>
</div>
            <!-- Profile update form -->
            <form method="post" enctype="multipart/form-data" action="profile.php">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required> <!-- XSS vulnerability -->

                <label for="profile_picture">Upload Profile Picture:</label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*">

                <label for="custom_link">Custom Link:</label>
                <input type="text" name="custom_link" id="custom_link" value="<?php echo htmlspecialchars($user['custom_link']); ?>">

                <label for="url">SSRF Example - Enter URL to Fetch:</label>
                <input type="text" name="url" id="url" placeholder="http://example.com">

                <label for="normal_data">Normal Data (Converted to XML):</label>
                <input type="text" name="normal_data" id="normal_data" placeholder="Enter normal data here">

                <label for="serialized_data">Insecure Deserialization - Enter Serialized Data:</label>
                <textarea name="serialized_data" id="serialized_data" rows="10" placeholder="Enter serialized data here"></textarea>

                <input type="submit" value="Update Profile">
            </form>
        </div>
    </div>
</body>
</html>
