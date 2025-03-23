<?php
session_start();
require 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Get the user's building ID and role
$sql_user = "SELECT full_name, building_id, role FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
if (!$stmt_user) {
    die("SQL Error: " . $conn->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$full_name = $user['full_name'] ?? 'User';
$building_id = $user['building_id'] ?? null;
$user_role = $user['role'] ?? '';

// Fetch completed pickups with building name
$sql_completed = "SELECT pr.request_id, pr.status, pr.created_at, pr.building_id, b.building_name, 
                  pr.latitude, pr.longitude, u.full_name as resident_name
                  FROM pickuprequests pr 
                  LEFT JOIN buildings b ON pr.building_id = b.building_id
                  LEFT JOIN users u ON pr.user_id = u.user_id
                  WHERE pr.status = 'completed'";

// If user is a resident, only show their pickups
if ($user_role === 'resident') {
    $sql_completed .= " AND pr.user_id = ?";
    $stmt_completed = $conn->prepare($sql_completed);
    $stmt_completed->bind_param("i", $user_id);
} else {
    $sql_completed .= " ORDER BY pr.created_at DESC";
    $stmt_completed = $conn->prepare($sql_completed);
}

if (!$stmt_completed) {
    die("SQL Error: " . $conn->error);
}
$stmt_completed->execute();
$result_completed = $stmt_completed->get_result();

// Fetch pending pickups with building name
$sql_pending = "SELECT pr.request_id, pr.status, pr.created_at, pr.building_id, b.building_name, 
                pr.latitude, pr.longitude, u.full_name as resident_name
                FROM pickuprequests pr 
                LEFT JOIN buildings b ON pr.building_id = b.building_id
                LEFT JOIN users u ON pr.user_id = u.user_id
                WHERE pr.status = 'pending'";

// If user is a resident, only show their pickups
if ($user_role === 'resident') {
    $sql_pending .= " AND pr.user_id = ?";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param("i", $user_id);
} else {
    $sql_pending .= " ORDER BY pr.created_at DESC";
    $stmt_pending = $conn->prepare($sql_pending);
}

if (!$stmt_pending) {
    die("SQL Error: " . $conn->error);
}
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();

// Include header after all potential redirects
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h2 class="card-title">Recent Activities</h2>
            <p class="text-muted">
                <?php if ($user_role === 'collector'): ?>
                    View all waste pickup activities across all buildings.
                <?php else: ?>
                    Review your past and upcoming waste pickups.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Completed Pickups -->
    <div class="mt-4">
        <h4>✅ Completed Pickups</h4>
        <?php if ($result_completed->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($pickup = $result_completed->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>📅 Request Date:</strong> <?= htmlspecialchars($pickup['created_at']) ?><br>
                                <strong>🏢 Building:</strong> <?= htmlspecialchars($pickup['building_name'] ?? 'Unknown Building') ?><br>
                                <?php if ($user_role === 'collector'): ?>
                                    <strong>👤 Resident:</strong> <?= htmlspecialchars($pickup['resident_name']) ?><br>
                                <?php endif; ?>
                                <strong>📍 Location:</strong> 
                                <?= "Lat: " . htmlspecialchars($pickup['latitude']) . ", Long: " . htmlspecialchars($pickup['longitude']) ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-2">No completed pickups found.</div>
        <?php endif; ?>
    </div>

    <!-- Pending Pickups -->
    <div class="mt-4">
        <h4>⏳ Pending Pickups</h4>
        <?php if ($result_pending->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($pickup = $result_pending->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-warning">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>📅 Request Date:</strong> <?= htmlspecialchars($pickup['created_at']) ?><br>
                                <strong>🏢 Building:</strong> <?= htmlspecialchars($pickup['building_name'] ?? 'Unknown Building') ?><br>
                                <?php if ($user_role === 'collector'): ?>
                                    <strong>👤 Resident:</strong> <?= htmlspecialchars($pickup['resident_name']) ?><br>
                                <?php endif; ?>
                                <strong>📍 Location:</strong> 
                                <?= "Lat: " . htmlspecialchars($pickup['latitude']) . ", Long: " . htmlspecialchars($pickup['longitude']) ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary mt-2">No pending pickups found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
