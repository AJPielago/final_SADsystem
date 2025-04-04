<?php
session_start();
require 'config/db.php';
require 'includes/mailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number']; // Capture phone number
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $building_id = $_POST['building_id'];
    $role = 'resident'; // Default role for registration

    // Debug: Log registration attempt
    error_log("Registration attempt for email: " . $email);

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check_email = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user with is_verified = 0
            $sql = "INSERT INTO users (full_name, email, phone_number, password, role, building_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $full_name, $email, $phone_number, $hashed_password, $role, $building_id);

            if ($stmt->execute()) {
                // Generate OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Debug: Log OTP generation
                error_log("Generated OTP: " . $otp . " for email: " . $email);
                error_log("OTP expires at: " . $expires_at);

                // Store OTP
                $otp_sql = "INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, ?)";
                $otp_stmt = $conn->prepare($otp_sql);
                $otp_stmt->bind_param("sss", $email, $otp, $expires_at);

                if ($otp_stmt->execute()) {
                    // Debug: Log successful OTP storage
                    error_log("OTP stored successfully in database");

                    // Send verification email
                    if (sendOTPEmail($email, $otp)) {
                        error_log("Verification email sent successfully");
                        $_SESSION['pending_email'] = $email;
                        header("Location: verify_email.php");
                        exit();
                    } else {
                        error_log("Failed to send verification email");
                        $_SESSION['error'] = "Registration successful but failed to send verification email. Please contact support.";
                        header("Location: login.php");
                        exit();
                    }
                } else {
                    error_log("Failed to store OTP in database: " . $otp_stmt->error);
                    $_SESSION['error'] = "Registration successful but failed to generate verification code. Please contact support.";
                    header("Location: login.php");
                    exit();
                }
            } else {
                error_log("Failed to create user: " . $stmt->error);
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        }
    }
}

// Fetch buildings for dropdown
$buildings_query = "SELECT building_id, building_name FROM buildings ORDER BY building_name";
$buildings_result = mysqli_query($conn, $buildings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SAD System</title>
    
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
                        <i class="bi bi-recycle nature-icon mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="mb-0">Create Account</h3>
                        <p class="text-light mb-0">Join our eco-friendly community</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <!-- Phone Number Field -->
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="building_id" class="form-label">Select Building</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-building"></i>
                                    </span>
                                    <select class="form-select" id="building_id" name="building_id" required>
                                        <option value="">Choose a building...</option>
                                        <?php while ($building = mysqli_fetch_assoc($buildings_result)): ?>
                                            <option value="<?= $building['building_id']; ?>">
                                                <?= htmlspecialchars($building['building_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus"></i> Register
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
