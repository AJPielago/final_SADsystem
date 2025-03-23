<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'saddb'); // Update with your database credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID
$userId = $_SESSION['user_id'];

// Fetch the user's building ID
$stmt = $conn->prepare("SELECT building_id FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($building_id);
$stmt->fetch();
$stmt->close();

// Check if the user is the first registered resident in their building
$is_first_resident = false;
$stmt = $conn->prepare("SELECT user_id FROM users WHERE building_id = ? AND role = 'resident' ORDER BY user_id ASC LIMIT 1");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $building_id);
$stmt->execute();
$stmt->bind_result($first_resident_id);
$stmt->fetch();
$stmt->close();

// Grant permission if the user is the first resident
if ($userId == $first_resident_id) {
    $is_first_resident = true;
}

// Fetch building coordinates
$latitude = null;
$longitude = null;
$stmt = $conn->prepare("SELECT latitude, longitude FROM buildings WHERE building_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $building_id);
$stmt->execute();
$stmt->bind_result($latitude, $longitude);
$stmt->fetch();
$stmt->close();
$conn->close();

// Default location if no coordinates are found
if (!$latitude || !$longitude) {
    $latitude = 14.5534;
    $longitude = 121.0490;
}
?>

<div class="container mt-4">
    <h2>Request Trash Pickup</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <?php if ($is_first_resident): ?>
        <form action="controllers/submit_pickup.php" method="POST">
            <button type="submit" class="btn btn-primary w-100 p-3">🚛 Submit Pickup Request</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">⚠️ Only the first registered resident in this building can request a pickup.</div>
    <?php endif; ?>

    <div id="map" style="height: 500px; width: 100%;" class="mt-4"></div>
</div>

<!-- Include Leaflet and FullCalendar libraries -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var map = L.map('map').setView([<?= htmlspecialchars($latitude) ?>, <?= htmlspecialchars($longitude) ?>], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var marker = L.marker([<?= htmlspecialchars($latitude) ?>, <?= htmlspecialchars($longitude) ?>]).addTo(map)
            .bindPopup('Pickup Location')
            .openPopup();

        map.setMaxBounds([[14.490, 121.015], [14.560, 121.100]]);
        map.setMinZoom(13);
    });
</script>

<?php include 'includes/footer.php'; ?>
