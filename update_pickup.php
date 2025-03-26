<?php
session_start();
require_once '../config/db.php';

// Validate user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: ../login.php");
    exit();
}

// Validate form submission method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../profile.php");
    exit();
}

// Sanitize and validate input
$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$building_id = intval($_POST['building_id'] ?? 0);

// Basic validation
if (empty($full_name) || empty($phone_number) || $building_id <= 0) {
    $_SESSION['error'] = "All fields are required";
    header("Location: ../profile.php");
    exit();
}

// Prepare and execute update query
$sql = "UPDATE users SET full_name = ?, phone_number = ?, building_id = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $full_name, $phone_number, $building_id, $user_id);

try {
    // Execute update
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update profile";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
} finally {
    // Close statement
    $stmt->close();
}

// Redirect back to profile page
header("Location: ../profile.php");
exit();