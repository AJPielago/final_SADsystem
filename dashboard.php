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

// Get the user's building_id
$user_id = $_SESSION['user_id'];
$user_query = "SELECT building_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$building_id = $user_data['building_id'];
$stmt->close();

// Fetch building schedule for the user's building
$building_query = "SELECT building_name, schedule FROM buildings WHERE building_id = ?";
$stmt = $conn->prepare($building_query);
$stmt->bind_param("i", $building_id);
$stmt->execute();
$building_result = $stmt->get_result();
$building_data = $building_result->fetch_assoc();
$building_name = $building_data['building_name'];
$fixed_schedule = $building_data['schedule']; // Assuming schedule is stored in a time format like '10:00 AM'
$stmt->close();

// Days of the week mapping
$days_of_week = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

// Normalize the fixed schedule to 24-hour format for comparison
$fixed_schedule_24hr = date("H:i", strtotime($fixed_schedule));
?>

<div class="container" style="margin-top: 80px;">
    <h2 class="text-center">Weekly Pickup Schedule for <?= htmlspecialchars($building_name); ?></h2>
    <div class="schedule-container">
        <div class="time-column"></div>
        <?php foreach ($days_of_week as $day) echo "<div class='text-center fw-bold'>$day</div>"; ?>
        
        <?php
        // Loop through each hour from 7 AM to 6 PM (18:00)
        for ($hour = 7; $hour <= 18; $hour++) {
            $time_label = date("h:i A", strtotime("$hour:00"));
            $time_24hr = date("H:i", strtotime("$hour:00"));
            echo "<div class='time-column'>$time_label</div>";
            foreach ($days_of_week as $day) {
                echo "<div class='schedule-cell' data-day='$day' data-time='$time_24hr'>";
                // Check if the time matches the fixed schedule
                if ($time_24hr == $fixed_schedule_24hr) {
                    echo "<div class='pickup-block'>Pickup Scheduled</div>";
                }
                echo "</div>";
            }
        }
        ?>
    </div>
</div> 

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Additional interactivity can be added here if needed
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
