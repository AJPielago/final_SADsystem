<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json'); // Ensure JSON response

error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false, "message" => "An error occurred. Please try again."];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        $response["message"] = "You must be logged in to submit feedback.";
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $feedback_message = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);

    if (!empty($feedback_message)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_message) VALUES (?, ?)");

        if ($stmt) {
            $stmt->bind_param("is", $user_id, $feedback_message);
            if ($stmt->execute()) {
                $response = ["success" => true, "message" => "Feedback submitted successfully!"];
            } else {
                $response["message"] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $response["message"] = "Failed to prepare SQL statement.";
        }
    } else {
        $response["message"] = "Feedback cannot be empty.";
    }
} else {
    $response["message"] = "Invalid request.";
}

echo json_encode($response);
?>
