-- Add auto_password_changed and is_new columns to users table
-- auto_password_changed: Tracks if user is still using auto-generated password
-- is_new: Tracks if user needs OTP verification

USE hcm_system;

-- Add auto_password_changed column
-- Set to 1 when password is auto-generated, set to 0 when user changes it
ALTER TABLE users 
ADD COLUMN auto_password_changed TINYINT(1) DEFAULT 0 COMMENT 'True when using auto-generated password, false after user changes it';

-- Add is_new column 
-- Set to 1 when account is created, set to 0 after OTP verification
ALTER TABLE users 
ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT 'True for new accounts requiring OTP verification, false after verified';

-- Add requires_password_change column if it doesn't exist
ALTER TABLE users 
ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0 COMMENT 'Prompt user to change password on next login';

-- Update existing users to have is_new = 0 (already verified)
UPDATE users SET is_new = 0, auto_password_changed = 0 WHERE is_new IS NULL OR is_new = 1;

SELECT 'Migration completed successfully!' AS status;
