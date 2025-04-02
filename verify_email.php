<?php
session_start();
require 'config/db.php';

// Set timezone to match your server
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

// Debug: Show the email being verified
error_log("Verifying email: " . $email);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    
    // Debug: Print the received OTP and email
    error_log("Received OTP: " . $otp . " for email: " . $email);
    
    // Debug: Check all OTPs for this email
    $check_all = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining FROM otp_verification WHERE email = ? ORDER BY created_at DESC";
    $check_stmt = $conn->prepare($check_all);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $all_results = $check_stmt->get_result();
    $all_otps = $all_results->fetch_all(MYSQLI_ASSOC);
    error_log("All OTPs for this email: " . print_r($all_otps, true));
    
    // Debug: Check current time and expiration
    $current_time = date('Y-m-d H:i:s');
    error_log("Current time (PHP): " . $current_time);
    
    // Get server time from MySQL
    $time_query = "SELECT NOW() as server_time";
    $time_result = $conn->query($time_query);
    $server_time = $time_result->fetch_assoc()['server_time'];
    error_log("Current time (MySQL): " . $server_time);
    
    if (!empty($all_otps)) {
        $latest_otp = $all_otps[0];
        error_log("Latest OTP details:");
        error_log("- Code: " . $latest_otp['otp_code']);
        error_log("- Created at: " . $latest_otp['created_at']);
        error_log("- Expires at: " . $latest_otp['expires_at']);
        error_log("- Is verified: " . $latest_otp['is_verified']);
        error_log("- Seconds remaining (MySQL): " . $latest_otp['seconds_remaining']);
        
        // Calculate time difference using PHP
        $expires_at = strtotime($latest_otp['expires_at']);
        $current_timestamp = strtotime($current_time);
        $time_remaining = $expires_at - $current_timestamp;
        error_log("Time remaining until expiration (PHP seconds): " . $time_remaining);
    }
    
    // Verify OTP with explicit time comparison
    $sql = "SELECT * FROM otp_verification 
            WHERE email = ? 
            AND otp_code = ? 
            AND is_verified = 0 
            AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debug: Print the query results
    if ($result->num_rows > 0) {
        error_log("OTP found in database");
        $row = $result->fetch_assoc();
        error_log("OTP details: " . print_r($row, true));
    } else {
        error_log("No matching OTP found in database");
        
        // Debug: Check if OTP exists but is expired
        $check_expired = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining 
                         FROM otp_verification 
                         WHERE email = ? AND otp_code = ? 
                         ORDER BY created_at DESC LIMIT 1";
        $expired_stmt = $conn->prepare($check_expired);
        $expired_stmt->bind_param("ss", $email, $otp);
        $expired_stmt->execute();
        $expired_result = $expired_stmt->get_result();
        
        if ($expired_result->num_rows > 0) {
            $expired_row = $expired_result->fetch_assoc();
            error_log("Found expired OTP: " . print_r($expired_row, true));
            error_log("Current time (PHP): " . $current_time);
            error_log("Current time (MySQL): " . $server_time);
            error_log("OTP expires at: " . $expired_row['expires_at']);
            error_log("OTP is_verified: " . $expired_row['is_verified']);
            error_log("Seconds remaining (MySQL): " . $expired_row['seconds_remaining']);
            
            // Calculate time difference for expired OTP
            $expires_at = strtotime($expired_row['expires_at']);
            $time_remaining = $expires_at - $current_timestamp;
            error_log("Time remaining for expired OTP (PHP seconds): " . $time_remaining);
        } else {
            error_log("No OTP found at all for this email");
        }
    }

    if ($result->num_rows > 0) {
        // Update OTP verification status
        $update_sql = "UPDATE otp_verification SET is_verified = 1 WHERE email = ? AND otp_code = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $email, $otp);
        $update_stmt->execute();

        // Update user verification status
        $user_sql = "UPDATE users SET is_verified = 1 WHERE email = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();

        unset($_SESSION['pending_email']);
        $_SESSION['success'] = "Email verified successfully! Please login.";
        header("Location: login.php");
        exit();
    } else {
        // Show more specific error message
        if ($expired_result->num_rows > 0) {
            $_SESSION['error'] = "OTP has expired. Please request a new one.";
        } else {
            $_SESSION['error'] = "Invalid OTP. Please check and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - SAD System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white text-center py-4">
                        <i class="bi bi-envelope-check nature-icon mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="mb-0">Verify Your Email</h3>
                        <p class="text-light mb-0">Enter the OTP sent to your email</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="otp" class="form-label">Enter OTP</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg text-center" id="otp" name="otp" maxlength="6" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Verify Email
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Didn't receive the OTP? <a href="resend_otp.php" class="text-primary">Resend</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 