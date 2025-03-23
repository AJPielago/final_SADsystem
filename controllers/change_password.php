<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['user_id']) || $_POST['user_id'] != $_SESSION['user_id']) {
    header("Location: ../profile.php?error=Unauthorized access");
    exit();
}

$user_id = $_SESSION['user_id'];
$old_password = trim($_POST['old_password']);
$new_password = trim($_POST['new_password']);
$confirm_password = trim($_POST['confirm_password']);

if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: ../profile.php?error=All fields are required.");
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: ../profile.php?error=New passwords do not match.");
    exit();
}

if (strlen($new_password) < 6) {
    header("Location: ../profile.php?error=Password must be at least 6 characters long.");
    exit();
}

// Fetch the current password hash
$sql = "SELECT password FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($old_password, $user['password'])) {
    header("Location: ../profile.php?error=Incorrect old password.");
    exit();
}

// Hash and update the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$update_sql = "UPDATE Users SET password=? WHERE user_id=?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $hashed_password, $user_id);

if ($update_stmt->execute()) {
    header("Location: ../profile.php?success=Password changed successfully.");
} else {
    header("Location: ../profile.php?error=Failed to update password.");
}

$stmt->close();
$update_stmt->close();
$conn->close();
exit();
