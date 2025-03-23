<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require '../config/db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reward_id'])) {
    $reward_id = intval($_POST['reward_id']);

    // Get the user's current points
    $sql_points = "SELECT points FROM users WHERE user_id = ?";
    $stmt_points = $conn->prepare($sql_points);
    $stmt_points->bind_param("i", $user_id);
    $stmt_points->execute();
    $result_points = $stmt_points->get_result();
    $user = $result_points->fetch_assoc();
    $available_points = $user['points'] ?? 0;
    $stmt_points->close();

    // Get the reward details
    $sql_reward = "SELECT reward_name, points_required FROM rewards_info WHERE reward_id = ?";
    $stmt_reward = $conn->prepare($sql_reward);
    $stmt_reward->bind_param("i", $reward_id);
    $stmt_reward->execute();
    $result_reward = $stmt_reward->get_result();
    $reward = $result_reward->fetch_assoc();
    $stmt_reward->close();

    if (!$reward) {
        $_SESSION['error'] = "Invalid reward selection.";
        header("Location: /SADsystem/redeem_rewards.php");
        exit();
    }

    $reward_name = $reward['reward_name'];
    $points_required = $reward['points_required'];

    // Check if user has enough points
    if ($available_points >= $points_required) {
        // Deduct points from user
        $new_points = $available_points - $points_required;
        $sql_update_points = "UPDATE users SET points = ? WHERE user_id = ?";
        $stmt_update_points = $conn->prepare($sql_update_points);
        $stmt_update_points->bind_param("ii", $new_points, $user_id);
        $stmt_update_points->execute();
        $stmt_update_points->close();

        // Store redemption record in rewards table
        $sql_insert_reward = "INSERT INTO rewards (user_id, points_used, reward_description) VALUES (?, ?, ?)";
        $stmt_insert_reward = $conn->prepare($sql_insert_reward);
        $stmt_insert_reward->bind_param("iis", $user_id, $points_required, $reward_name);
        $stmt_insert_reward->execute();
        $stmt_insert_reward->close();

        $_SESSION['success'] = "Successfully redeemed '$reward_name'!";
    } else {
        $_SESSION['error'] = "You do not have enough points to redeem this reward.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../redeem_rewards.php");
exit();
?>
