-- Update notifications table
ALTER TABLE `notifications` 
ADD COLUMN `pickup_request_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD COLUMN `type` enum('approved','rejected','rescheduled') NOT NULL AFTER `pickup_request_id`,
ADD KEY `pickup_request_id` (`pickup_request_id`),
ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`pickup_request_id`) REFERENCES `pickuprequests` (`request_id`) ON DELETE CASCADE; 