<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'config/db.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $image = "";
    
    // Validate inputs
    if (empty($item_name) || empty($description) || empty($category)) {
        header("Location: donate.php?error=All fields are required");
        exit();
    }
    
    // Handle image upload
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "uploads/donations/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename to prevent overwriting
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        
        // Check file size (limit to 5MB)
        if ($_FILES["image"]["size"] > 5000000) {
            header("Location: donate.php?error=Image is too large (max 5MB)");
            exit();
        }
        
        // Allow certain file formats
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            header("Location: donate.php?error=Only JPG, JPEG, PNG & GIF files are allowed");
            exit();
        }
        
        // Try to upload the file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $target_file;
        } else {
            header("Location: donate.php?error=Error uploading image");
            exit();
        }
    }
    
    // Add timestamp for donation date
    $donation_date = date("Y-m-d H:i:s");
    
    // Insert into database
    $sql = "INSERT INTO donations (user_id, item_name, description, category, image, donation_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $item_name, $description, $category, $image, $donation_date);
    
    if ($stmt->execute()) {
        // Success! Redirect back to donate page with success message
        header("Location: donations.php?success=Donation submitted successfully! An admin will review your donation.");
        exit();
    } else {
        // Error
        header("Location: donations.php?error=Error submitting donation: " . $conn->error);
        exit();
    }
} else {
    // If someone tries to access this file directly without submitting the form
    header("Location: donate.php");
    exit();
}
?>