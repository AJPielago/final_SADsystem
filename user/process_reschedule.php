<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_id'], $_POST['action'])) {
    $reschedule_id = intval($_POST['reschedule_id']);
    $action = $_POST['action'];

    if ($action === 'acknowledge') {
        $query = "UPDATE rescheduled_pickups SET reschedule_status = 'Acknowledged' WHERE reschedule_id = ?";
    } elseif ($action === 'dispute') {
        $query = "UPDATE rescheduled_pickups SET reschedule_status = 'Disputed' WHERE reschedule_id = ?";
    } else {
        header("Location: manage_reschedules.php?error=invalid_action");
        exit();
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reschedule_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: manage_reschedules.php?success=$action");
    exit();
} else {
    header("Location: manage_reschedules.php?error=invalid_request");
    exit();
}
?>
