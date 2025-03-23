<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; // Initialize message variable

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reschedule_id']) && isset($_POST['new_day']) && isset($_POST['new_time'])) {
        $conn->begin_transaction(); // Start transaction
        
        try {
            foreach ($_POST['reschedule_id'] as $reschedule_id) {
                $new_day = $_POST['new_day'][$reschedule_id];
                $new_time = $_POST['new_time'][$reschedule_id];

                // Calculate the new date based on the selected day
                $dayOfWeek = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                $currentDayNum = date('N'); // 1 (Monday) to 7 (Sunday)
                $targetDayNum = $dayOfWeek[$new_day];
                $daysToAdd = ($targetDayNum - $currentDayNum + 7) % 7;
                $new_date = date('Y-m-d', strtotime("+$daysToAdd days"));

                // Verify the user owns this request and get the request_id
                $verify_query = "SELECT rr.request_id, pr.building_id, pr.latitude, pr.longitude
                                 FROM reschedule_requests rr
                                 JOIN pickuprequests pr ON rr.request_id = pr.request_id
                                 WHERE rr.reschedule_id = ? AND pr.user_id = ? AND rr.status = 'Approved'";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("ii", $reschedule_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {
                    throw new Exception("Invalid request or permission denied.");
                }

                $request_data = $verify_result->fetch_assoc();
                $request_id = $request_data['request_id'];
                $building_id = $request_data['building_id'];
                $latitude = $request_data['latitude'];
                $longitude = $request_data['longitude'];

                // Update the reschedule request
                $update_query = "UPDATE reschedule_requests SET new_date = ?, new_time = ? WHERE reschedule_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $new_date, $new_time, $reschedule_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Update failed: " . $update_stmt->error);
                }

                // Insert new pickup request with retrieved data
                $pickup_insert_query = "INSERT INTO pickuprequests (user_id, status, created_at, building_id, latitude, longitude) 
                                        VALUES (?, 'pending', NOW(), ?, ?, ?)";
                $pickup_insert_stmt = $conn->prepare($pickup_insert_query);
                $pickup_insert_stmt->bind_param("iidd", $user_id, $building_id, $latitude, $longitude);
                if (!$pickup_insert_stmt->execute()) {
                    throw new Exception("Pickup request insert failed: " . $pickup_insert_stmt->error);
                }

                // Remove the reschedule request after updating
                $delete_query = "DELETE FROM reschedule_requests WHERE reschedule_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $reschedule_id);
                if (!$delete_stmt->execute()) {
                    throw new Exception("Delete failed: " . $delete_stmt->error);
                }
            }

            $conn->commit(); // Commit transaction
            $message = "<div class='alert alert-success'>Your pickup request has been successfully queued again!</div>";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback if any error occurs
            $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Missing required data.</div>";
    }
}

// Fetch approved reschedule requests for the logged-in user
$query = "
    SELECT rr.reschedule_id, rr.request_id, rr.reason, rr.status, 
           pr.created_at AS original_created_at, rr.new_date, rr.new_time,
           DAYNAME(rr.new_date) AS new_day
    FROM reschedule_requests rr
    LEFT JOIN pickuprequests pr ON rr.request_id = pr.request_id
    WHERE pr.user_id = ? AND rr.status = 'Approved'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h2>📅 Manage Reschedule Requests</h2>
    
    <?= $message; ?> <!-- Show success/error message -->

    <?php if ($result->num_rows > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fa fa-calendar-alt"></i> Approved Reschedule Requests
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Original Request</th>
                                <th>Reason</th>
                                <th>New Day</th>
                                <th>New Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>Request #<?= $row['request_id'] ?><br>
                                        <small class="text-muted">Created: <?= date('M j, Y', strtotime($row['original_created_at'])) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                    <td>
                                        <select name="new_day[<?= $row['reschedule_id'] ?>]" class="form-select">
                                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                                <option value="<?= $day ?>" <?= ($row['new_day'] == $day) ? 'selected' : '' ?>><?= $day ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="new_time[<?= $row['reschedule_id'] ?>]" class="form-select">
                                            <?php foreach (['08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00'] as $time): ?>
                                                <option value="<?= $time ?>" <?= ($row['new_time'] == $time) ? 'selected' : '' ?>><?= date("g:i A", strtotime($time)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="reschedule_id[]" value="<?= $row['reschedule_id'] ?>">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            <i class="fa fa-exclamation-triangle"></i> No approved reschedule requests.
        </div>
    <?php endif; ?>

    <div class="text-center">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php $conn->close(); ?>
