<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Check if user is admin
    $sql_role = "SELECT role FROM users WHERE user_id = ?";
    $stmt_role = $conn->prepare($sql_role);
    $stmt_role->bind_param("i", $user_id);
    $stmt_role->execute();
    $result_role = $stmt_role->get_result();
    $user = $result_role->fetch_assoc();

    if ($user['role'] !== 'admin') {
        echo "Unauthorized access.";
        exit();
    }

    // Process feedback review
    if (isset($_POST['feedback_id'])) {
        $feedback_id = $_POST['feedback_id'];

        $sql_update = "UPDATE feedback SET status = 'Reviewed' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $feedback_id);

        if ($stmt_update->execute()) {
            header("Location: ../view_feedback.php?success=Feedback marked as reviewed");
            exit();
        } else {
            header("Location: ../view_feedback.php?error=Failed to update feedback");
            exit();
        }
    }
}
?>
