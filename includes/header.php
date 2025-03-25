<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and fetch user details
$user_role = '';
$user_id = '';
$full_name = 'User';
$points = 0;
$building_id = null;
$isFirstResident = false;
$isLoggedIn = isset($_SESSION['user_id']);
$showPickupOptions = false; // Controls "Request Pickup" & "Pickup History" visibility

if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];

    // Fetch user details including building_id
    $sql_user = "SELECT full_name, role, points, building_id FROM users WHERE user_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $full_name = htmlspecialchars($user['full_name'] ?? 'User');
        $user_role = $user['role'] ?? '';
        $points = $user['points'] ?? 0;
        $building_id = $user['building_id'] ?? null;
    }
    $stmt_user->close();

    // Check if the user is the first resident in their building
    if ($building_id !== null) {
        $stmt = $conn->prepare("SELECT MIN(user_id) FROM users WHERE building_id = ?");
        $stmt->bind_param("i", $building_id);
        $stmt->execute();
        $stmt->bind_result($lowest_user_id);
        $stmt->fetch();
        $stmt->close();

        if ($user_id == $lowest_user_id) {
            $isFirstResident = true;
            $showPickupOptions = true; // Enable visibility
        }
    }
}

// For completed pickups
$sql_completed = "SELECT pr.request_id, pr.status, pr.created_at, pr.building_id, b.building_name, 
                  pr.latitude, pr.longitude, u.full_name as resident_name
                  FROM pickuprequests pr 
                  LEFT JOIN buildings b ON pr.building_id = b.building_id
                  LEFT JOIN users u ON pr.user_id = u.user_id
                  WHERE pr.status = 'completed'";

// For pending pickups
$sql_pending = "SELECT pr.request_id, pr.status, pr.created_at, pr.building_id, b.building_name, 
                pr.latitude, pr.longitude, u.full_name as resident_name
                FROM pickuprequests pr 
                LEFT JOIN buildings b ON pr.building_id = b.building_id
                LEFT JOIN users u ON pr.user_id = u.user_id
                WHERE pr.status = 'pending'";

if ($user_role === 'resident') {
    $sql_pending .= " AND pr.user_id = ?";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param("i", $user_id);
} else {
    $sql_pending .= " ORDER BY pr.created_at DESC";
    $stmt_pending = $conn->prepare($sql_pending);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAD System - Smart Automated Disposal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer fix CSS -->
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
        
        /* Fixed styles for dropdown functionality */
        .dropdown-menu {
      position: absolute !important;
      left: 50% !important; /* Center horizontally */
      transform: translateX(-50%) !important; /* Adjust for half width shift */
      z-index: 2000;
      display: none;
  }
        
        .dropdown-menu.show {
            display: block !important;
        }
        
        /* Style for notification bell */
        .notification-bell {
            cursor: pointer;
            position: relative;
        }
        
        /* Ensure sidebar doesn't interfere with dropdowns */
        #sidebar {
            z-index: 1020;
        }
        
        /* Make sure dropdown is visible */
        .dropdown {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <?php if ($isLoggedIn): ?>
                <button id="sidebarToggle" class="btn btn-outline-light me-2">
                    <i class="bi bi-list"></i>
                </button>
            <?php endif; ?>
            
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-recycle nature-icon"></i>
                Green Bin
            </a>

            <?php if ($isLoggedIn): ?>
                <div class="d-flex align-items-center">
                    <!-- Notifications Dropdown (Simplified) -->
                    <div class="dropdown me-3">
                        <a href="#" class="nav-link text-light position-relative notification-bell" id="notificationBell">
                            <i class="bi bi-bell"></i>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            require_once __DIR__ . '/../vendor/autoload.php';
                            $notificationHelper = new \App\Includes\NotificationHelper($conn);
                            $isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
                            $notifications = $notificationHelper->getUnreadNotifications($_SESSION['user_id'], $isAdmin);
                            $unreadCount = count($notifications);
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" id="notificationsMenu">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (empty($notifications)): ?>
                                <a class="dropdown-item" href="#">No new notifications</a>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a class="dropdown-item notification-item" href="#" data-notification-id="<?= $notification['notification_id'] ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php
                                                $icon = '';
                                                switch ($notification['type']) {
                                                    case 'approved':
                                                        $icon = '<i class="bi bi-check-circle-fill text-success"></i>';
                                                        break;
                                                    case 'rejected':
                                                        $icon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                                                        break;
                                                    case 'rescheduled':
                                                        $icon = '<i class="bi bi-calendar-check text-warning"></i>';
                                                        break;
                                                    case 'new_request':
                                                        $icon = '<i class="bi bi-plus-circle-fill text-primary"></i>';
                                                        break;
                                                }
                                                echo $icon;
                                                ?>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <div class="small text-muted"><?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?></div>
                                                <div><?= htmlspecialchars($notification['message']) ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#" id="markAllAsRead">Mark all as read</a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile Button -->
                    <a href="profile.php" class="btn btn-outline-light">
                        <i class="bi bi-person-circle"></i> <?php echo $full_name; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <?php if ($isLoggedIn): ?>
        <div id="sidebar">
            <div class="sidebar-header">
                <h3>Green Bin</h3>
            </div>
            <ul class="list-unstyled">
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="admin_dashboard.php"><i class="bi bi-speedometer"></i> Dashboard</a></li>
                    <li><a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a></li>
                    <li><a href="manage_schedules.php"><i class="bi bi-calendar-check"></i> Manage Pickup Schedules</a></li>
                    <li><a href="reschedule_requests.php"><i class="bi bi-clock-history"></i> Reschedule Requests</a></li>
                    <li><a href="view_reports.php"><i class="bi bi-clipboard-data"></i> View Reports</a></li>
                    <li><a href="view_feedback.php"><i class="bi bi-chat-text"></i> View Feedback</a></li>
                <?php elseif ($user_role === 'collector'): ?>
                    <li><a href="collector_dashboard.php"><i class="bi bi-truck"></i> Dashboard</a></li>
                    <li><a href="assigned_pickups.php"><i class="bi bi-clipboard-check"></i> Assigned Pickups</a></li>
                    <li><a href="pickup_history.php"><i class="bi bi-clock-history"></i> Pickup History</a></li>
                <?php else: ?>
                    <li><a href="dashboard.php"><i class="bi bi-speedometer"></i> Dashboard</a></li>
                    <?php if ($showPickupOptions): ?>
                        <li><a href="pickup.php"><i class="bi bi-truck"></i> Request Pickup</a></li>
                        <li><a href="pickup_history.php"><i class="bi bi-clock-history"></i> Recent Activities</a></li>
                        <li><a href="redeem_rewards.php"><i class="bi bi-gift"></i> Redeem Rewards</a></li>
                    <?php endif; ?>
                    <li><a href="report_issue.php"><i class="bi bi-exclamation-triangle"></i> Report Issue</a></li>
                    <li><a href="donations.php"><i class="bi bi-gift"></i> Donate an Item</a></li>
                    <hr class="bg-light">
                    <li><a href="user_reschedule.php"><i class="bi bi-clock-history"></i> Manage Reschedule Requests</a></li>
                <?php endif; ?>

                <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="container py-4">
    
    <!-- Custom JavaScript for Notifications - Implemented with manual toggle -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM fully loaded");
        
        // Handle sidebar toggle
        const sidebarToggle = document.getElementById("sidebarToggle");
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function() {
                document.getElementById("sidebar").classList.toggle("show");
            });
        }
        
        // Manual notification dropdown toggle
        const notificationBell = document.getElementById('notificationBell');
        const notificationsMenu = document.getElementById('notificationsMenu');
        
        if (notificationBell && notificationsMenu) {
            notificationBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                notificationsMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!notificationsMenu.contains(e.target) && e.target !== notificationBell) {
                    notificationsMenu.classList.remove('show');
                }
            });
        }
        
        // Handle notification item click
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const notificationId = this.getAttribute('data-notification-id');
                markNotificationAsRead(notificationId);
            });
        });
        
        // Handle mark all as read
        const markAllBtn = document.getElementById('markAllAsRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                markAllNotificationsAsRead();
            });
        }
    });
    
    function markNotificationAsRead(notificationId) {
        console.log("Marking notification as read:", notificationId);
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            console.log("Response:", data);
            if (data.success) {
                // Remove the notification from the dropdown
                const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notification) {
                    notification.remove();
                }
                
                // Update the notification count
                const badge = document.querySelector('#notificationBell .badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent);
                    if (currentCount > 1) {
                        badge.textContent = currentCount - 1;
                    } else {
                        badge.remove();
                    }
                }
                
                // If no more notifications, update the dropdown content
                const remainingNotifications = document.querySelectorAll('.notification-item');
                if (remainingNotifications.length === 0) {
                    document.getElementById('notificationsMenu').innerHTML = '<a class="dropdown-item" href="#">No new notifications</a>';
                }
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    function markAllNotificationsAsRead() {
        console.log("Marking all notifications as read");
        fetch('mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log("Response:", data);
            if (data.success) {
                // Clear all notifications from the dropdown
                document.getElementById('notificationsMenu').innerHTML = '<a class="dropdown-item" href="#">No new notifications</a>';
                
                // Remove the notification count badge
                const badge = document.querySelector('#notificationBell .badge');
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    }
    </script>