<?php
require_once 'C:/xamppSAD/htdocs/SADsystem/vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    die('PHPMailer class not found. Check autoload path.');
}
if (!class_exists('NotificationHelper')) {
    class NotificationHelper {
        private $conn;
        private $mailer;

        public function __construct($conn) {
            $this->conn = $conn;
            
            // Initialize PHPMailer
            $this->mailer = new PHPMailer(true);
            
            try {
                // Server settings
                $this->mailer->isSMTP();
                $this->mailer->Host = SMTP_HOST;
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = SMTP_USERNAME;
                $this->mailer->Password = SMTP_PASSWORD;
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = SMTP_PORT;

                // Sender details
                $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            } catch (Exception $e) {
                error_log("Mailer Error: " . $e->getMessage());
            }
        }

        public function createNotification($userId, $pickupRequestId, $type, $message, $isAdminNotification = false) {
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, pickup_request_id, type, message, is_admin_notification) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissi", $userId, $pickupRequestId, $type, $message, $isAdminNotification);
                $stmt->execute();
                $stmt->close();
            }

            if (!$isAdminNotification) {
                $stmt = $this->conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($user = $result->fetch_assoc()) {
                        $this->sendEmailNotification($user['email'], $user['full_name'], $message);
                    }
                    $stmt->close();
                }
            }
        }

        public function createAdminNotification($pickupRequestId, $type, $message) {
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'admin'");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                while ($admin = $result->fetch_assoc()) {
                    $this->createNotification($admin['user_id'], $pickupRequestId, $type, $message, true);
                }
                $stmt->close();
            }
        }

        private function sendEmailNotification($email, $name, $message) {
            try {
                $this->mailer->addAddress($email, $name);
                $this->mailer->isHTML(true);
                $this->mailer->Subject = 'Pickup Request Update';
                $this->mailer->Body = "
                    <h2>Pickup Request Update</h2>
                    <p>Dear {$name},</p>
                    <p>{$message}</p>
                    <p>Thank you for using our service!</p>
                ";
                $this->mailer->send();
            } catch (Exception $e) {
                error_log("Email sending failed: " . $e->getMessage());
            } finally {
                $this->mailer->clearAddresses(); // Ensure no duplicate emails on next send
            }
        }

        public function getUnreadNotifications($userId, $isAdmin = false) {
            $notifications = [];
            $stmt = $this->conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 AND is_admin_notification = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $userId, $isAdmin);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $notifications[] = $row;
                }
                $stmt->close();
            }
            return $notifications;
        }

        public function markAsRead($notificationId, $userId) {
            $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $notificationId, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }

        public function markAllAsRead($userId) {
            $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
