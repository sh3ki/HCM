-- Add requires_password_change flag to users table
-- This flag is set to 1 when a user is created by admin with auto-generated password
-- User must change password before proceeding to OTP verification

USE hcm_system;

-- Add requires_password_change column (skip if exists)
ALTER TABLE users 
ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0 AFTER is_active;

-- Add is_new column (skip if exists, likely already exists)
-- ALTER TABLE users 
-- ADD COLUMN is_new TINYINT(1) DEFAULT 1 AFTER requires_password_change;
