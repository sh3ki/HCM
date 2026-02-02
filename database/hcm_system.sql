-- HCM System Database Schema
-- Human Capital Management System
-- Created: 2025-09-15

DROP DATABASE IF EXISTS hcm_system;
CREATE DATABASE hcm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hcm_system;

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'manager', 'employee') DEFAULT 'employee',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    budget DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Positions/Job Titles table
CREATE TABLE positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    description TEXT,
    min_salary DECIMAL(12,2),
    max_salary DECIMAL(12,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Employees table
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    employee_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    department_id INT,
    position_id INT,
    manager_id INT,
    hire_date DATE NOT NULL,
    employment_status ENUM('active', 'inactive', 'terminated', 'on_leave') DEFAULT 'active',
    employment_type ENUM('full_time', 'part_time', 'contract', 'intern') DEFAULT 'full_time',
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- Add foreign key for department manager
ALTER TABLE departments ADD FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL;

-- Compensation/Salary table
CREATE TABLE compensation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    salary_type ENUM('monthly', 'hourly', 'annual') DEFAULT 'monthly',
    base_salary DECIMAL(12,2) NOT NULL,
    hourly_rate DECIMAL(8,2),
    overtime_rate DECIMAL(8,2),
    currency VARCHAR(3) DEFAULT 'PHP',
    effective_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Allowances table
CREATE TABLE allowances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2),
    is_taxable BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee allowances junction table
CREATE TABLE employee_allowances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    allowance_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (allowance_id) REFERENCES allowances(id) ON DELETE CASCADE
);

-- Deductions table
CREATE TABLE deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    deduction_type ENUM('fixed', 'percentage', 'variable') DEFAULT 'fixed',
    amount DECIMAL(10,2),
    percentage DECIMAL(5,2),
    is_mandatory BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee deductions junction table
CREATE TABLE employee_deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    deduction_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (deduction_id) REFERENCES deductions(id) ON DELETE CASCADE
);

-- Payroll periods table
CREATE TABLE payroll_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pay_date DATE NOT NULL,
    status ENUM('draft', 'processing', 'approved', 'paid') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payroll table
CREATE TABLE payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    payroll_period_id INT NOT NULL,
    gross_pay DECIMAL(12,2) NOT NULL,
    total_allowances DECIMAL(12,2) DEFAULT 0,
    total_deductions DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) NOT NULL,
    hours_worked DECIMAL(8,2) DEFAULT 0,
    overtime_hours DECIMAL(8,2) DEFAULT 0,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    break_start TIME,
    break_end TIME,
    hours_worked DECIMAL(5,2) DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('present', 'absent', 'late', 'half_day', 'on_leave') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, date)
);

-- Leave types table
CREATE TABLE leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    max_days_per_year INT DEFAULT 0,
    is_paid BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leave requests table
CREATE TABLE leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Employee leave balances table
CREATE TABLE leave_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    year YEAR NOT NULL,
    allocated_days INT DEFAULT 0,
    used_days INT DEFAULT 0,
    remaining_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_leave_year (employee_id, leave_type_id, year)
);

-- Benefits table
CREATE TABLE benefits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    benefit_type ENUM('health', 'insurance', 'retirement', 'other') DEFAULT 'other',
    provider VARCHAR(100),
    coverage_amount DECIMAL(12,2),
    employee_contribution DECIMAL(8,2) DEFAULT 0,
    employer_contribution DECIMAL(8,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee benefits junction table
CREATE TABLE employee_benefits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    benefit_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    end_date DATE,
    employee_contribution DECIMAL(8,2) DEFAULT 0,
    employer_contribution DECIMAL(8,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (benefit_id) REFERENCES benefits(id) ON DELETE CASCADE
);

-- Performance reviews table
CREATE TABLE performance_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    overall_rating DECIMAL(3,2),
    goals_achievement TEXT,
    strengths TEXT,
    areas_for_improvement TEXT,
    development_plan TEXT,
    status ENUM('draft', 'submitted', 'approved') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- System logs table for audit trail
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Session management table
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_employees_department ON employees(department_id);
CREATE INDEX idx_employees_position ON employees(position_id);
CREATE INDEX idx_employees_status ON employees(employment_status);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, date);
CREATE INDEX idx_payroll_period ON payroll(payroll_period_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_leave_requests_dates ON leave_requests(start_date, end_date);
CREATE INDEX idx_system_logs_created ON system_logs(created_at);
CREATE INDEX idx_user_sessions_activity ON user_sessions(last_activity);

-- Create views for common queries
CREATE VIEW employee_details AS
SELECT
    e.id,
    e.employee_number,
    CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) AS full_name,
    e.email,
    e.phone,
    d.name AS department_name,
    p.title AS position_title,
    CONCAT(m.first_name, ' ', m.last_name) AS manager_name,
    e.hire_date,
    e.employment_status,
    e.employment_type,
    c.base_salary,
    c.salary_type
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN positions p ON e.position_id = p.id
LEFT JOIN employees m ON e.manager_id = m.id
LEFT JOIN compensation c ON e.id = c.employee_id AND c.is_active = TRUE;

CREATE VIEW current_payroll_summary AS
SELECT
    pp.period_name,
    pp.start_date,
    pp.end_date,
    pp.pay_date,
    COUNT(p.id) AS total_employees,
    SUM(p.gross_pay) AS total_gross_pay,
    SUM(p.total_deductions) AS total_deductions,
    SUM(p.net_pay) AS total_net_pay,
    pp.status
FROM payroll_periods pp
LEFT JOIN payroll p ON pp.id = p.payroll_period_id
GROUP BY pp.id, pp.period_name, pp.start_date, pp.end_date, pp.pay_date, pp.status;

-- Insert default data
INSERT INTO leave_types (name, description, max_days_per_year, is_paid) VALUES
('Vacation Leave', 'Annual vacation days', 15, TRUE),
('Sick Leave', 'Medical leave for illness', 10, TRUE),
('Emergency Leave', 'Emergency situations', 5, TRUE),
('Maternity Leave', 'Maternity leave for mothers', 90, TRUE),
('Paternity Leave', 'Paternity leave for fathers', 7, TRUE),
('Bereavement Leave', 'Leave for family deaths', 3, TRUE);

INSERT INTO allowances (name, description, amount, is_taxable) VALUES
('Transportation Allowance', 'Daily transportation costs', 500.00, FALSE),
('Meal Allowance', 'Daily meal allowance', 300.00, FALSE),
('Phone Allowance', 'Mobile phone allowance', 1000.00, TRUE),
('Overtime Allowance', 'Overtime work compensation', 0.00, TRUE);

INSERT INTO deductions (name, description, deduction_type, is_mandatory) VALUES
('SSS', 'Social Security System', 'percentage', TRUE),
('PhilHealth', 'Philippine Health Insurance', 'percentage', TRUE),
('Pag-IBIG', 'Home Development Mutual Fund', 'fixed', TRUE),
('Withholding Tax', 'Income tax withholding', 'percentage', TRUE),
('Late Deduction', 'Deduction for tardiness', 'variable', FALSE);

INSERT INTO benefits (name, description, benefit_type, provider) VALUES
('Health Insurance', 'Comprehensive health coverage', 'health', 'PhilHealth'),
('Life Insurance', 'Life insurance coverage', 'insurance', 'Insurance Company'),
('Dental Coverage', 'Dental care coverage', 'health', 'Dental Provider'),
('Vision Coverage', 'Eye care coverage', 'health', 'Vision Provider');

-- Create a default admin user (password: password123 - should be changed in production)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'admin');

-- Update all existing users to have password: password123
UPDATE users SET password_hash = '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG';

COMMIT;