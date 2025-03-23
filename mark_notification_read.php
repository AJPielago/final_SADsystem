<?php
session_start();
require 'config/db.php';
require 'includes/NotificationHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$notificationHelper = new NotificationHelper($conn);
$notificationHelper->markAsRead($_POST['notification_id'], $_SESSION['user_id']);

echo json_encode(['success' => true]); 