-- Add new_date and new_time columns to reschedule_requests table
ALTER TABLE reschedule_requests 
ADD COLUMN new_date DATE AFTER request_date,
ADD COLUMN new_time TIME AFTER new_date; 