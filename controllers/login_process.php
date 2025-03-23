<?php
session_start();
require '../config/db.php'; // Ensure this file correctly connects to the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Debug: Check if database connection is working
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");

    // Check if query preparation was successful
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_email, $hashed_password, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = ucfirst($role);

            // ✅ Set collector session variable
            if (strtolower($role) === 'collector') {
                $_SESSION['collector_id'] = $user_id;
            }

            // Redirect based on role
            if (strtolower($role) === 'admin') {
                header("Location: ../admin_dashboard.php");
                exit();
            } elseif (strtolower($role) === 'collector') {
                header("Location: ../collector_dashboard.php");
                $stmt->close();
                exit();
            } else {
                header("Location: ../dashboard.php");
                $stmt->close();
                exit();
            }
        } else {
            $stmt->close();
            header("Location: ../login.php?error=Invalid credentials");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: ../login.php?error=User not found");
        exit();
    }
}
?>