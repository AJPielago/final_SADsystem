<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];

    // Fetch the request_id based on schedule_id
    $sql = "SELECT request_id, user_id FROM pickup_schedules WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pickup = $result->fetch_assoc();
        $user_id = $pickup['user_id'];

        // Update the pickup request to mark it as completed
        $updateSql = "UPDATE pickuprequests 
                      SET status = 'Completed' 
                      WHERE request_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $pickup['request_id']);
        $updateStmt->execute();

        // Award points to the user (15 points per completed pickup)
        $pointsSql = "UPDATE users SET points = points + 15 WHERE user_id = ?";
        $pointsStmt = $conn->prepare($pointsSql);
        $pointsStmt->bind_param("i", $user_id);
        $pointsStmt->execute();

        // Check for success
        if ($updateStmt->affected_rows > 0 && $pointsStmt->affected_rows > 0) {
            // Redirect with success message
            header("Location: assigned_pickups.php?success=completed");
        } else {
            // Redirect with error message
            header("Location: assigned_pickups.php?error=completion_failed");
        }

        // Close the statements
        $updateStmt->close();
        $pointsStmt->close();
    } else {
        // If no matching schedule_id found
        header("Location: assigned_pickups.php?error=invalid_schedule_id");
    }

    $stmt->close();
    $conn->close();
}
?>
