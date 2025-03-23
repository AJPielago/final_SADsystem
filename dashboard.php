<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include header first - this will establish the database connection
include 'includes/header.php';

// Get current week's start and end date
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));

// Get user's building_id
$user_id = $_SESSION['user_id'];
$user_query = "SELECT building_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$building_id = $user_data['building_id'];
$stmt->close();

// Fetch approved scheduled pickups for this week for the user's building
$sql = "SELECT ps.collection_date, ps.collection_time, b.building_name, u.full_name
        FROM pickup_schedules ps
        JOIN pickuprequests pr ON ps.request_id = pr.request_id
        JOIN users u ON pr.user_id = u.user_id
        JOIN buildings b ON pr.building_id = b.building_id
        WHERE pr.building_id = ?
        AND pr.status = 'approved'
        AND ps.collection_date BETWEEN ? AND ?
        AND TIME(ps.collection_time) >= '07:00:00' 
        AND TIME(ps.collection_time) <= '18:00:00'
        ORDER BY ps.collection_date, ps.collection_time";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("iss", $building_id, $start_of_week, $end_of_week);
$stmt->execute();
$result = $stmt->get_result();

$pickups = [];
while ($row = $result->fetch_assoc()) {
    $pickups[] = $row;
}
$stmt->close();

// Get building name for display
$building_query = "SELECT building_name FROM buildings WHERE building_id = ?";
$stmt = $conn->prepare($building_query);
$stmt->bind_param("i", $building_id);
$stmt->execute();
$building_result = $stmt->get_result();
$building_data = $building_result->fetch_assoc();
$building_name = $building_data['building_name'];
$stmt->close();

// Days of the week mapping
$days_of_week = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
?>

<div class="container" style="margin-top: 80px;">
    <h2 class="text-center">Weekly Pickup Schedule for <?= htmlspecialchars($building_name); ?></h2>
    <div class="schedule-container">
        <div class="time-column"></div>
        <?php foreach ($days_of_week as $day) echo "<div class='text-center fw-bold'>$day</div>"; ?>
        
        <?php
        for ($hour = 7; $hour <= 18; $hour++) {
            $time_label = date("h:i A", strtotime("$hour:00"));
            echo "<div class='time-column'>$time_label</div>";
            foreach ($days_of_week as $day) {
                echo "<div class='schedule-cell' data-day='$day' data-time='$hour:00'></div>";
            }
        }
        ?>
    </div>
</div> 

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let pickups = <?php echo json_encode($pickups); ?>;
        pickups.forEach(pickup => {
            let date = new Date(pickup.collection_date + 'T' + pickup.collection_time);
            let dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
            let time = date.toTimeString().substring(0, 5);
            let cell = document.querySelector(`[data-day='${dayName}'][data-time='${time}']`);
            if (cell) {
                let div = document.createElement("div");
                div.className = "pickup-block";
                div.innerHTML = `
                    ${pickup.building_name}
                `;
                cell.appendChild(div);
            }
        });
    });
</script>

<style>
    .schedule-container {
        display: grid;
        grid-template-columns: 100px repeat(7, 1fr);
        gap: 2px;
        background: #ddd;
        padding: 2px;
        border-radius: 5px;
    }
    .time-column {
        background: #f8f9fa;
        text-align: center;
        padding: 10px;
        font-weight: bold;
    }
    .schedule-cell {
        background: white;
        min-height: 50px;
        position: relative;
        padding: 5px;
    }
    .pickup-block {
        background: #a0d468;
        color: white;
        position: absolute;
        width: calc(100% - 10px);
        text-align: center;
        padding: 5px;
        border-radius: 5px;
        font-size: 0.9em;
        line-height: 1.2;
    }
    .text-center {
        text-align: center;
    }
    .fw-bold {
        font-weight: bold;
    }
</style>

<?php include 'includes/footer.php'; ?>