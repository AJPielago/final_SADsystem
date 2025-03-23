<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields.");
        exit();
    }

    // Check user credentials
    $sql = "SELECT user_id, password, LOWER(role) FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        // DEBUGGING: Echo role to check what is retrieved
        echo "Retrieved role from database: " . $role . "<br>";

        if (password_verify($password, $hashed_password)) {
            // Store session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = ucfirst($role); // Capitalize first letter

            // DEBUGGING: Echo session role
            echo "Session role stored: " . $_SESSION['role'] . "<br>";

            // Force lowercase comparison to ensure collector role works
            if (strtolower($role) === 'admin') {
                echo "Redirecting to admin_dashboard.php...";
                header("Location: admin_dashboard.php");
                exit();
            } elseif (strtolower($role) === 'collector') {
                echo "Redirecting to collector_dashboard.php...";
                header("Location: collector_dashboard.php");
                exit();
            } else {
                echo "Redirecting to dashboard.php...";
                header("Location: dashboard.php");
                exit();
            }
        } else {
            header("Location: login.php?error=Invalid password.");
            exit();
        }
    } else {
        header("Location: login.php?error=No account found with this email.");
        exit();
    }
    $stmt->close();
    $conn->close();
}
?>
