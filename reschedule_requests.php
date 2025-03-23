<?php
session_start();
require 'config/db.php';
require 'includes/NotificationHelper.php';

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $reschedule_id = $_POST['reschedule_id'];
    $action = $_POST['action'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];

    // Start transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // Get the current request details
        $check_query = "SELECT rr.*, 
                               pr.request_id, 
                               u.full_name, 
                               b.building_name, 
                               pr.created_at AS request_created_date
                        FROM reschedule_requests rr
                        JOIN pickuprequests pr ON rr.request_id = pr.request_id
                        JOIN users u ON rr.user_id = u.user_id
                        JOIN buildings b ON pr.building_id = b.building_id
                        WHERE rr.reschedule_id = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt_check, "i", $reschedule_id);
        mysqli_stmt_execute($stmt_check);
        $check_result = mysqli_stmt_get_result($stmt_check);
        $request_data = mysqli_fetch_assoc($check_result);
        mysqli_stmt_close($stmt_check);

        if ($action === 'approve') {
            // Update reschedule request status
            $update_status = "UPDATE reschedule_requests SET status = 'Approved', new_date = ?, new_time = ? WHERE reschedule_id = ?";
            $stmt_status = mysqli_prepare($conn, $update_status);
            mysqli_stmt_bind_param($stmt_status, "ssi", $new_date, $new_time, $reschedule_id);
            mysqli_stmt_execute($stmt_status);
            mysqli_stmt_close($stmt_status);

            // Create notification for the collector
            $collector_message = "Your reschedule request has been approved. New collection date: " . 
                               date('F j, Y', strtotime($new_date)) . " at " . 
                               date('g:i A', strtotime($new_time));
            $notificationHelper->createNotification(
                $request_data['user_id'],
                $request_data['request_id'],
                'reschedule_approved',
                $collector_message,
                true
            );

            $_SESSION['success'] = "Reschedule request approved successfully.";
        } else if ($action === 'reject') {
            // Update reschedule request status
            $update_status = "UPDATE reschedule_requests SET status = 'Rejected' WHERE reschedule_id = ?";
            $stmt_status = mysqli_prepare($conn, $update_status);
            mysqli_stmt_bind_param($stmt_status, "i", $reschedule_id);
            mysqli_stmt_execute($stmt_status);
            mysqli_stmt_close($stmt_status);

            // Create notification for the collector
            $collector_message = "Your reschedule request has been rejected. The original schedule remains unchanged.";
            $notificationHelper->createNotification(
                $request_data['user_id'],
                $request_data['request_id'],
                'reschedule_rejected',
                $collector_message,
                true
            );

            $_SESSION['success'] = "Reschedule request rejected successfully.";
        }

        // Commit transaction
        mysqli_commit($conn);
        header("Location: reschedule_requests.php");
        exit();
    } catch (Exception $e) {
        // Rollback if any error occurs
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error processing request!";
        header("Location: reschedule_requests.php");
        exit();
    }
}

// Fetch all reschedule requests
$query = "SELECT rr.*, 
                 pr.request_id, 
                 u.full_name, 
                 b.building_name,
                 pr.created_at AS request_created_date
          FROM reschedule_requests rr
          JOIN pickuprequests pr ON rr.request_id = pr.request_id
          JOIN users u ON rr.user_id = u.user_id
          JOIN buildings b ON pr.building_id = b.building_id
          ORDER BY rr.request_date DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn)); // Debugging: Check for errors
}

// Include header after all processing
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Manage Reschedule Requests</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Collector</th>
                                    <th>Building</th>
                                    <th>Requested Schedule</th>
                                    <th>Reason</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['building_name']) ?></td>
                                            <td>
                                                <?php if (!empty($row['new_date']) && !empty($row['new_time'])): ?>
                                                    <?= date('F j, Y', strtotime($row['new_date'])) ?> at 
                                                    <?= date('g:i A', strtotime($row['new_time'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['reason']) ?></td>
                                            <td>
                                                <?php if (!empty($row['request_created_date'])): ?>
                                                    <?= date('F j, Y', strtotime($row['request_created_date'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] === 'Pending' ? 'warning' : 
                                                    ($row['status'] === 'Approved' ? 'success' : 'danger') ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Pending'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="reschedule_id" value="<?= $row['reschedule_id'] ?>">
                                                        <input type="hidden" name="new_date" value="<?= $row['new_date'] ?>">
                                                        <input type="hidden" name="new_time" value="<?= $row['new_time'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="reschedule_id" value="<?= $row['reschedule_id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No reschedule requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>