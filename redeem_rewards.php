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
                                <form action="user/process_redemption.php" method="POST" class="redeem-form">
                                    <input type="hidden" name="reward_id" value="<?= $reward['reward_id'] ?>">
                                    <input type="hidden" name="reward_name" value="<?= htmlspecialchars($reward['reward_name']) ?>">
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

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">Redemption Error</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="errorMessage" class="text-danger">An unexpected error occurred during redemption.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1" role="dialog" aria-labelledby="couponModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalLabel">🎉 Coupon Claimed!</h5>
            </div>
            <div class="modal-body text-center">
                <p>Congratulations! You've redeemed the <span id="rewardName" class="font-weight-bold"></span>.</p>
                <div class="my-4">
                    <h6>Your Coupon Code:</h6>
                    <div class="d-flex justify-content-center align-items-center">
                        <code class="h3 bg-light px-4 py-2 rounded" id="couponCode"></code>
                        <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyCode()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <p class="text-muted small">Please save this code. You'll need it to claim your reward.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeAndRefresh()">Done</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a redemption query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const redeemed = urlParams.get('redeemed');
    const rewardName = urlParams.get('reward_name');
    const errorMessage = urlParams.get('error');

    // Handle error message if exists
    if (errorMessage) {
        document.getElementById('errorMessage').textContent = decodeURIComponent(errorMessage);
        $('#errorModal').modal('show');
    }

    // Handle successful redemption
    if (redeemed === 'success') {
        // Generate random coupon code (3 letters + 3 numbers)
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        
        let couponCode = '';
        // 3 random letters
        for (let i = 0; i < 3; i++) {
            couponCode += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        // 3 random numbers
        for (let i = 0; i < 3; i++) {
            couponCode += numbers.charAt(Math.floor(Math.random() * numbers.length));
        }

        // Set modal content
        document.getElementById('rewardName').textContent = decodeURIComponent(rewardName);
        document.getElementById('couponCode').textContent = couponCode;

        // Show modal
        $('#couponModal').modal('show');
    }

    // Intercept form submission
    document.querySelectorAll('.redeem-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rewardName = formData.get('reward_name');
            
            fetch('user/process_redemption.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok (status in 200-299 range)
                if (!response.ok) {
                    // Throw an error for non-200 responses
                    return response.text().then(text => {
                        throw new Error(text || 'Server responded with an error');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Set modal content
                    document.getElementById('rewardName').textContent = data.reward_name;
                    document.getElementById('couponCode').textContent = data.coupon_code;

                    // Show modal (it won't auto-dismiss)
                    $('#couponModal').modal('show');
                } else {
                    // Handle error response from server
                    throw new Error(data.message || 'Redemption failed');
                }
            })
            .catch(error => {
                console.error('Redemption Error:', error);
                document.getElementById('errorMessage').textContent = error.message;
                $('#errorModal').modal('show');
            });
        });
    });

    // Add these new functions
    window.copyCode = function() {
        const codeElement = document.getElementById('couponCode');
        const code = codeElement.textContent;
        
        navigator.clipboard.writeText(code).then(() => {
            // Visual feedback for copy
            const copyBtn = event.target.closest('button');
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    };

    window.closeAndRefresh = function() {
        $('#couponModal').modal('hide');
        window.location.reload();
    };
});
</script>