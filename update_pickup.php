<?php
session_start();
require 'config/db.php';
require 'includes/NotificationHelper.php';

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if request_id is provided
if (!isset($_POST['request_id'])) {
    $_SESSION['error'] = "Request ID is required.";
    header("Location: assigned_pickups.php");
    exit();
}

$request_id = (int)$_POST['request_id'];

// Start transaction for data integrity
mysqli_begin_transaction($conn);

try {
    // Get the current status and user_id before updating
    $check_query = "SELECT status, user_id FROM pickuprequests WHERE request_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt_check, "i", $request_id);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);
    $request_data = mysqli_fetch_assoc($check_result);
    $old_status = $request_data['status'];
    $user_id = $request_data['user_id'];
    mysqli_stmt_close($stmt_check);

    // Update the pickup request status to completed
    $update_query = "UPDATE pickuprequests SET status = 'completed' WHERE request_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);

    // Create notification for the resident
    if ($old_status !== 'completed') {
        $message = "Your pickup request has been marked as completed. Thank you for using our service!";
        $notificationHelper->createNotification($user_id, $request_id, 'completed', $message, false);
    }

    // Commit transaction
    mysqli_commit($conn);
    $_SESSION['success'] = "Pickup marked as completed successfully.";
} catch (Exception $e) {
    // Rollback if any error occurs
    mysqli_rollback($conn);
    $_SESSION['error'] = "Error updating pickup status.";
}

// Redirect back to assigned pickups page
header("Location: assigned_pickups.php");
exit(); 