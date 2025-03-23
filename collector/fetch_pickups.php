<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$approved_pickups = [];

$sql = "SELECT ps.schedule_id, ps.request_id, ps.collection_date, ps.collection_time, 
               b.building_name, pr.latitude, pr.longitude, u.full_name AS resident_name
        FROM pickup_schedules ps
        JOIN pickuprequests pr ON ps.request_id = pr.request_id
        JOIN users u ON pr.user_id = u.user_id
        JOIN buildings b ON pr.building_id = b.building_id
        WHERE ps.collection_date = CURDATE() 
        AND pr.status = 'approved'";  // Only fetch approved requests for today

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