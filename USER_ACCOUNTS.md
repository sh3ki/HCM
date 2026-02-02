# HCM System - User Accounts

## Available User Accounts

### 1. Admin Account (Full System Access)
- **Username:** `admin`
- **Password:** `password123`
- **Email:** robert.johnson@company.com
- **Role:** Super Admin
- **Permissions:** Full system access to all functions

### 2. Employee Accounts

#### John Doe (Employee)
- **Username:** `john_doe`
- **Password:** `password123`
- **Email:** john.doe@company.com
- **Role:** Employee
- **Permissions:** 
  - Download or Print Payslip
  - View Tax Deductions
  - View Performance History

#### Jane Smith (Employee)
- **Username:** `jane_smith`
- **Password:** `password123`
- **Email:** jane.smith@company.com
- **Role:** Employee
- **Permissions:** 
  - Download or Print Payslip
  - View Tax Deductions
  - View Performance History

#### James Anderson (Employee)
- **Username:** `james_anderson`
- **Password:** `password123`
- **Email:** james.anderson@company.com
- **Role:** Employee
- **Permissions:** 
  - Download or Print Payslip
  - View Tax Deductions
  - View Performance History

### 3. HR Staff Accounts

#### HR Manager
- **Username:** `hr_manager`
- **Password:** `password123`
- **Email:** hr.manager@company.com
- **Role:** HR Manager
- **Permissions:** employees, payroll, compensation, benefits, reports

#### HR Staff
- **Username:** `hr_staff`
- **Password:** `password123`
- **Email:** hr.staff@company.com
- **Role:** HR Staff
- **Permissions:** employees, attendance, leaves

### 4. Payroll Officer
- **Username:** `payroll_officer`
- **Password:** `password123`
- **Email:** payroll@company.com
- **Role:** Payroll Officer
- **Permissions:** payroll, compensation, reports

---

## System Roles Overview

| Role ID | Role Name          | Description                 | Key Permissions                                            |
|---------|--------------------|-----------------------------|-----------------------------------------------------------|
| 1       | Super Admin        | Full system access          | All functions                                             |
| 2       | HR Manager         | HR management access        | employees, payroll, compensation, benefits, reports       |
| 3       | HR Staff           | Basic HR operations         | employees, attendance, leaves                             |
| 4       | Payroll Officer    | Payroll processing access   | payroll, compensation, reports                            |
| 5       | Employee           | Self-service access         | payslip, tax_deduction, performance_history               |
| 6       | Department Manager | Department staff management | team_management, attendance, leaves_approval              |

---

## Login Instructions

1. Navigate to: `http://localhost/HCM/views/login.php`
2. Enter username and password
3. Click "Login"

## Password Reset

If you need to reset passwords for testing, you can use this SQL:

```sql
-- Reset password to "password123" for any user
UPDATE hcm_system.users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';
```

## Adding New Users

To add new users, you can:
1. Use the admin panel (if available)
2. Create employees through the API endpoints
3. Run SQL scripts in the `database/` folder

---

**Note:** For security reasons, make sure to change default passwords in production environment.
