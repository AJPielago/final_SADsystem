<!-- filepath: c:\xamppSAD\htdocs\SADsystem\controllers\approve_pickup.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php'; // Include your database connection file
require_once '../includes/NotificationHelper.php'; // Include NotificationHelper

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $status = 'approved';

    // Start transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // Get user information for notification
        $user_query = "SELECT u.user_id, u.full_name, b.building_name 
                      FROM pickuprequests pr 
                      JOIN users u ON pr.user_id = u.user_id 
                      JOIN buildings b ON u.building_id = b.building_id 
                      WHERE pr.request_id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user_data = $user_result->fetch_assoc();
        $stmt->close();

        // Update the status of the pickup request
        $update_stmt = $conn->prepare("UPDATE pickuprequests SET status = ? WHERE request_id = ?");
        if ($update_stmt === false) {
            throw new Exception('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $update_stmt->bind_param("si", $status, $request_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Create notification for the user
        $message = "Your pickup request has been approved. Collection is scheduled for " . date('F j, Y', strtotime('+1 day')) . " at 7:00 AM.";
        $notificationHelper->createNotification($user_data['user_id'], $request_id, 'approved', $message);

        // Commit transaction
        mysqli_commit($conn);
        header("Location: ../admin_dashboard.php?success=Pickup request approved successfully.");
    } catch (Exception $e) {
        // Rollback if any error occurs
        mysqli_rollback($conn);
        header("Location: ../admin_dashboard.php?error=Failed to approve pickup request. " . htmlspecialchars($e->getMessage()));
    }

    $conn->close();
}
?>