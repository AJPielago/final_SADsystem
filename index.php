<?php 
session_start(); 
include 'includes/header.php'; 
?>

<style>
/* Ensure full height layout */
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    font-family: 'Poppins', sans-serif;
}

/* Ensure navbar stays at the top */
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

/* Banner should be full width and just below the navbar */
.banner {
    width: 100%;
    height: 300px;
    background: url('assets/forest.jpg') no-repeat center center;
    background-size: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    margin-top: 56px;
}

/* Banner text styling */
.banner-content {
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 20px 40px;
    border-radius: 10px;
    text-align: center;
}

/* Community Hub Button Container */
.community-hub-container {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

/* Modern Square Button */
.community-hub-box {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    text-decoration: none;
    width: 300px; /* Square Shape */
    height: 300px; /* Square Shape */
    border-radius: 20px;
    overflow: hidden;
    background: linear-gradient(135deg, #2ECC71, #1E8449);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Frosted Glass Effect */
.community-hub-box::before {
    content: "";
    position: absolute;
    width: 90%;
    height: 90%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    top: 5%;
    left: 5%;
}

/* Button hover effect */
.community-hub-box:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

/* Icon */
/* Icon */
.community-hub-box img {
    width: 140px; /* Increased image size */
    height: 140px;
    margin-bottom: 10px;
    z-index: 2;
}


/* Text */
.community-hub-box h2 {
    font-size: 22px;
    color: white;
    margin: 0;
    z-index: 2;
    text-align: center;
}

.community-hub-box p {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 5px;
    text-align: center;
    z-index: 2;
}

/* Make sure the main content takes up available space */
.main-content {
    flex: 1;
    padding: 20px;
    text-align: center;
}

/* Push the footer to the bottom without scrolling */
.footer {
    width: 100%;
    position: fixed;
    bottom: 0;
}
</style>

<!-- Banner Section -->
<div class="banner">
    <div class="banner-content">
        <h1>Welcome to Green Bin</h1>
        <p>A smart waste management system for your community.</p>

        <!-- Login/Register Buttons inside Banner -->
        <div class="mt-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                    require_once 'config/db.php';

                    // Get user role
                    $user_id = $_SESSION['user_id'];
                    $sql_role = "SELECT role FROM users WHERE user_id = ?";
                    
                    if ($stmt_role = $conn->prepare($sql_role)) {
                        $stmt_role->bind_param("i", $user_id);
                        $stmt_role->execute();
                        $result_role = $stmt_role->get_result();

                        if ($result_role->num_rows > 0) {
                            $user = $result_role->fetch_assoc();
                            $role = $user['role'];

                            // Redirect to the correct dashboard
                            if ($role === 'admin') {
                                $dashboard_link = "admin_dashboard.php";
                            } elseif ($role === 'collector') {
                                $dashboard_link = "collector_dashboard.php";
                            } else {
                                $dashboard_link = "dashboard.php"; // Default user dashboard
                            }
                        } else {
                            $dashboard_link = "index.php"; // Fallback in case of an issue
                        }
                        $stmt_role->close();
                    } else {
                        $dashboard_link = "index.php"; // Fallback if query fails
                    }
                ?>
                <a href="<?= htmlspecialchars($dashboard_link) ?>" class="btn btn-warning btn-lg">Go to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg me-3">Login</a>
                <a href="register.php" class="btn btn-success btn-lg">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Community Hub Square Button -->
<div class="community-hub-container">
    <a href="community_hub.php" class="community-hub-box">
        <img src="assets/education.png" alt="Community Hub">
        <h2>Community Hub</h2>
    </a>
</div>

<!-- Main Content Section -->
<div class="main-content">
    <h2>Learn More About Waste Management</h2>
    <p>Discover useful tips and resources to make waste disposal easier and more sustainable.</p>
</div>

<?php include 'includes/footer.php'; ?>
