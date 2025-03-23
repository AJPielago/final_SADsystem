<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Ensure the user is an admin
$user_id = $_SESSION['user_id'];
$role_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$result = $role_stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../admin_dashboard.php?error=No request ID provided.");
    exit();
}

$id = intval($_GET['id']);

// Check if request exists
$check_stmt = $conn->prepare("SELECT status FROM pickuprequests WHERE request_id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../admin_dashboard.php?error=Request not found.");
    exit();
}

$row = $result->fetch_assoc();

// Prevent rejecting already rejected requests
if ($row['status'] === 'rejected') {
    header("Location: ../admin_dashboard.php?error=Request already rejected.");
    exit();
}

// Reject the request
$stmt = $conn->prepare("UPDATE pickuprequests SET status = 'rejected' WHERE request_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: ../admin_dashboard.php?success=Request rejected successfully.");
} else {
    header("Location: ../admin_dashboard.php?error=Failed to reject request.");
}
exit();
?>
