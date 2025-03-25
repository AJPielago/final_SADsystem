<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Get POST data
$points = floatval($_POST['points'] ?? 0);

if ($points <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid points value']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current points
$sql_get = "SELECT points FROM users WHERE user_id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $user_id);
$stmt_get->execute();
$result = $stmt_get->get_result();
$user = $result->fetch_assoc();
$current_points = $user['points'] ?? 0;
$stmt_get->close();

// Update user's points
$new_points = $current_points + $points;
$sql_update = "UPDATE users SET points = ? WHERE user_id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("di", $new_points, $user_id);

if ($stmt_update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Points updated successfully',
        'new_total' => $new_points
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating points: ' . $conn->error
    ]);
}

$stmt_update->close();
$conn->close();
?> 