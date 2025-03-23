<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/header.php';
require '../config/db.php';


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $image = "";

    // Handle image upload
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "uploads/donations/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = $target_dir . basename($_FILES["image"]["name"]);
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image)) {
            die("Image upload failed!");
        }
    }

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Insert into database
    $sql = "INSERT INTO donations (user_id, item_name, item_description, category, image, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'available', NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Statement preparation failed: " . $conn->error);
    }

    $stmt->bind_param("issss", $user_id, $item_name, $description, $category, $image);
    if (!$stmt->execute()) {
        die("Execution failed: " . $stmt->error);
    }

    // Success message
    echo "Donation successfully submitted!";
    header("Location: ../donations.php?success=Donation submitted successfully!");
    exit();
}

?>
