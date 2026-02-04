-- Add password reset columns to users table
ALTER TABLE users 
ADD COLUMN password_reset_otp VARCHAR(255) NULL,
ADD COLUMN password_reset_expires DATETIME NULL,
ADD COLUMN password_reset_sent_at DATETIME NULL,
ADD COLUMN password_reset_attempts INT DEFAULT 0;
