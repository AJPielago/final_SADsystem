<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$approved_pickups = [];

$sql = "SELECT pr.request_id, pr.created_at, 
               b.building_name, pr.latitude, pr.longitude, u.full_name AS resident_name
        FROM pickuprequests pr
        JOIN users u ON pr.user_id = u.user_id
        JOIN buildings b ON pr.building_id = b.building_id
        WHERE DATE(pr.created_at) = CURDATE()
        AND pr.status = 'approved'
        AND pr.request_id NOT IN (SELECT request_id FROM reschedule_requests WHERE status = 'Pending')";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$pickups = $result->fetch_all(MYSQLI_ASSOC);

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['latitude']) && !empty($row['longitude'])) {
            $approved_pickups[] = $row;
        }
    }
    $result->free();
} else {
    error_log("Database Error: " . $conn->error);
    echo json_encode(["error" => "Database query failed."]);
    exit();
}

// Debugging: Log output in browser console
echo json_encode($approved_pickups, JSON_PRETTY_PRINT);
?>