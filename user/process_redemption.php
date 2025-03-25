<?php
session_start();
require '../config/db.php';

// Ensure proper error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
http_response_code(400); // Default to error response

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $user_id = $_SESSION['user_id'];
    $reward_id = $_POST['reward_id'] ?? null;
    $reward_name = $_POST['reward_name'] ?? null;

    // Validate inputs
    if (!$reward_id) {
        throw new Exception('Invalid reward ID');
    }
    if (!$reward_name) {
        throw new Exception('Invalid reward name');
    }

    // Start a transaction
    $conn->begin_transaction();

    // First, check current points and reward cost
    $stmt_check = $conn->prepare("SELECT points_required FROM rewards_info WHERE reward_id = ?");
    if (!$stmt_check) {
        throw new Exception('Failed to prepare rewards check statement: ' . $conn->error);
    }
    $stmt_check->bind_param("i", $reward_id);
    if (!$stmt_check->execute()) {
        throw new Exception('Failed to execute rewards check: ' . $stmt_check->error);
    }
    $result = $stmt_check->get_result();
    $reward = $result->fetch_assoc();
    $stmt_check->close();

    if (!$reward) {
        throw new Exception('Reward not found');
    }

    // Check if user has enough points
    $stmt_points = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
    if (!$stmt_points) {
        throw new Exception('Failed to prepare points check statement: ' . $conn->error);
    }
    $stmt_points->bind_param("i", $user_id);
    if (!$stmt_points->execute()) {
        throw new Exception('Failed to execute points check: ' . $stmt_points->error);
    }
    $result_points = $stmt_points->get_result();
    $user = $result_points->fetch_assoc();
    $stmt_points->close();

    if ($user['points'] < $reward['points_required']) {
        throw new Exception('Insufficient points');
    }

    // Deduct points
    $stmt_deduct = $conn->prepare("UPDATE users SET points = points - ? WHERE user_id = ?");
    if (!$stmt_deduct) {
        throw new Exception('Failed to prepare points deduction statement: ' . $conn->error);
    }
    $stmt_deduct->bind_param("ii", $reward['points_required'], $user_id);
    if (!$stmt_deduct->execute()) {
        throw new Exception('Failed to deduct points: ' . $stmt_deduct->error);
    }
    $stmt_deduct->close();

    // Record the redemption
    $coupon_code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3) . substr(str_shuffle('0123456789'), 0, 3));
    $stmt_redeem = $conn->prepare("INSERT INTO rewards (coupon_code, user_id, points_used, reward_description) VALUES (?, ?, ?, ?)");
    if (!$stmt_redeem) {
        throw new Exception('Failed to prepare redemption record statement: ' . $conn->error);
    }
    $stmt_redeem->bind_param("siis", $coupon_code, $user_id, $reward['points_required'], $reward_name);
    if (!$stmt_redeem->execute()) {
        throw new Exception('Failed to record redemption: ' . $stmt_redeem->error);
    }
    $stmt_redeem->close();

    // Commit the transaction
    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction: ' . $conn->error);
    }

    // Success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Reward redeemed successfully',
        'reward_name' => $reward_name,
        'coupon_code' => $coupon_code
    ]);

} catch (Exception $e) {
    // Rollback the transaction
    $conn->rollback();

    // Log the error (optional, but recommended)
    error_log('Redemption Error: ' . $e->getMessage());

    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
} finally {
    // Close the connection
    $conn->close();
}
?>