<?php
namespace App\Includes;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class NotificationHelper {
    private $conn;
    private $mailer;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USERNAME;
        $this->mailer->Password = SMTP_PASSWORD;
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    }

    public function createNotification($userId, $pickupRequestId, $type, $message, $isAdminNotification = false, $sendEmail = false) {
        try {
            // Insert notification into database
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, pickup_request_id, type, message, is_admin_notification, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("Failed to prepare notification insert statement: " . $this->conn->error);
            }
            $stmt->bind_param("iissi", $userId, $pickupRequestId, $type, $message, $isAdminNotification);
            $success = $stmt->execute();
            if (!$success) {
                throw new Exception("Failed to insert notification: " . $stmt->error);
            }
            $stmt->close();

            // Send email if requested
            if ($sendEmail) {
                // Get user email
                $stmt = $this->conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
                if (!$stmt) {
                    throw new Exception("Failed to prepare user select statement: " . $this->conn->error);
                }
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && !empty($user['email'])) {
                    try {
                        $this->mailer->clearAddresses();
                        $this->mailer->addAddress($user['email'], $user['full_name']);
                        $this->mailer->isHTML(false);
                        
                        // Set subject based on notification type
                        $subject = "Waste Collection Update";
                        if ($type === 'approved') {
                            $subject = "Pickup Request Approved";
                        } else if ($type === 'building_pickup_approved') {
                            $subject = "New Pickup Scheduled in Your Building";
                        } else if ($type === 'reschedule_approved') {
                            $subject = "Reschedule Request Approved";
                        } else if ($type === 'reschedule_rejected') {
                            $subject = "Reschedule Request Rejected";
                        } else if ($type === 'building_reschedule_approved') {
                            $subject = "Building Pickup Schedule Changed";
                        }
                        
                        $this->mailer->Subject = $subject;
                        $this->mailer->Body = $message;
                        $this->mailer->send();
                    } catch (Exception $e) {
                        error_log("Failed to send email: " . $e->getMessage());
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in createNotification: " . $e->getMessage());
            throw $e;
        }
    }

    public function createAdminNotification($pickupRequestId, $type, $message) {
        try {
            // Get all admin users
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'admin'");
            if (!$stmt) {
                throw new Exception("Failed to prepare admin select statement: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $success = true;
            while ($admin = $result->fetch_assoc()) {
                if (!$this->createNotification($admin['user_id'], $pickupRequestId, $type, $message, true)) {
                    $success = false;
                    error_log("Failed to create notification for admin ID: " . $admin['user_id']);
                }
            }
            
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error in createAdminNotification: " . $e->getMessage());
            return false;
        }
    }

    private function sendEmailNotification($email, $name, $message) {
        if (empty($email) || empty($name)) {
            error_log("Invalid email or name provided to sendEmailNotification");
            return false;
        }

        try {
            // Reset all recipients and reply-to
            $this->mailer->clearAllRecipients();
            $this->mailer->clearReplyTos();
            
            // Set up email
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Pickup Request Update';
            
            // Create HTML email body with proper escaping
            $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { padding: 20px; }
                        .header { color: #2c3e50; }
                        .content { margin: 20px 0; }
                        .footer { color: #7f8c8d; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2 class='header'>Pickup Request Update</h2>
                        <div class='content'>
                            <p>Dear " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p>
                            <p>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>
                        </div>
                        <div class='footer'>
                            <p>Thank you for using our service!</p>
                            <p>Green Bin Waste Management System</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));
            
            // Send email
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed to {$email}. Error: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadNotifications($userId, $isAdmin = false) {
        try {
            $stmt = $this->conn->prepare("
                SELECT n.* 
                FROM notifications n
                WHERE n.user_id = ? AND n.is_read = 0 AND n.is_admin_notification = ?
                ORDER BY n.created_at DESC
                LIMIT 10
            ");
            if (!$stmt) {
                throw new Exception("Failed to prepare unread notifications statement: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $userId, $isAdmin);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            $stmt->close();
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in getUnreadNotifications: " . $e->getMessage());
            return [];
        }
    }

    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare mark as read statement: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $notificationId, $userId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error in markAsRead: " . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare mark all as read statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $userId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error in markAllAsRead: " . $e->getMessage());
            return false;
        }
    }
}