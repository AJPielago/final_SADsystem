<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php'; // Include your database connection file

// Fetch the logged-in user's ID
$userId = $_SESSION['user_id'];

// Fetch the user's building ID
$stmt = $conn->prepare("SELECT building_id FROM users WHERE user_id = ?");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($building_id);
$stmt->fetch();
$stmt->close();

// Check if the user is the first resident in their building
$stmt = $conn->prepare("SELECT user_id FROM users WHERE building_id = ? AND role = 'resident' ORDER BY user_id ASC LIMIT 1");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $building_id);
$stmt->execute();
$stmt->bind_result($first_resident_id);
$stmt->fetch();
$stmt->close();

// If the user is not the first resident, deny access
if ($userId != $first_resident_id) {
    header("Location: ../pickup.php?error=Only the first registered resident in this building can request a pickup.");
    exit();
}

// Get the building's coordinates
$stmt = $conn->prepare("SELECT latitude, longitude FROM buildings WHERE building_id = ?");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $building_id);
$stmt->execute();
$stmt->bind_result($latitude, $longitude);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = 'pending';
    $created_at = date('Y-m-d H:i:s');

    // Insert the pickup request into the database
    $stmt = $conn->prepare("INSERT INTO pickuprequests (user_id, building_id, latitude, longitude, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("iiddss", $userId, $building_id, $latitude, $longitude, $status, $created_at);

    if ($stmt->execute()) {
        header("Location: ../pickup.php?success=Pickup request submitted successfully.");
    } else {
        header("Location: ../pickup.php?error=Failed to submit pickup request. " . htmlspecialchars($stmt->error));
    }

    $stmt->close();
    $conn->close();
}
?>