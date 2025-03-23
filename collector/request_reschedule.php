<?php
session_start();
require 'config/db.php'; // Fixed path

// Ensure only collectors can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Collector') {
    header("Location: login.php");
    exit();
}

// Get the schedule_id from the URL (or redirect if missing)
if (!isset($_GET['schedule_id'])) {
    header("Location: assigned_pickups.php?error=Invalid request.");
    exit();
}

$schedule_id = intval($_GET['schedule_id']);

// Fetch schedule details
$query = "SELECT ps.schedule_id, ps.collection_date, ps.collection_time, ps.pickup_location, r.waste_type 
          FROM pickup_schedules ps
          JOIN pickup_requests r ON ps.request_id = r.request_id
          WHERE ps.schedule_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    header("Location: assigned_pickups.php?error=Schedule not found.");
    exit();
}

$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h2>🚛 Request Reschedule</h2>
    <p><strong>Schedule ID:</strong> <?= htmlspecialchars($schedule['schedule_id']) ?></p>
    <p><strong>Collection Date:</strong> <?= htmlspecialchars($schedule['collection_date']) ?></p>
    <p><strong>Collection Time:</strong> <?= htmlspecialchars($schedule['collection_time']) ?></p>
    <p><strong>Pickup Location:</strong> <?= htmlspecialchars($schedule['pickup_location']) ?></p>
    <p><strong>Waste Type:</strong> <?= htmlspecialchars($schedule['waste_type']) ?></p>

    <form action="request_reschedule.php" method="POST">
        <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">

        <div class="mb-3">
            <label for="reason" class="form-label">Reason for Reschedule:</label>
            <textarea name="reason" id="reason" class="form-control" required></textarea>
        </div>

        <button type="submit" class="btn btn-warning">Submit Request</button>
        <a href="assigned_pickups.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
