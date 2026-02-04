# Auto-Password and OTP Verification Implementation

## Overview

This implementation adds two important tracking columns to the `users` table:

1. **`auto_password_changed`** - Tracks if user is still using auto-generated password
2. **`is_new`** - Tracks if user needs OTP verification

## Column Details

### 1. `auto_password_changed` (TINYINT)
**Purpose:** Track whether user has changed from auto-generated password

**Values:**
- `1` (TRUE) - User is still using auto-generated password (set during employee creation)
- `0` (FALSE) - User has changed their password (permanent state)

**Behavior:**
- Set to `1` when employee account is created with auto-generated password
- Changed to `0` when user changes their password for the first time
- Once set to `0`, it stays `0` forever (user has personalized their password)

### 2. `is_new` (TINYINT)
**Purpose:** Track whether user needs OTP verification

**Values:**
- `1` (TRUE) - New account requiring OTP verification (set during employee creation)
- `0` (FALSE) - Account verified via OTP

**Behavior:**
- Set to `1` when employee account is created
- Changed to `0` when user successfully verifies OTP
- Once set to `0`, it stays `0` forever (user is verified)

## Database Migration

### Run the Migration

**Option 1: Using PHP Script (Recommended)**
```
http://localhost/HCM/database/run_auto_password_migration.php
```
This will:
- Check if columns exist
- Add missing columns
- Update existing users
- Show success/error messages

**Option 2: Using SQL File**
Run the SQL file in your database:
```sql
-- File: database/add_auto_password_and_is_new_columns.sql
USE hcm_system;

ALTER TABLE users 
ADD COLUMN auto_password_changed TINYINT(1) DEFAULT 0 
COMMENT 'True when using auto-generated password, false after user changes it';

ALTER TABLE users 
ADD COLUMN is_new TINYINT(1) DEFAULT 0 
COMMENT 'True for new accounts requiring OTP verification, false after verified';

UPDATE users SET is_new = 0, auto_password_changed = 0 
WHERE is_new IS NULL OR is_new = 1;
```

## Implementation Flow

### Employee Creation Flow

```
1. Admin creates employee
   ↓
2. System generates auto password
   ↓
3. User record created with:
   - auto_password_changed = 1 ✓
   - is_new = 1 ✓
   - role_id = 5 (Employee)
   ↓
4. Employee record created and linked
   ↓
5. Email sent with credentials
```

### First Login Flow

```
1. Employee logs in with username/password
   ↓
2. Session created with:
   - auto_password_changed = 1
   - is_new = 1
   ↓
3. System checks is_new = 1
   ↓
4. OTP verification modal shown
   ↓
5. User enters OTP code
   ↓
6. System updates: is_new = 0 ✓
   ↓
7. User can access system
```

### Password Change Flow

```
1. User clicks "Change Password"
   ↓
2. User enters new password
   ↓
3. System updates:
   - password_hash (new password)
   - auto_password_changed = 0 ✓ (permanent)
   - requires_password_change = 0
   ↓
4. User now has personalized password
```

## Code Changes

### 1. Employee Creation API (`api/employees.php`)

**Before:**
```php
INSERT INTO users (username, email, password_hash, role_id, is_active, last_login)
VALUES (?, ?, ?, 5, 1, NULL)
```

**After:**
```php
INSERT INTO users (username, email, password_hash, role_id, is_active, auto_password_changed, is_new, last_login)
VALUES (?, ?, ?, 5, 1, 1, 1, NULL)
```

### 2. Password Change API (`api/change_password.php`)

**Before:**
```php
UPDATE users 
SET password_hash = ?, 
    requires_password_change = 0,
    updated_at = CURRENT_TIMESTAMP
WHERE id = ?
```

**After:**
```php
UPDATE users 
SET password_hash = ?, 
    requires_password_change = 0,
    auto_password_changed = 0,  -- Permanent change
    updated_at = CURRENT_TIMESTAMP
WHERE id = ?
```

### 3. OTP Verification API (`api/otp.php`)

**Already Implemented:**
```php
// When OTP is verified
UPDATE users SET is_new = 0 WHERE id = :user_id
```

## Usage Examples

### Check if User Has Auto-Generated Password
```php
$stmt = $pdo->prepare("SELECT auto_password_changed FROM users WHERE id = ?");
$stmt->execute([$userId]);
$hasAutoPassword = $stmt->fetchColumn();

if ($hasAutoPassword == 1) {
    echo "User is still using auto-generated password";
} else {
    echo "User has personalized their password";
}
```

### Check if User Needs OTP Verification
```php
$stmt = $pdo->prepare("SELECT is_new FROM users WHERE id = ?");
$stmt->execute([$userId]);
$needsOTP = $stmt->fetchColumn();

if ($needsOTP == 1) {
    echo "User needs to verify OTP";
} else {
    echo "User is already verified";
}
```

### Force Password Change for Auto-Password Users
```php
// Get all users still using auto-generated passwords
$stmt = $pdo->query("
    SELECT u.*, e.first_name, e.last_name 
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    WHERE u.auto_password_changed = 1
    AND u.role_id = 5
");
$usersWithAutoPasswords = $stmt->fetchAll();
```

## Security Benefits

1. **Track Password Security**
   - Know which users are still using auto-generated passwords
   - Send reminders to change passwords
   - Enforce password change policies

2. **Account Verification**
   - Ensure all new accounts verify their email via OTP
   - Prevent unauthorized access
   - Confirm email ownership

3. **Audit Trail**
   - Track when users transition from auto to custom passwords
   - Monitor account verification status
   - Identify unverified accounts

## Reporting Queries

### Users Still Using Auto-Generated Passwords
```sql
SELECT 
    u.id,
    u.username,
    u.email,
    e.first_name,
    e.last_name,
    e.employee_id,
    u.created_at,
    DATEDIFF(NOW(), u.created_at) as days_since_creation
FROM users u
LEFT JOIN employees e ON e.user_id = u.id
WHERE u.auto_password_changed = 1
AND u.role_id = 5
ORDER BY u.created_at DESC;
```

### Unverified New Accounts
```sql
SELECT 
    u.id,
    u.username,
    u.email,
    e.first_name,
    e.last_name,
    u.created_at,
    DATEDIFF(NOW(), u.created_at) as days_unverified
FROM users u
LEFT JOIN employees e ON e.user_id = u.id
WHERE u.is_new = 1
ORDER BY u.created_at DESC;
```

### Account Status Summary
```sql
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN auto_password_changed = 1 THEN 1 ELSE 0 END) as using_auto_password,
    SUM(CASE WHEN auto_password_changed = 0 THEN 1 ELSE 0 END) as using_custom_password,
    SUM(CASE WHEN is_new = 1 THEN 1 ELSE 0 END) as unverified_accounts,
    SUM(CASE WHEN is_new = 0 THEN 1 ELSE 0 END) as verified_accounts
FROM users
WHERE role_id = 5;
```

## Testing Checklist

- [ ] Run database migration script
- [ ] Verify columns exist in users table
- [ ] Create new employee
- [ ] Verify auto_password_changed = 1 and is_new = 1
- [ ] Login as new employee
- [ ] Verify OTP verification prompt appears
- [ ] Complete OTP verification
- [ ] Verify is_new = 0 after OTP verification
- [ ] Change password
- [ ] Verify auto_password_changed = 0 after password change
- [ ] Check that auto_password_changed stays 0 (permanent)
- [ ] Verify login still works after changes

## Files Modified

1. **api/employees.php** - Added auto_password_changed=1 and is_new=1 during creation
2. **api/change_password.php** - Added auto_password_changed=0 when password changed
3. **api/otp.php** - Already had is_new=0 logic (no changes needed)
4. **database/add_auto_password_and_is_new_columns.sql** - Migration SQL script
5. **database/run_auto_password_migration.php** - Migration runner script

## Status

✅ **FULLY IMPLEMENTED**

- Database migration script created
- Employee creation updated to set both flags
- Password change updated to clear auto_password_changed
- OTP verification already updates is_new flag
- Documentation complete
- Ready for testing

---

**Date Implemented:** February 4, 2026
**Version:** 1.0
