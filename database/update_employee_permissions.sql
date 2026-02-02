-- Update Employee Role Permissions
-- Only allow: Payslip, Tax Deduction, and Performance History

USE hcm_system;

UPDATE roles 
SET permissions = '["payslip", "tax_deduction", "performance_history"]',
    description = 'Employee self-service: payslip, tax deductions, and performance history'
WHERE id = 5;

-- Verify the update
SELECT id, role_name, description, permissions 
FROM roles 
WHERE id = 5;
