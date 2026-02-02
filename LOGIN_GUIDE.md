# Quick Login Guide - HCM System

## 🔑 Main Users for Testing

### 👤 Admin User (Full Access)
```
Username: admin
Password: password123
```
**Access:** All system functions, complete administrative control

### 👤 Employee User (Limited Access)  
```
Username: john_doe
Password: password123
```
**Access:** 
- Download or Print Payslip
- View Tax Deductions
- View Performance History

---

## 📋 All Available Test Accounts

All accounts use the password: **password123**

| Username        | Role            | Access Level                                  |
|-----------------|-----------------|-----------------------------------------------|
| admin           | Super Admin     | ✅ Full system access                         |
| hr_manager      | HR Manager      | ✅ Employees, payroll, compensation, reports  |
| hr_staff        | HR Staff        | ✅ Employees, attendance, leaves              |
| payroll_officer | Payroll Officer | ✅ Payroll, compensation, reports             |
| john_doe        | Employee        | 🔒 Self-service only                          |
| jane_smith      | Employee        | 🔒 Self-service only                          |
| james_anderson  | Employee        | 🔒 Self-service only                          |

---

## 🌐 Login URL
```
http://localhost/HCM/views/login.php
```

---

## 📝 Notes
- All passwords are currently set to `password123` for testing
- The admin account has unrestricted access to all modules
- Employee accounts can only access their own profile and related functions
- Change passwords in production environment for security

---

For more detailed information, see [USER_ACCOUNTS.md](USER_ACCOUNTS.md)
