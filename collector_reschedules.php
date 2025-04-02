<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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

if ($role !== 'collector') {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

$user_id = $_SESSION['user_id'];

$query = "SELECT rr.reschedule_id, rr.schedule_id, rr.reason, rr.status, ps.collection_date, ps.collection_time
          FROM reschedule_requests rr
          JOIN pickup_schedules ps ON rr.schedule_id = ps.schedule_id
          WHERE rr.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reschedules = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h2>🔄 Your Reschedule Requests</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Collection Date</th>
                <th>Collection Time</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reschedules as $reschedule): ?>
                <tr>
                    <td><?= htmlspecialchars($reschedule['collection_date']) ?></td>
                    <td><?= htmlspecialchars($reschedule['collection_time']) ?></td>
                    <td><?= htmlspecialchars($reschedule['reason']) ?></td>
                    <td><?= htmlspecialchars($reschedule['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
