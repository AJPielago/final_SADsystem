<?php
session_start();
require '../config/db.php';
require '../includes/NotificationHelper.php';

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

// Ensure session is properly set
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access: No user_id in session.");
}

// Fetch user role from the database
$user_id = $_SESSION['user_id'];
$sql_role = "SELECT role FROM users WHERE user_id = ?";
$stmt_role = $conn->prepare($sql_role);
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$result_role = $stmt_role->get_result();
$user = $result_role->fetch_assoc();
$stmt_role->close(); // Close statement

if (!$user || $user['role'] !== 'collector') {
    die("Unauthorized access: You do not have collector privileges.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reschedule_id = $_POST['reschedule_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$reschedule_id || !$action) {
        die("Error: Missing reschedule_id or action.");
    }

    // Start transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // Get request details including user information
        $check_query = "SELECT rr.*, u.user_id, u.full_name, u.email, ps.collection_date, ps.collection_time 
                       FROM reschedule_requests rr
                       JOIN users u ON rr.user_id = u.user_id
                       JOIN pickup_schedules ps ON rr.schedule_id = ps.schedule_id
                       WHERE rr.reschedule_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $reschedule_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Error: Request not found in database.");
        }

        $request = $result->fetch_assoc();
        $check_stmt->close();

        // Set status based on action
        $status = ($action === 'approve') ? 'Approved' : 'Denied';

        // Update the status
        $update_stmt = $conn->prepare("UPDATE reschedule_requests SET status = ? WHERE reschedule_id = ?");
        $update_stmt->bind_param("si", $status, $reschedule_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Create notification for the user
        $message = "";
        if ($action === 'approve') {
            $message = "Your reschedule request for pickup on " . date('F j, Y', strtotime($request['collection_date'])) . 
                      " at " . date('g:i A', strtotime($request['collection_time'])) . " has been approved.";
        } else {
            $message = "Your reschedule request for pickup on " . date('F j, Y', strtotime($request['collection_date'])) . 
                      " at " . date('g:i A', strtotime($request['collection_time'])) . " has been denied.";
        }

        $notificationHelper->createNotification(
            $request['user_id'],
            $request['schedule_id'],
            'rescheduled',
            $message,
            false
        );

        // Commit transaction
        mysqli_commit($conn);
        header("Location: ../admin_dashboard.php?success=$action");
        exit();
    } catch (Exception $e) {
        // Rollback if any error occurs
        mysqli_rollback($conn);
        die("Error: " . $e->getMessage());
    }
}
?>
