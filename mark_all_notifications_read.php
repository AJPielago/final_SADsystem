<?php
session_start();
require 'config/db.php';
require 'includes/NotificationHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$notificationHelper = new NotificationHelper($conn);
$notificationHelper->markAllAsRead($_SESSION['user_id']);

echo json_encode(['success' => true]); 