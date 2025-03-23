<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if (isset($_GET['building_id'])) {
    $building_id = intval($_GET['building_id']);

    // Check if there's already a resident in this building who can request pickups
    $sql = "SELECT COUNT(*) as count FROM users WHERE building_id = ? AND can_request_pickup = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $building_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // If no resident has the permission, the new user will be the first one
    $is_first = ($row['count'] == 0) ? true : false;

    echo json_encode(['is_first' => $is_first]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
exit;
