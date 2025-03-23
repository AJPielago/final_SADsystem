<?php
session_start(); // Ensure session is started
require '../config/db.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Fetch all pickup requests along with generated collection dates
$sql = "SELECT pr.id, u.full_name, u.address, pr.waste_type, pr.pickup_day, pr.notes, pr.status, 
               GROUP_CONCAT(ps.collection_date ORDER BY ps.collection_date ASC SEPARATOR ', ') AS collection_dates
        FROM PickupRequests pr
        JOIN Users u ON pr.user_id = u.user_id
        LEFT JOIN PickupSchedules ps ON pr.id = ps.request_id
        GROUP BY pr.id
        ORDER BY pr.status ASC, pr.pickup_day ASC";

$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">Manage Pickup Requests</h2>

    <!-- Display success or error messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Action completed successfully!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Responsive Table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>User</th>
                    <th>Address</th>
                    <th>Waste Type</th>
                    <th>Pickup Day</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Collection Dates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['waste_type']) ?></td>
                        <td><?= htmlspecialchars($row['pickup_day']) ?></td>
                        <td><?= htmlspecialchars($row['notes']) ?></td>
                        <td>
                            <span class="badge bg-<?= $row['status'] === 'Pending' ? 'warning' : ($row['status'] === 'Approved' ? 'success' : 'danger') ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $row['collection_dates'] ? nl2br(htmlspecialchars($row['collection_dates'])) : '<span class="text-muted">No dates generated</span>'; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <a href="../controllers/update_pickups.php?id=<?= $row['id'] ?>&status=Approved" class="btn btn-success btn-sm">Approve</a>
                                <a href="../controllers/update_pickups.php?id=<?= $row['id'] ?>&status=Rejected" class="btn btn-danger btn-sm">Reject</a>
                            <?php else: ?>
                                <span class="text-muted">No actions available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
