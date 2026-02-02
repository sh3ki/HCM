-- Sample Data for HCM System
-- This file contains sample data for testing and development purposes
USE hcm_system;

-- Insert sample departments
INSERT INTO departments (name, description, budget) VALUES
('Human Resources', 'Manages employee relations, recruitment, and HR policies', 500000.00),
('Information Technology', 'Handles all technology infrastructure and software development', 1200000.00),
('Finance', 'Manages financial operations, accounting, and budgeting', 800000.00),
('Sales', 'Responsible for sales operations and customer relationships', 1000000.00),
('Marketing', 'Handles marketing campaigns and brand management', 600000.00),
('Operations', 'Manages day-to-day business operations', 900000.00);

-- Insert sample positions
INSERT INTO positions (title, department_id, description, min_salary, max_salary) VALUES
-- HR Positions
('HR Manager', 1, 'Oversees HR operations and policies', 60000.00, 80000.00),
('HR Specialist', 1, 'Handles recruitment and employee relations', 35000.00, 50000.00),
('HR Assistant', 1, 'Supports HR operations and documentation', 25000.00, 35000.00),

-- IT Positions
('IT Manager', 2, 'Manages IT department and technology strategy', 80000.00, 120000.00),
('Senior Developer', 2, 'Leads software development projects', 60000.00, 90000.00),
('Junior Developer', 2, 'Develops software applications', 30000.00, 50000.00),
('System Administrator', 2, 'Maintains IT infrastructure', 45000.00, 65000.00),

-- Finance Positions
('Finance Manager', 3, 'Oversees financial operations', 70000.00, 100000.00),
('Accountant', 3, 'Handles accounting and financial reporting', 40000.00, 60000.00),
('Finance Assistant', 3, 'Supports financial operations', 28000.00, 40000.00),

-- Sales Positions
('Sales Manager', 4, 'Manages sales team and strategy', 65000.00, 95000.00),
('Sales Representative', 4, 'Handles client relationships and sales', 30000.00, 50000.00),
('Sales Coordinator', 4, 'Supports sales operations', 25000.00, 35000.00),

-- Marketing Positions
('Marketing Manager', 5, 'Oversees marketing campaigns and strategy', 60000.00, 85000.00),
('Marketing Specialist', 5, 'Executes marketing campaigns', 35000.00, 55000.00),

-- Operations Positions
('Operations Manager', 6, 'Manages daily business operations', 65000.00, 90000.00),
('Operations Coordinator', 6, 'Coordinates operational activities', 30000.00, 45000.00);

-- Insert sample users and employees
-- HR Department
INSERT INTO users (username, email, password_hash, role) VALUES
('maria.santos', 'maria.santos@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'hr'),
('john.cruz', 'john.cruz@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'hr'),
('anna.reyes', 'anna.reyes@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee');

INSERT INTO employees (user_id, employee_number, first_name, last_name, email, phone, department_id, position_id, hire_date, employment_status, employment_type) VALUES
(2, 'EMP001', 'Maria', 'Santos', 'maria.santos@hcm.local', '09171234567', 1, 1, '2023-01-15', 'active', 'full_time'),
(3, 'EMP002', 'John', 'Cruz', 'john.cruz@hcm.local', '09187654321', 1, 2, '2023-03-01', 'active', 'full_time'),
(4, 'EMP003', 'Anna', 'Reyes', 'anna.reyes@hcm.local', '09195555555', 1, 3, '2023-06-01', 'active', 'full_time');

-- Update department manager
UPDATE departments SET manager_id = 1 WHERE id = 1;

-- IT Department
INSERT INTO users (username, email, password_hash, role) VALUES
('robert.garcia', 'robert.garcia@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'manager'),
('sarah.lopez', 'sarah.lopez@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee'),
('michael.torres', 'michael.torres@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee'),
('lisa.hernandez', 'lisa.hernandez@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee');

INSERT INTO employees (user_id, employee_number, first_name, last_name, email, phone, department_id, position_id, manager_id, hire_date, employment_status, employment_type) VALUES
(5, 'EMP004', 'Robert', 'Garcia', 'robert.garcia@hcm.local', '09171111111', 2, 4, NULL, '2022-08-01', 'active', 'full_time'),
(6, 'EMP005', 'Sarah', 'Lopez', 'sarah.lopez@hcm.local', '09172222222', 2, 5, 4, '2023-02-15', 'active', 'full_time'),
(7, 'EMP006', 'Michael', 'Torres', 'michael.torres@hcm.local', '09173333333', 2, 6, 4, '2023-09-01', 'active', 'full_time'),
(8, 'EMP007', 'Lisa', 'Hernandez', 'lisa.hernandez@hcm.local', '09174444444', 2, 7, 4, '2023-04-01', 'active', 'full_time');

-- Update department manager
UPDATE departments SET manager_id = 4 WHERE id = 2;

-- Finance Department
INSERT INTO users (username, email, password_hash, role) VALUES
('david.martinez', 'david.martinez@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'manager'),
('jennifer.gonzalez', 'jennifer.gonzalez@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee'),
('carlos.rodriguez', 'carlos.rodriguez@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee');

INSERT INTO employees (user_id, employee_number, first_name, last_name, email, phone, department_id, position_id, manager_id, hire_date, employment_status, employment_type) VALUES
(9, 'EMP008', 'David', 'Martinez', 'david.martinez@hcm.local', '09175555555', 3, 8, NULL, '2022-05-01', 'active', 'full_time'),
(10, 'EMP009', 'Jennifer', 'Gonzalez', 'jennifer.gonzalez@hcm.local', '09176666666', 3, 9, 8, '2023-01-20', 'active', 'full_time'),
(11, 'EMP010', 'Carlos', 'Rodriguez', 'carlos.rodriguez@hcm.local', '09177777777', 3, 10, 8, '2023-07-15', 'active', 'full_time');

-- Update department manager
UPDATE departments SET manager_id = 8 WHERE id = 3;

-- Sales Department
INSERT INTO users (username, email, password_hash, role) VALUES
('patricia.flores', 'patricia.flores@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'manager'),
('james.rivera', 'james.rivera@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee'),
('michelle.castro', 'michelle.castro@hcm.local', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'employee');

INSERT INTO employees (user_id, employee_number, first_name, last_name, email, phone, department_id, position_id, manager_id, hire_date, employment_status, employment_type) VALUES
(12, 'EMP011', 'Patricia', 'Flores', 'patricia.flores@hcm.local', '09178888888', 4, 11, NULL, '2022-03-01', 'active', 'full_time'),
(13, 'EMP012', 'James', 'Rivera', 'james.rivera@hcm.local', '09179999999', 4, 12, 11, '2023-05-01', 'active', 'full_time'),
(14, 'EMP013', 'Michelle', 'Castro', 'michelle.castro@hcm.local', '09170000000', 4, 13, 11, '2023-08-01', 'active', 'full_time');

-- Update department manager
UPDATE departments SET manager_id = 11 WHERE id = 4;

-- Insert compensation data
INSERT INTO compensation (employee_id, salary_type, base_salary, effective_date, is_active) VALUES
(1, 'monthly', 75000.00, '2023-01-15', TRUE),
(2, 'monthly', 45000.00, '2023-03-01', TRUE),
(3, 'monthly', 30000.00, '2023-06-01', TRUE),
(4, 'monthly', 100000.00, '2022-08-01', TRUE),
(5, 'monthly', 75000.00, '2023-02-15', TRUE),
(6, 'monthly', 40000.00, '2023-09-01', TRUE),
(7, 'monthly', 55000.00, '2023-04-01', TRUE),
(8, 'monthly', 85000.00, '2022-05-01', TRUE),
(9, 'monthly', 50000.00, '2023-01-20', TRUE),
(10, 'monthly', 35000.00, '2023-07-15', TRUE),
(11, 'monthly', 80000.00, '2022-03-01', TRUE),
(12, 'monthly', 40000.00, '2023-05-01', TRUE),
(13, 'monthly', 28000.00, '2023-08-01', TRUE);

-- Insert employee allowances
INSERT INTO employee_allowances (employee_id, allowance_id, amount, effective_date, is_active) VALUES
-- Transportation allowances for all employees
(1, 1, 500.00, '2023-01-15', TRUE),
(2, 1, 500.00, '2023-03-01', TRUE),
(3, 1, 500.00, '2023-06-01', TRUE),
(4, 1, 500.00, '2022-08-01', TRUE),
(5, 1, 500.00, '2023-02-15', TRUE),
(6, 1, 500.00, '2023-09-01', TRUE),
(7, 1, 500.00, '2023-04-01', TRUE),

-- Meal allowances for all employees
(1, 2, 300.00, '2023-01-15', TRUE),
(2, 2, 300.00, '2023-03-01', TRUE),
(3, 2, 300.00, '2023-06-01', TRUE),
(4, 2, 300.00, '2022-08-01', TRUE),
(5, 2, 300.00, '2023-02-15', TRUE),
(6, 2, 300.00, '2023-09-01', TRUE),
(7, 2, 300.00, '2023-04-01', TRUE),

-- Phone allowances for managers
(1, 3, 1000.00, '2023-01-15', TRUE),
(4, 3, 1000.00, '2022-08-01', TRUE),
(8, 3, 1000.00, '2022-05-01', TRUE),
(11, 3, 1000.00, '2022-03-01', TRUE);

-- Insert employee deductions (SSS, PhilHealth, Pag-IBIG)
INSERT INTO employee_deductions (employee_id, deduction_id, amount, effective_date, is_active) VALUES
-- SSS deductions (assuming 4.5% employee share)
(1, 1, 3375.00, '2023-01-15', TRUE),
(2, 1, 2025.00, '2023-03-01', TRUE),
(3, 1, 1350.00, '2023-06-01', TRUE),
(4, 1, 4500.00, '2022-08-01', TRUE),
(5, 1, 3375.00, '2023-02-15', TRUE),

-- PhilHealth deductions (assuming 1.25% employee share)
(1, 2, 937.50, '2023-01-15', TRUE),
(2, 2, 562.50, '2023-03-01', TRUE),
(3, 2, 375.00, '2023-06-01', TRUE),
(4, 2, 1250.00, '2022-08-01', TRUE),
(5, 2, 937.50, '2023-02-15', TRUE),

-- Pag-IBIG deductions (fixed amount)
(1, 3, 200.00, '2023-01-15', TRUE),
(2, 3, 200.00, '2023-03-01', TRUE),
(3, 3, 200.00, '2023-06-01', TRUE),
(4, 3, 200.00, '2022-08-01', TRUE),
(5, 3, 200.00, '2023-02-15', TRUE);

-- Insert leave balances for current year
INSERT INTO leave_balances (employee_id, leave_type_id, year, allocated_days, used_days, remaining_days) VALUES
-- Vacation Leave (15 days allocated)
(1, 1, 2023, 15, 3, 12),
(2, 1, 2023, 15, 1, 14),
(3, 1, 2023, 15, 0, 15),
(4, 1, 2023, 15, 5, 10),
(5, 1, 2023, 15, 2, 13),

-- Sick Leave (10 days allocated)
(1, 2, 2023, 10, 1, 9),
(2, 2, 2023, 10, 0, 10),
(3, 2, 2023, 10, 0, 10),
(4, 2, 2023, 10, 2, 8),
(5, 2, 2023, 10, 1, 9),

-- Emergency Leave (5 days allocated)
(1, 3, 2023, 5, 0, 5),
(2, 3, 2023, 5, 0, 5),
(3, 3, 2023, 5, 1, 4),
(4, 3, 2023, 5, 0, 5),
(5, 3, 2023, 5, 0, 5);

-- Insert sample leave requests
INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, days_requested, reason, status, approved_by) VALUES
(2, 1, '2023-12-20', '2023-12-22', 3, 'Family vacation during holidays', 'approved', 1),
(5, 2, '2023-11-15', '2023-11-15', 1, 'Medical check-up', 'approved', 4),
(3, 3, '2023-10-05', '2023-10-05', 1, 'Family emergency', 'approved', 1),
(6, 1, '2023-12-15', '2023-12-16', 2, 'Personal matters', 'pending', NULL);

-- Insert sample attendance records for current month
INSERT INTO attendance (employee_id, date, time_in, time_out, hours_worked, status) VALUES
-- Sample attendance for Employee 1 (Maria Santos)
(1, '2023-09-01', '08:00:00', '17:00:00', 8.00, 'present'),
(1, '2023-09-02', '08:15:00', '17:00:00', 7.75, 'late'),
(1, '2023-09-03', '08:00:00', '17:00:00', 8.00, 'present'),
(1, '2023-09-04', '08:00:00', '17:00:00', 8.00, 'present'),
(1, '2023-09-05', '08:00:00', '17:00:00', 8.00, 'present'),

-- Sample attendance for Employee 2 (John Cruz)
(2, '2023-09-01', '08:00:00', '17:00:00', 8.00, 'present'),
(2, '2023-09-02', '08:00:00', '17:00:00', 8.00, 'present'),
(2, '2023-09-03', NULL, NULL, 0.00, 'absent'),
(2, '2023-09-04', '08:00:00', '17:00:00', 8.00, 'present'),
(2, '2023-09-05', '08:00:00', '17:00:00', 8.00, 'present'),

-- Sample attendance for Employee 4 (Robert Garcia)
(4, '2023-09-01', '08:00:00', '18:00:00', 9.00, 'present'),
(4, '2023-09-02', '08:00:00', '17:30:00', 8.50, 'present'),
(4, '2023-09-03', '08:00:00', '17:00:00', 8.00, 'present'),
(4, '2023-09-04', '08:00:00', '19:00:00', 10.00, 'present'),
(4, '2023-09-05', '08:00:00', '17:00:00', 8.00, 'present');

-- Insert employee benefits
INSERT INTO employee_benefits (employee_id, benefit_id, enrollment_date, employee_contribution, employer_contribution, status) VALUES
-- Health Insurance for all employees
(1, 1, '2023-01-15', 500.00, 1500.00, 'active'),
(2, 1, '2023-03-01', 500.00, 1500.00, 'active'),
(3, 1, '2023-06-01', 500.00, 1500.00, 'active'),
(4, 1, '2022-08-01', 500.00, 1500.00, 'active'),
(5, 1, '2023-02-15', 500.00, 1500.00, 'active'),

-- Life Insurance for full-time employees
(1, 2, '2023-01-15', 200.00, 800.00, 'active'),
(2, 2, '2023-03-01', 200.00, 800.00, 'active'),
(4, 2, '2022-08-01', 200.00, 800.00, 'active'),
(5, 2, '2023-02-15', 200.00, 800.00, 'active');

-- Create a sample payroll period
INSERT INTO payroll_periods (period_name, start_date, end_date, pay_date, status, created_by) VALUES
('September 2023', '2023-09-01', '2023-09-30', '2023-10-05', 'approved', 1);

-- Insert sample payroll data
INSERT INTO payroll (employee_id, payroll_period_id, gross_pay, total_allowances, total_deductions, net_pay, hours_worked, status) VALUES
(1, 1, 75000.00, 800.00, 4512.50, 71287.50, 160.00, 'approved'),
(2, 1, 45000.00, 800.00, 2787.50, 43012.50, 160.00, 'approved'),
(3, 1, 30000.00, 800.00, 1925.00, 28875.00, 160.00, 'approved'),
(4, 1, 100000.00, 1800.00, 5950.00, 95850.00, 170.00, 'approved'),
(5, 1, 75000.00, 800.00, 4512.50, 71287.50, 164.00, 'approved');

COMMIT;