<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form is submitted and the necessary fields are present
if (isset($_POST['reschedule_id']) && isset($_POST['new_date']) && isset($_POST['new_time'])) {
    // Loop through the reschedule requests
    foreach ($_POST['reschedule_id'] as $index => $reschedule_id) {
        $new_date = $_POST['new_date'][$reschedule_id];
        $new_time = $_POST['new_time'][$reschedule_id];

        // You can retrieve current schedule info and update rescheduled_pickups accordingly
        $query = "
            SELECT ps.collection_day, ps.collection_time
            FROM pickup_schedules ps
            JOIN reschedule_requests rr ON ps.schedule_id = rr.schedule_id
            WHERE rr.reschedule_id = ? AND rr.user_id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $reschedule_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $old_schedule_day = $row['collection_day'];
        $old_schedule_time = $row['collection_time'];

        // Insert the updated reschedule info into `rescheduled_pickups` table
        $insert_query = "
            INSERT INTO rescheduled_pickups (request_id, schedule_id, collector_reason, reschedule_status, old_schedule_day, new_schedule_day, request_date)
            VALUES (?, ?, ?, 'Pending', ?, ?, NOW())
        ";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $reschedule_id, $row['schedule_id'], $_POST['reason'], $old_schedule_day, $new_date);
        $insert_stmt->execute();
    }

    header("Location: user_reschedule.php?success=update");
    exit();
} else {
    header("Location: user_reschedule.php?error=missing_data");
    exit();
}
