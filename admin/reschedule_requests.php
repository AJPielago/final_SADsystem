<?php
session_start();
require '../config/db.php';
require '../includes/NotificationHelper.php';

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Process form submission for handling reschedule requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $reschedule_id = (int)$_POST['reschedule_id'];
    $action = $_POST['action'];
    $schedule_id = (int)$_POST['schedule_id'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Get the reschedule request details
        $query = "SELECT rr.*, ps.request_id, u.user_id, u.full_name, u.email
                 FROM reschedule_requests rr
                 JOIN pickup_schedules ps ON rr.schedule_id = ps.schedule_id
                 JOIN pickuprequests pr ON ps.request_id = pr.request_id
                 JOIN users u ON pr.user_id = u.user_id
                 WHERE rr.reschedule_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $reschedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if ($action === 'approve') {
            // Update the pickup schedule
            $update_schedule = "UPDATE pickup_schedules 
                              SET collection_date = ?, collection_time = ? 
                              WHERE schedule_id = ?";
            $stmt = $conn->prepare($update_schedule);
            $stmt->bind_param("ssi", $request['new_date'], $request['new_time'], $schedule_id);
            $stmt->execute();
            
            // Update reschedule request status
            $update_status = "UPDATE reschedule_requests SET status = 'Approved' WHERE reschedule_id = ?";
            $stmt = $conn->prepare($update_status);
            $stmt->bind_param("i", $reschedule_id);
            $stmt->execute();
            
            // Create notification for the resident
            $message = "Your reschedule request has been approved. New collection date: " . 
                      date('F j, Y', strtotime($request['new_date'])) . " at " . 
                      date('g:i A', strtotime($request['new_time']));
            $notificationHelper->createNotification($request['user_id'], $request['request_id'], 'reschedule_approved', $message, false);
            
            $_SESSION['success'] = "Reschedule request approved successfully.";
        } else {
            // Update reschedule request status to Denied
            $update_status = "UPDATE reschedule_requests SET status = 'Denied' WHERE reschedule_id = ?";
            $stmt = $conn->prepare($update_status);
            $stmt->bind_param("i", $reschedule_id);
            $stmt->execute();
            
            // Create notification for the resident
            $message = "Your reschedule request has been denied. Please contact the administrator for more information.";
            $notificationHelper->createNotification($request['user_id'], $request['request_id'], 'reschedule_denied', $message, false);
            
            $_SESSION['success'] = "Reschedule request denied successfully.";
        }
        
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error processing reschedule request.";
    }
    
    header("Location: reschedule_requests.php");
    exit();
}

// Fetch all pending reschedule requests
$sql = "SELECT rr.*, ps.collection_date, ps.collection_time, 
               u.full_name, u.email, b.building_name
        FROM reschedule_requests rr
        JOIN pickup_schedules ps ON rr.schedule_id = ps.schedule_id
        JOIN pickuprequests pr ON ps.request_id = pr.request_id
        JOIN users u ON pr.user_id = u.user_id
        JOIN buildings b ON pr.building_id = b.building_id
        WHERE rr.status = 'Pending'
        ORDER BY rr.created_at DESC";

$result = $conn->query($sql);

// Include header after all processing
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Reschedule Requests</h2>
    
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
    
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Building</th>
                        <th>Current Schedule</th>
                        <th>New Schedule</th>
                        <th>Reason</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['building_name']) ?></td>
                            <td>
                                <?= date('F j, Y', strtotime($row['collection_date'])) ?><br>
                                <?= date('g:i A', strtotime($row['collection_time'])) ?>
                            </td>
                            <td>
                                <?= date('F j, Y', strtotime($row['new_date'])) ?><br>
                                <?= date('g:i A', strtotime($row['new_time'])) ?>
                            </td>
                            <td><?= htmlspecialchars($row['reason']) ?></td>
                            <td><?= date('F j, Y g:i A', strtotime($row['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reschedule_id" value="<?= $row['reschedule_id'] ?>">
                                    <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reschedule_id" value="<?= $row['reschedule_id'] ?>">
                                    <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                                    <input type="hidden" name="action" value="deny">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Deny
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No pending reschedule requests.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?> 