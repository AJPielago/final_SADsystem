<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['collector_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];

    $update_sql = "UPDATE pickup_schedules SET status = 'Completed' WHERE schedule_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $schedule_id);

    if ($stmt->execute()) {
        header("Location: assigned_pickups.php?success=Request marked as completed.");
    } else {
        header("Location: assigned_pickups.php?error=Failed to update status.");
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: assigned_pickups.php");
    exit();
}
