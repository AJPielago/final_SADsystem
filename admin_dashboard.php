<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php'; // Include your database connection file

// Verify if the user is an admin
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'saddb'); // Update with your database credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    header("Location: login.php");
    exit();
}

// Add this before including the header
$pageTitle = "Admin Dashboard";
// Add these CSS classes to create a sticky footer layout
$bodyClass = "d-flex flex-column min-vh-100";
$mainContentClass = "flex-grow-1";

include 'includes/header.php';

// Fetch pending pickup requests from the database
$sql = "SELECT p.*, b.building_name FROM pickuprequests p join buildings b ON p.building_id = b.building_id  WHERE status = 'pending'";
$result = $conn->query($sql);
?>

<!-- Add a wrapper div with flex-grow-1 to push the footer down -->
<div class="<?php echo $mainContentClass; ?>">
    <div class="container mt-4">
        <h2>Pending Pickup Requests</h2>

        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User ID</th>
                        <th>Building_Id</th>
                        <th>Building_Name</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['request_id']) ?></td>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['building_id']) ?></td>
                            <td><?= htmlspecialchars($row['building_name']) ?></td>
                            <td><?= htmlspecialchars($row['latitude']) ?></td>
                            <td><?= htmlspecialchars($row['longitude']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <form action="admin/approve_pickup.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['request_id']) ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="admin/reject_pickup.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['request_id']) ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No pending pickup requests found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>