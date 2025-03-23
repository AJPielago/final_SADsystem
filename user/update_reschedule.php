<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reschedule_id']) && isset($_POST['new_day']) && isset($_POST['new_time'])) {
        
        $conn->begin_transaction();

        try {
            foreach ($_POST['reschedule_id'] as $reschedule_id) {
                $new_day = $_POST['new_day'][$reschedule_id];
                $new_time = $_POST['new_time'][$reschedule_id];

                $dayOfWeek = [
                    'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
                    'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7
                ];

                $currentDayNum = date('N');
                $targetDayNum = $dayOfWeek[$new_day];
                $daysToAdd = ($targetDayNum - $currentDayNum + 7) % 7;
                if ($daysToAdd === 0) {
                    $daysToAdd = 7;
                }

                $new_date = date('Y-m-d', strtotime("+$daysToAdd days"));

                $verify_query = "
                    SELECT rr.request_id, rr.user_id
                    FROM reschedule_requests rr
                    JOIN pickuprequests pr ON rr.request_id = pr.request_id
                    WHERE rr.reschedule_id = ? AND pr.user_id = ? AND rr.status = 'Approved'
                ";

                $verify_stmt = $conn->prepare($verify_query);
                if ($verify_stmt === false) {
                    throw new Exception("Error preparing verification query: " . $conn->error);
                }

                $verify_stmt->bind_param("ii", $reschedule_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {
                    throw new Exception("You don't have permission to update this request or it's not approved");
                }

                $request_data = $verify_result->fetch_assoc();
                $request_id = $request_data['request_id'];

                $update_query = "UPDATE reschedule_requests SET new_date = ?, new_time = ? WHERE reschedule_id = ?";
                $update_stmt = $conn->prepare($update_query);
                if ($update_stmt === false) {
                    throw new Exception("Error preparing reschedule update statement: " . $conn->error);
                }

                $update_stmt->bind_param("ssi", $new_date, $new_time, $reschedule_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating reschedule request: " . $update_stmt->error);
                }

                $pickup_update_query = "UPDATE pickuprequests 
                                        SET pickup_date = ?, pickup_time = ?
                                        WHERE request_id = ?";

                $pickup_update_stmt = $conn->prepare($pickup_update_query);
                if ($pickup_update_stmt === false) {
                    throw new Exception("Error preparing pickup update statement: " . $conn->error);
                }

                $pickup_update_stmt->bind_param("ssi", $new_date, $new_time, $request_id);
                if (!$pickup_update_stmt->execute()) {
                    throw new Exception("Error updating pickup request: " . $pickup_update_stmt->error);
                }

                // Fetch building_id, latitude, and longitude using request_id
                $get_location_query = "SELECT building_id, latitude, longitude FROM pickuprequests WHERE request_id = ?";
                $get_location_stmt = $conn->prepare($get_location_query);
                if ($get_location_stmt === false) {
                    throw new Exception("Error preparing location query: " . $conn->error);
                }

                $get_location_stmt->bind_param("i", $request_id);
                $get_location_stmt->execute();
                $get_location_result = $get_location_stmt->get_result();

                if ($get_location_result->num_rows === 0) {
                    throw new Exception("No location data found for request_id: " . $request_id);
                }

                $location_data = $get_location_result->fetch_assoc();
                $building_id = $location_data['building_id'];
                $latitude = $location_data['latitude'];
                $longitude = $location_data['longitude'];

                // Prepare insert statement using retrieved values
                $pickup_insert_query = "INSERT INTO pickuprequests (user_id, building_id, latitude, longitude) VALUES (?, ?, ?, ?)";
                $pickup_insert_stmt = $conn->prepare($pickup_insert_query);
                if ($pickup_insert_stmt === false) {
                    throw new Exception("Error preparing pickup insert statement: " . $conn->error);
                }

                $pickup_insert_stmt->bind_param("iidd", $user_id, $building_id, $latitude, $longitude);
                if (!$pickup_insert_stmt->execute()) {
                    throw new Exception("Error inserting new pickup request: " . $pickup_insert_stmt->error);
                }

                $delete_query = "DELETE FROM reschedule_requests WHERE reschedule_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                if ($delete_stmt === false) {
                    throw new Exception("Error preparing delete statement: " . $conn->error);
                }

                $delete_stmt->bind_param("i", $reschedule_id);
                if (!$delete_stmt->execute()) {
                    throw new Exception("Error deleting reschedule request: " . $delete_stmt->error);
                }

                // JavaScript to remove the processed reschedule entry from the page
                echo "<script>
                    document.getElementById('reschedule-$reschedule_id').remove();
                </script>";
            }

            $conn->commit();

            // Notify the user that their request has been queued again
            echo "<div class='alert alert-success'>Your pickup request has been successfully queued again!</div>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Missing required data</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Invalid request method</div>";
}
?>
