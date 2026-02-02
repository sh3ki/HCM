-- Add Admin and Employee Users for HCM System
-- Created: 2026-02-01
-- Admin user: admin / admin123
-- Employee user: employee / employee123

USE hcm_system;

-- Insert admin user
-- Password: admin123 (hashed with bcrypt)
-- role_id 1 = Super Admin (full system access)
INSERT INTO users (username, email, password_hash, role_id, is_active) VALUES
('admin', 'admin@hcm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE);

-- Get the admin user_id
SET @admin_user_id = LAST_INSERT_ID();

-- Insert admin employee record
INSERT INTO employees (
    user_id, 
    employee_number, 
    first_name, 
    last_name, 
    email, 
    phone, 
    department_id, 
    position_id, 
    hire_date, 
    employment_status, 
    employment_type
) VALUES (
    @admin_user_id,
    'EMP000',
    'System',
    'Administrator',
    'admin@hcm.local',
    '09171234000',
    1, -- HR Department
    1, -- HR Manager position
    '2026-01-01',
    'active',
    'full_time'
);

-- Insert employee user
-- Password: employee123 (hashed with bcrypt)
-- role_id 5 = Employee (self-service access)
INSERT INTO users (username, email, password_hash, role_id, is_active) VALUES
('employee', 'employee@hcm.local', '$2y$10$5Zl0YrZXWW9f0YV5hBJLOuDqP6S3X.KKqXR8Y4fG5b3FqQ8vN2TZy', 5, TRUE);

-- Get the employee user_id
SET @employee_user_id = LAST_INSERT_ID();

-- Insert employee record
INSERT INTO employees (
    user_id, 
    employee_number, 
    first_name, 
    last_name, 
    email, 
    phone, 
    department_id, 
    position_id, 
    hire_date, 
    employment_status, 
    employment_type
) VALUES (
    @employee_user_id,
    'EMP999',
    'John',
    'Employee',
    'employee@hcm.local',
    '09171234999',
    2, -- IT Department
    6, -- Junior Developer position
    '2026-02-01',
    'active',
    'full_time'
);

-- Display created users
SELECT 
    u.id,
    u.username,
    u.email,
    r.role_name,
    e.employee_number,
    CONCAT(e.first_name, ' ', e.last_name) as full_name
FROM users u
LEFT JOIN employees e ON u.id = e.user_id
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.username IN ('admin', 'employee')
ORDER BY u.role_id ASC;

-- Login credentials for reference:
-- Admin: username = admin, password = admin123
-- Employee: username = employee, password = employee123
