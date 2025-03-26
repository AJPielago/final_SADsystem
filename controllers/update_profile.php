<?php
session_start();
require '../config/db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php?error=Invalid%20request%20method");
    exit();
}

// Sanitize and validate input
$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$building_id = $_POST['building_id'] ?? '';

// Validate inputs
$errors = [];

if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($phone_number)) {
    $errors[] = "Phone number is required";
}

if (empty($building_id)) {
    $errors[] = "Building selection is required";
}

// If there are validation errors, redirect back with error messages
if (!empty($errors)) {
    $error_message = urlencode(implode(', ', $errors));
    header("Location: ../profile.php?error={$error_message}");
    exit();
}

// Prepare and execute the update statement
$sql = "UPDATE users 
        SET full_name = ?, 
            phone_number = ?, 
            building_id = ? 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $full_name, $phone_number, $building_id, $user_id);

try {
    // Execute the update
    if ($stmt->execute()) {
        // Redirect to profile page with success message
        header("Location: ../profile.php?success=1");
        exit();
    } else {
        // Database error
        throw new Exception("Database update failed");
    }
} catch (Exception $e) {
    // Log the error (in a real-world scenario, you'd use proper logging)
    error_log($e->getMessage());
    
    // Redirect with a generic error message
    header("Location: ../profile.php?error=Update%20failed");
    exit();
} finally {
    // Close the statement
    $stmt->close();
    // Close the database connection
    $conn->close();
}