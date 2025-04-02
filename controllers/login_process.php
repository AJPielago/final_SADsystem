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

    // Updated SQL to include is_verified
    $stmt = $conn->prepare("SELECT user_id, email, password, role, is_verified FROM users WHERE email = ?");

    // Check if query preparation was successful
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_email, $hashed_password, $role, $is_verified);
        $stmt->fetch();

        // Check if email is verified
        if (!$is_verified) {
            // Store email in session for verification
            $_SESSION['pending_email'] = $email;
            
            // Generate new OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store OTP
            $otp_sql = "INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, ?)";
            $otp_stmt = $conn->prepare($otp_sql);
            $otp_stmt->bind_param("sss", $email, $otp, $expires_at);
            $otp_stmt->execute();

            // Send verification email
            require '../includes/mailer.php';
            if (sendOTPEmail($email, $otp)) {
                error_log("New OTP sent to: " . $email);
                error_log("OTP: " . $otp);
                error_log("Expires at: " . $expires_at);
                header("Location: ../verify_email.php");
            } else {
                header("Location: ../login.php?error=Failed to send verification email. Please contact support.");
            }
            exit();
        }

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