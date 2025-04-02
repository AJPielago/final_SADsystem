<?php
session_start();
require 'config/db.php';
require 'includes/mailer.php';

// Set timezone to match your server
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

// Debug: Log resend attempt
error_log("Resending OTP for email: " . $email);

// Generate new OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Calculate expiry time more reliably
$current_time = new DateTime();
$expiry_time = clone $current_time;
$expiry_time->modify('+10 minutes');
$expires_at = $expiry_time->format('Y-m-d H:i:s');

// Debug: Log new OTP details
error_log("Generated new OTP: " . $otp);
error_log("OTP expires at: " . $expires_at);
error_log("Current time: " . $current_time->format('Y-m-d H:i:s'));

// First, mark all previous OTPs as verified to prevent confusion
$mark_previous = "UPDATE otp_verification SET is_verified = 1 WHERE email = ? AND is_verified = 0";
$mark_stmt = $conn->prepare($mark_previous);
$mark_stmt->bind_param("s", $email);
$mark_stmt->execute();

// Store new OTP
$sql = "INSERT INTO otp_verification (email, otp_code, expires_at, is_verified) VALUES (?, ?, ?, 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $otp, $expires_at);

if ($stmt->execute()) {
    error_log("New OTP stored successfully in database");
    
    // Send new OTP email
    if (sendOTPEmail($email, $otp)) {
        error_log("New OTP email sent successfully");
        $_SESSION['success'] = "New OTP has been sent to your email.";
    } else {
        error_log("Failed to send new OTP email");
        $_SESSION['error'] = "Failed to send OTP. Please try again.";
    }
} else {
    error_log("Failed to store new OTP in database: " . $stmt->error);
    $_SESSION['error'] = "Failed to generate new OTP. Please try again.";
}

header("Location: verify_email.php");
exit(); 