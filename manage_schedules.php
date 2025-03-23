<?php
session_start();
require 'config/db.php';
require 'includes/NotificationHelper.php';

// Initialize NotificationHelper
$notificationHelper = new NotificationHelper($conn);

// Ensure session variables exist
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    die("Access denied: User role is " . htmlspecialchars($_SESSION['role'] ?? 'Not set'));
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $collection_date = $_POST['collection_date'];
    $collection_time = $_POST['collection_time'];

    // Validate required fields for approval
    if ($status === 'approved' && (empty($collection_date) || empty($collection_time))) {
        $_SESSION['error'] = "Collection date and time are required for approval.";
        header("Location: manage_schedules.php" . (isset($_GET['building_id']) ? "?building_id=" . $_GET['building_id'] : ""));
        exit();
    }

    // Start transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // Get the current status and user_id before updating
        $check_query = "SELECT pr.status, pr.user_id, pr.building_id, b.building_name 
                       FROM pickuprequests pr
                       JOIN buildings b ON pr.building_id = b.building_id
                       WHERE pr.request_id = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt_check, "i", $request_id);
        mysqli_stmt_execute($stmt_check);
        $check_result = mysqli_stmt_get_result($stmt_check);
        $request_data = mysqli_fetch_assoc($check_result);
        $old_status = $request_data['status'];
        $user_id = $request_data['user_id'];
        $building_id = $request_data['building_id'];
        $building_name = $request_data['building_name'];
        mysqli_stmt_close($stmt_check);

        // Update the pickup request status
        $update_query = "UPDATE pickuprequests SET status = ? WHERE request_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $status, $request_id);
        mysqli_stmt_execute($stmt);

        // Handle schedule updates based on status
        if ($status === 'approved') {
            // Check if already in pickup_schedules
            $check_query = "SELECT 1 FROM pickup_schedules WHERE request_id = ?";
            $stmt_check = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt_check, "i", $request_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) == 0) {
                // Insert new schedule
                $insert_schedule = "INSERT INTO pickup_schedules (request_id, collection_date, collection_time) 
                                  VALUES (?, ?, ?)";
                $stmt_schedule = mysqli_prepare($conn, $insert_schedule);
                mysqli_stmt_bind_param($stmt_schedule, "iss", $request_id, $collection_date, $collection_time);
                mysqli_stmt_execute($stmt_schedule);
                $_SESSION['message'] = "Pickup request approved and scheduled successfully!";
            } else {
                // Update existing schedule
                $update_schedule = "UPDATE pickup_schedules SET collection_date = ?, collection_time = ? WHERE request_id = ?";
                $stmt_schedule = mysqli_prepare($conn, $update_schedule);
                mysqli_stmt_bind_param($stmt_schedule, "ssi", $collection_date, $collection_time, $request_id);
                mysqli_stmt_execute($stmt_schedule);
                $_SESSION['message'] = "Pickup schedule updated successfully!";
            }
            mysqli_stmt_close($stmt_check);

            // Create notification for the requester
            $message = "Your pickup request has been approved. Collection is scheduled for " . 
                      date('F j, Y', strtotime($collection_date)) . " at " . 
                      date('g:i A', strtotime($collection_time));
            $notificationHelper->createNotification($user_id, $request_id, 'approved', $message, false);

            // Get all residents in the same building
            $residents_sql = "SELECT user_id, full_name, email FROM users WHERE building_id = ? AND role = 'resident'";
            $residents_stmt = $conn->prepare($residents_sql);
            $residents_stmt->bind_param("i", $building_id);
            $residents_stmt->execute();
            $residents_result = $residents_stmt->get_result();

            // Create notification for each resident in the building
            while ($resident = $residents_result->fetch_assoc()) {
                $building_message = "A pickup in your building (" . $building_name . ") has been approved. " .
                                  "Collection is scheduled for " . date('F j, Y', strtotime($collection_date)) . 
                                  " at " . date('g:i A', strtotime($collection_time));
                
                $notificationHelper->createNotification(
                    $resident['user_id'],
                    $request_id,
                    'building_status_update',
                    $building_message,
                    true  // Set to true to send email notification
                );
            }
        } elseif ($status === 'rejected') {
            // Remove from pickup_schedules if rejected
            $delete_schedule = "DELETE FROM pickup_schedules WHERE request_id = ?";
            $stmt_delete = mysqli_prepare($conn, $delete_schedule);
            mysqli_stmt_bind_param($stmt_delete, "i", $request_id);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);
            $_SESSION['message'] = "Pickup request rejected and removed from schedule.";
        } elseif ($status === 'completed') {
            $_SESSION['message'] = "Pickup request marked as completed.";
        }

        // Commit transaction
        mysqli_commit($conn);
        
        // Store the selected building in session for redirect
        if (isset($_GET['building_id'])) {
            $_SESSION['selected_building'] = $_GET['building_id'];
        }
        
        // Redirect to the same page
        header("Location: manage_schedules.php" . (isset($_GET['building_id']) ? "?building_id=" . $_GET['building_id'] : ""));
        exit();
    } catch (Exception $e) {
        // Rollback if any error occurs
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error updating request: " . $e->getMessage();
        header("Location: manage_schedules.php" . (isset($_GET['building_id']) ? "?building_id=" . $_GET['building_id'] : ""));
        exit();
    }
}

// Get selected building filter
$selected_building = isset($_GET['building_id']) ? (int)$_GET['building_id'] : null;

// Fetch all buildings for the filter dropdown
$buildings_query = "SELECT building_id, building_name FROM buildings ORDER BY building_name";
$buildings_result = mysqli_query($conn, $buildings_query);

// Fetch pickup requests with user and building information
$query = "SELECT pr.request_id, u.full_name, pr.building_id, b.building_name, pr.status, pr.created_at, ps.collection_date, ps.collection_time 
          FROM pickuprequests pr 
          JOIN users u ON pr.user_id = u.user_id
          JOIN buildings b ON pr.building_id = b.building_id
          LEFT JOIN pickup_schedules ps ON pr.request_id = ps.request_id";

// Add building filter if selected
if ($selected_building) {
    $query .= " WHERE pr.building_id = " . $selected_building;
}

$query .= " ORDER BY pr.created_at DESC";
$result = mysqli_query($conn, $query);

// Include header after all processing is done
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-12 mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Manage Pickup Schedules</h3>
                    <!-- Building Filter Form -->
                    <form method="GET" class="d-flex align-items-center">
                        <select name="building_id" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">All Buildings</option>
                            <?php while ($building = mysqli_fetch_assoc($buildings_result)): ?>
                                <option value="<?= $building['building_id']; ?>" <?= ($selected_building == $building['building_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($building['building_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-light">Filter</button>
                    </form>
                </div>
                <div class="card-body">
                    
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['notice'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['notice']); unset($_SESSION['notice']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Pickup Requests Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>User</th>
                                    <th>Building</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Collection Date</th>
                                    <th>Collection Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($result) > 0):
                                    while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['request_id']); ?>">
                                            
                                            <td><?= htmlspecialchars($row['full_name']); ?></td>
                                            <td><?= htmlspecialchars($row['building_name']); ?></td>
                                            
                                            <td>
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?= ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?= ($row['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="completed" <?= ($row['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="rejected" <?= ($row['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </td>

                                            <td><?= date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td><input type="date" name="collection_date" class="form-control" value="<?= htmlspecialchars($row['collection_date'] ?? ''); ?>"></td>
                                            <td><input type="time" name="collection_time" class="form-control" value="<?= htmlspecialchars($row['collection_time'] ?? '07:00'); ?>"></td>

                                            <td class="text-center">
                                                <button type="submit" name="update" class="btn btn-success btn-sm">
                                                    <i class="bi bi-pencil-square"></i> Update
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No pickup requests found for the selected building.</td>
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
