<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch approved reschedule requests for the logged-in user
$query = "
    SELECT rr.reschedule_id, rr.schedule_id, rr.reason, rr.status, 
           ps.collection_date, ps.collection_time,
           DAYNAME(ps.collection_date) as collection_day
    FROM reschedule_requests rr
    LEFT JOIN pickup_schedules ps ON rr.schedule_id = ps.schedule_id
    WHERE rr.user_id = ? AND rr.status = 'approved'
";

$stmt = $conn->prepare($query);

// Check if the statement preparation failed
if ($stmt === false) {
    die("Error: Unable to prepare statement. " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug information
if ($result->num_rows === 0) {
    // Check if there are any reschedule requests at all
    $check_query = "SELECT COUNT(*) as total FROM reschedule_requests WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $total_requests = $check_result->fetch_assoc()['total'];
    
    // Check status distribution
    $status_query = "SELECT status, COUNT(*) as count FROM reschedule_requests WHERE user_id = ? GROUP BY status";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    echo "<!-- Debug Info:
    Total requests: " . $total_requests . "
    Status distribution:";
    while ($status_row = $status_result->fetch_assoc()) {
        echo "\n    " . $status_row['status'] . ": " . $status_row['count'];
    }
    echo "
    -->";
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h2>📅 Manage Reschedule Requests</h2>

    <!-- Display Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['success']) === 'update' ? 'Reschedule request updated successfully!' : '' ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <form action="update_reschedule.php" method="POST">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th>Current Date</th>
                        <th>Current Time</th>
                        <th>New Day</th>
                        <th>New Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['reason']) ?></td>
                            <td><?= date('F j, Y', strtotime($row['collection_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($row['collection_time'])) ?></td>
                            <td>
                                <select name="new_day[<?= $row['reschedule_id'] ?>]" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </td>
                            <td><input type="time" name="new_time[<?= $row['reschedule_id'] ?>]" required></td>
                            <td>
                                <input type="hidden" name="reschedule_id[]" value="<?= $row['reschedule_id'] ?>">
                                <input type="submit" value="Update" class="btn btn-primary btn-sm">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </form>
    <?php else: ?>
        <div class="alert alert-warning text-center">No approved reschedule requests.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<?php
// Close the connection
$conn->close();
?>
