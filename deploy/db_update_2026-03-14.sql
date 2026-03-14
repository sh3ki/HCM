-- HCM production DB update for 2026-03-14
-- Safe/idempotent migration for admin OTP login and account flags
-- IMPORTANT: In phpMyAdmin, select your target database first (production: hr4_hcm_system)
-- then run/import this script.

-- 1) Ensure users table has required columns
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'auto_password_changed') = 0,
    'ALTER TABLE users ADD COLUMN auto_password_changed TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''True when using auto-generated password, false after user changes it''',
    'SELECT ''users.auto_password_changed already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_new') = 0,
    'ALTER TABLE users ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''True for new accounts requiring OTP verification, false after verified''',
    'SELECT ''users.is_new already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'requires_password_change') = 0,
    'ALTER TABLE users ADD COLUMN requires_password_change TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Prompt user to change password on next login''',
    'SELECT ''users.requires_password_change already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Ensure password reset columns exist (safe even if forgot password UI is hidden)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_otp') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_otp VARCHAR(255) NULL',
    'SELECT ''users.password_reset_otp already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_expires') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL',
    'SELECT ''users.password_reset_expires already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_sent_at') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_sent_at DATETIME NULL',
    'SELECT ''users.password_reset_sent_at already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_attempts') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_attempts INT NOT NULL DEFAULT 0',
    'SELECT ''users.password_reset_attempts already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Ensure user_otps table exists for OTP verification workflow
CREATE TABLE IF NOT EXISTS user_otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp_code VARCHAR(255) NOT NULL,
    otp_expires_at DATETIME NULL,
    otp_last_sent_at DATETIME NULL,
    otp_attempts INT NOT NULL DEFAULT 0,
    otp_verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_otps_user_id (user_id),
    KEY idx_user_otps_expires (otp_expires_at),
    CONSTRAINT fk_user_otps_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3b) Normalize existing user_otps schema (important if table already existed with short otp_code)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_otps' AND COLUMN_NAME = 'otp_code') = 0,
    'ALTER TABLE user_otps ADD COLUMN otp_code VARCHAR(255) NOT NULL',
    'SELECT ''user_otps.otp_code exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE user_otps
    MODIFY COLUMN otp_code VARCHAR(255) NOT NULL,
    MODIFY COLUMN otp_expires_at DATETIME NULL,
    MODIFY COLUMN otp_last_sent_at DATETIME NULL,
    MODIFY COLUMN otp_attempts INT NOT NULL DEFAULT 0,
    MODIFY COLUMN otp_verified_at DATETIME NULL;

-- 4) Optional normalization for existing accounts
UPDATE users
SET is_new = 0
WHERE is_new IS NULL;

SELECT 'DB update 2026-03-14 completed successfully' AS status;
