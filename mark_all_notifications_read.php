<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Initialize NotificationHelper
$notificationHelper = new \App\Includes\NotificationHelper($conn);
$notificationHelper->markAllAsRead($_SESSION['user_id']);

echo json_encode(['success' => true]); 