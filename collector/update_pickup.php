<?php
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    $sql = "UPDATE pickuprequests SET status = 'completed' WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute()) {
        header("Location: ../front-end/assigned_pickups.php?success=Pickup completed successfully.");
    } else {
        header("Location: ../front-end/assigned_pickups.php?error=Failed to update status.");
    }
    exit();
}
?>
