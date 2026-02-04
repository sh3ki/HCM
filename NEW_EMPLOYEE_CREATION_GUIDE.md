# NEW EMPLOYEE CREATION & FIRST LOGIN FLOW

## Overview
This document explains the complete flow for creating new employees and their first login experience.

## Features Implemented

### 1. Admin Creates New Employee
**Location:** Employee Management Page (`views/employees.php`)
**API Endpoint:** `api/employees.php` (POST)

When admin creates a new employee:
- ✅ Saves to **BOTH** `users` table AND `employees` table
- ✅ Auto-generates a secure random password (16 characters)
- ✅ Creates unique username from email (e.g., john.doe@company.com → john.doe)
- ✅ Generates employee number (format: EMP-001, EMP-002, etc.)
- ✅ Sends email with login credentials
- ✅ Sets flags: `requires_password_change = 1`, `is_new = 1`

### 2. Email Notification
**Function:** `sendCredentialsEmail()` in `api/employees.php`

The new employee receives an email with:
- Username
- Temporary password
- Login URL
- Important notes about first login

**Note:** In development mode, credentials are logged to PHP error log.

### 3. First Login Flow

#### Step 1: Employee logs in with emailed credentials
- Uses username and temporary password from email
- System authenticates and creates session

#### Step 2: Password Change Modal (Can be skipped)
**Location:** `views/includes/header.php`
**API Endpoint:** `api/change_password.php`

If `requires_password_change = 1`:
- **Password Change Modal** appears first
- User can:
  - Change password (New Password + Confirm Password)
  - Skip for now (can change later)
- Password requirements: Minimum 6 characters
- After change or skip → proceeds to OTP verification

#### Step 3: OTP Verification Modal
**Location:** `views/includes/header.php`
**API Endpoint:** `api/otp.php`

If `is_new = 1`:
- **OTP Modal** appears (after password change modal)
- System sends OTP to user's email
- User enters OTP code
- After verification → full access granted

## Database Schema Changes

### Users Table
```sql
ALTER TABLE users 
ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0 AFTER is_active;

ALTER TABLE users 
ADD COLUMN is_new TINYINT(1) DEFAULT 1 AFTER requires_password_change;
```

**Migration File:** `database/add_password_change_flag.sql`

## API Endpoints

### 1. Create Employee
**Endpoint:** `POST /api/employees.php`

**Request Body:**
```json
{
  "first_name": "John",
  "middle_name": "M",
  "last_name": "Doe",
  "email": "john.doe@company.com",
  "phone": "+1234567890",
  "address": "123 Main St",
  "date_of_birth": "1990-01-15",
  "gender": "male",
  "marital_status": "single",
  "emergency_contact_name": "Jane Doe",
  "emergency_contact_phone": "+0987654321",
  "department": "Information Technology",
  "position": "Software Developer",
  "hire_date": "2026-02-04",
  "employment_status": "Active",
  "employment_type": "Full-time"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 5,
    "employee_id": 18,
    "employee_number": "EMP-005",
    "username": "john.doe",
    "email": "john.doe@company.com",
    "email_sent": true
  },
  "message": "Employee created successfully. Login credentials have been sent to john.doe@company.com"
}
```

### 2. Change Password
**Endpoint:** `POST /api/change_password.php?action=change`

**Request Body:**
```json
{
  "new_password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

### 3. Skip Password Change
**Endpoint:** `POST /api/change_password.php?action=skip`

**Response:**
```json
{
  "success": true,
  "message": "Password change skipped"
}
```

## Session Variables

After successful login, these session variables are set:
```php
$_SESSION['user_id']
$_SESSION['username']
$_SESSION['email']
$_SESSION['role_id']
$_SESSION['is_new']                      // For OTP verification
$_SESSION['requires_password_change']    // For password change modal
$_SESSION['authenticated'] = true
```

## Modal Flow Logic

### JavaScript Logic (`views/includes/scripts.php`)

1. **Password Change Modal** (`initPasswordChangeModal()`)
   - Only appears if `$_SESSION['requires_password_change'] == 1`
   - Blocks body scroll
   - After change or skip → Shows OTP modal

2. **OTP Modal** (`initOtpModal()`)
   - Only appears if `$_SESSION['is_new'] == 1`
   - Hidden initially if password change modal is present
   - Shown after password change modal is dismissed

## Testing the Feature

### Test Scenario 1: Create New Employee

1. Login as admin (username: `admin`, password: `admin`)
2. Go to **Employees** page
3. Click **+ Add Employee**
4. Fill in employee details:
   - First Name: Test
   - Last Name: Employee
   - Email: test.employee@company.com
   - Hire Date: 2026-02-04
   - Employment Status: Active
   - Other optional fields
5. Click **Create Employee**
6. Check PHP error log for credentials:
   ```
   === NEW EMPLOYEE CREDENTIALS ===
   Email: test.employee@company.com
   Username: test.employee
   Password: [auto-generated password]
   ================================
   ```

### Test Scenario 2: First Login with Auto-Generated Password

1. **Logout** from admin account
2. Go to login page
3. Enter credentials from log:
   - Username: `test.employee`
   - Password: [from log]
4. Click **Sign in**

**Expected Flow:**
1. ✅ Redirected to dashboard
2. ✅ **Password Change Modal** appears
   - Can enter new password + confirm
   - Or click "Skip for Now"
3. ✅ After change/skip → **OTP Modal** appears
   - OTP sent to email
   - Enter code to verify

### Test Scenario 3: Skip Password Change

1. In Password Change Modal, click **"Skip for Now"**
2. Modal closes
3. OTP Modal appears immediately
4. Complete OTP verification
5. Access granted to system

### Test Scenario 4: Change Password

1. In Password Change Modal:
   - New Password: `newpassword123`
   - Confirm Password: `newpassword123`
2. Click **"Change Password"**
3. Success message appears
4. Modal closes
5. OTP Modal appears
6. Complete OTP verification

## Files Modified/Created

### Created Files:
1. `database/add_password_change_flag.sql` - Database migration
2. `api/change_password.php` - Password change API endpoint
3. `NEW_EMPLOYEE_CREATION_GUIDE.md` - This documentation

### Modified Files:
1. `api/employees.php`
   - Implemented `handleCreateEmployee()` function
   - Added `sendCredentialsEmail()` function
   
2. `views/includes/header.php`
   - Added Password Change Modal
   - Modified OTP Modal display logic
   
3. `views/includes/scripts.php`
   - Added `initPasswordChangeModal()` function
   - Modified `initOtpModal()` function
   
4. `views/login.php`
   - Added `requires_password_change` to session storage
   
5. `includes/JWT.php`
   - Updated `sanitizeUserData()` to include `requires_password_change`

## Error Handling

### Common Issues:

1. **Email not sent in development**
   - Check PHP error log for credentials
   - Mail function requires mail server configuration
   
2. **Modal doesn't appear**
   - Check session variables: `$_SESSION['requires_password_change']`, `$_SESSION['is_new']`
   - Check browser console for JavaScript errors
   
3. **Password validation fails**
   - Minimum length: 6 characters
   - Passwords must match
   
4. **Employee creation fails**
   - Check for duplicate email
   - Verify all required fields are provided
   - Check database connection

## Security Features

1. ✅ Auto-generated secure passwords (16 random characters)
2. ✅ Password hashing with bcrypt
3. ✅ Unique usernames enforced
4. ✅ Email uniqueness validation
5. ✅ Transaction-based employee creation (rollback on failure)
6. ✅ Password change can be skipped (forced later via settings)
7. ✅ OTP verification for new accounts

## Future Enhancements

- [ ] Add email queue system for production
- [ ] Add password strength meter
- [ ] Add password complexity requirements (uppercase, numbers, symbols)
- [ ] Add "Send Password Reset" option in admin panel
- [ ] Add audit log for employee creation

## Support

For issues or questions:
1. Check PHP error log: `C:\laragon\www\HCM\logs\`
2. Check browser console for JavaScript errors
3. Verify database structure with `DESCRIBE users;`
4. Test API endpoints with Postman

---

**Implementation Date:** February 4, 2026  
**Developer:** GitHub Copilot  
**Status:** ✅ Complete and Tested
