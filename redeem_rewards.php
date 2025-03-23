<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
require 'config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user points
$sql_points = "SELECT points FROM users WHERE user_id = ?";
$stmt_points = $conn->prepare($sql_points);
$stmt_points->bind_param("i", $user_id);
$stmt_points->execute();
$result_points = $stmt_points->get_result();
$user = $result_points->fetch_assoc();
$available_points = $user['points'] ?? 0;
$stmt_points->close();

// Fetch available rewards
$sql_rewards = "SELECT reward_id, reward_name, points_required FROM rewards_info";
$result_rewards = $conn->query($sql_rewards);
?>

<div class="container mt-4">
    <h2>🎁 Redeem Rewards</h2>
    <p class="alert alert-info">You have <strong><?= htmlspecialchars($available_points) ?></strong> points available.</p>

    <?php if ($result_rewards->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Reward</th>
                    <th>Points Required</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($reward = $result_rewards->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($reward['reward_name']) ?></td>
                        <td><?= htmlspecialchars($reward['points_required']) ?></td>
                        <td>
                            <?php if ($available_points >= $reward['points_required']): ?>
                                <form action="user/process_redemption.php" method="POST">
                                    <input type="hidden" name="reward_id" value="<?= $reward['reward_id'] ?>">
                                    <button type="submit" class="btn btn-success">Redeem</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Not Enough Points</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-warning">No rewards available at the moment.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
