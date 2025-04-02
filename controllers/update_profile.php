<?php
session_start();
require_once '../config/db.php';

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

// Handle profile picture upload
$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        header("Location: ../profile.php?error=Invalid file type. Only JPG, PNG, and GIF are allowed.");
        exit();
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        header("Location: ../profile.php?error=File too large. Maximum size is 2MB.");
        exit();
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/profile_pictures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_') . '.' . $file_extension;
    $target_path = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $profile_picture = 'uploads/profile_pictures/' . $filename;
    } else {
        header("Location: ../profile.php?error=Failed to upload profile picture.");
        exit();
    }
}

// Update user profile
$sql = "UPDATE users SET full_name = ?, phone_number = ?, building_id = ?";
$params = [$full_name, $phone_number, $building_id];
$types = "ssi";

// Add profile picture to update if uploaded
if ($profile_picture) {
    $sql .= ", profile_picture = ?";
    $params[] = $profile_picture;
    $types .= "s";
}

$sql .= " WHERE user_id = ?";
$params[] = $user_id;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

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

