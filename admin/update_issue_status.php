<?php
session_start();
require '../config/db.php';

// Validate issue_id
if (!isset($_POST['issue_id']) || !is_numeric($_POST['issue_id'])) {
    die('Invalid issue ID');
}

// Validate status
if (!isset($_POST['status']) || !in_array($_POST['status'], ['pending', 'resolved'])) {
    die('Invalid status');
}

$issue_id = intval($_POST['issue_id']);
$status = $_POST['status'];

// Prepare SQL statement
$sql = "UPDATE issues SET status = ? WHERE issue_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

// Bind parameters
if (!$stmt->bind_param('si', $status, $issue_id)) {
    die("Binding parameters failed: " . htmlspecialchars($stmt->error));
}

// Execute the statement
if ($stmt->execute()) {
    // Redirect back to the previous page
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    die("Execute failed: " . htmlspecialchars($stmt->error));
}

// Close statement and connection
$stmt->close();
$conn->close();
?>