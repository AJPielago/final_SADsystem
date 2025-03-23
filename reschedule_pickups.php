<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['request_id'])) {
    $_SESSION['error'] = "Request ID is required.";
    header("Location: assigned_pickups.php");
    exit();
}

$request_id = (int) $_GET['request_id'];

// Fetch pickup details
$sql = "SELECT pr.created_at, pr.status, b.building_name, u.full_name 
        FROM pickuprequests pr
        JOIN buildings b ON pr.building_id = b.building_id
        JOIN users u ON pr.user_id = u.user_id
        WHERE pr.request_id = ? AND pr.status = 'approved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Pickup request not found or not eligible for rescheduling.";
    header("Location: assigned_pickups.php");
    exit();
}

$request = $result->fetch_assoc();
$created_at = strtotime($request['created_at']);
$new_date = date('Y-m-d', strtotime('+1 day', $created_at)); // Set new date to the next day

// Check for existing pending reschedule request
$check_sql = "SELECT 1 FROM reschedule_requests WHERE request_id = ? AND status = 'Pending'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $request_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = "A pending reschedule request already exists for this pickup.";
    header("Location: assigned_pickups.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reason = trim($_POST['reason']);
    $new_time = $_POST['new_time'];
    $request_date = date('Y-m-d H:i:s');

    if (empty($reason)) {
        $_SESSION['error'] = "Reason for rescheduling is required.";
    } else {
        $new_datetime = strtotime("$new_date $new_time");
        if ($new_datetime <= time()) {
            $_SESSION['error'] = "The new time must be in the future.";
        } else {
            $insert_sql = "INSERT INTO reschedule_requests (request_id, user_id, request_date, new_date, new_time, reason, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iissss", $request_id, $user_id, $request_date, $new_date, $new_time, $reason);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Reschedule request submitted successfully.";
                header("Location: assigned_pickups.php");
                exit();
            } else {
                $_SESSION['error'] = "Error submitting reschedule request.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Request Reschedule</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">New Collection Date</label>
                            <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($new_date)) ?>" readonly>
                            <input type="hidden" name="new_date" value="<?= $new_date ?>">
                        </div>

                        <div class="mb-3">
                            <label for="new_time" class="form-label">New Collection Time</label>
                            <select class="form-control" id="new_time" name="new_time" required>
                                <?php
                                for ($hour = 6; $hour <= 21; $hour++) {
                                    $time_value = sprintf("%02d:00", $hour);
                                    $time_display = date("g:00 A", strtotime($time_value));
                                    echo "<option value=\"$time_value\">$time_display</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Rescheduling</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Reschedule Request</button>
                            <a href="assigned_pickups.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
