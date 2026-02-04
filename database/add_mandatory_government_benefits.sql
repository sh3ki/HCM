-- ==========================================
-- Add Mandatory Philippine Government Benefits
-- SSS, Pag-IBIG, and PhilHealth
-- ==========================================

-- First, check if insurance_providers table exists and add government provider
INSERT INTO insurance_providers (provider_name, provider_code, provider_type, contact_person, contact_email, contact_phone, address, is_active, created_at)
VALUES 
('Philippine Government', 'GOV-PH-001', 'Other', 'Government Agency', 'info@government.ph', '02-1234-5678', 'Philippines', 1, NOW())
ON DUPLICATE KEY UPDATE provider_name = provider_name;

-- Get the provider ID for government benefits
SET @gov_provider_id = (SELECT id FROM insurance_providers WHERE provider_name = 'Philippine Government' LIMIT 1);

-- ==========================================
-- 1. SSS (Social Security System)
-- ==========================================
-- Monthly contribution rates based on ₱30,000 salary bracket
-- Employee: ₱1,350.00 (4.5% of ₱30,000)
-- Employer: ₱2,700.00 (9.0% of ₱30,000)
-- Total: ₱4,050.00 (13.5% of ₱30,000)

INSERT INTO insurance_plans (
    plan_code,
    plan_name,
    plan_type,
    description,
    provider_id,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    coverage_amount,
    is_active,
    created_at
)
VALUES (
    'GOV-SSS-2026',
    'SSS (Social Security System)',
    'Dependent',
    'Mandatory social insurance program providing benefits for sickness, maternity, disability, retirement, death, and funeral. Contribution rates based on monthly salary credit of ₱30,000.',
    @gov_provider_id,
    4050.00,
    2700.00,
    1350.00,
    0.00,
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution);

-- ==========================================
-- 2. Pag-IBIG (Home Development Mutual Fund)
-- ==========================================
-- Monthly contribution rates:
-- Employee: ₱100.00 (1% of ₱10,000 minimum, capped at ₱100)
-- Employer: ₱200.00 (2% of ₱10,000 minimum, capped at ₱200)
-- Total: ₱300.00 (For salaries below ₱1,500, both contribute equally)

INSERT INTO insurance_plans (
    plan_code,
    plan_name,
    plan_type,
    description,
    provider_id,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    coverage_amount,
    is_active,
    created_at
)
VALUES (
    'GOV-PAGIBIG-2026',
    'Pag-IBIG Fund',
    'Dependent',
    'Mandatory savings program providing housing loans, short-term loans, and other member benefits. Employee contributes 1-2% and employer contributes 2% of monthly salary.',
    @gov_provider_id,
    300.00,
    200.00,
    100.00,
    0.00,
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution);

-- ==========================================
-- 3. PhilHealth (Philippine Health Insurance Corporation)
-- ==========================================
-- Monthly contribution rates based on ₱40,000 salary bracket
-- Employee: ₱900.00 (2.25% of ₱40,000)
-- Employer: ₱900.00 (2.25% of ₱40,000)
-- Total: ₱1,800.00 (4.5% of ₱40,000)

INSERT INTO insurance_plans (
    plan_code,
    plan_name,
    plan_type,
    description,
    provider_id,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    coverage_amount,
    is_active,
    created_at,
    updated_at
)
)
VALUES (
    'GOV-PHILHEALTH-2026',
    'PhilHealth',
    'Dependent',
    'Mandatory health insurance providing coverage for inpatient and outpatient services, medicines, and various medical procedures. Premium rate is 4.5% of monthly basic salary shared equally between employer and employee.',
    @gov_provider_id,
    1800.00,
    900.00,
    900.00,
150000.00,
    1
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution),
    coverage_amount = VALUES(coverage_amount);

-- ==========================================
-- Auto-enroll all active employees in mandatory government benefits
-- ==========================================

-- Get plan IDs
SET @sss_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-SSS-2026' LIMIT 1);
SET @pagibig_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-PAGIBIG-2026' LIMIT 1);
SET @philhealth_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-PHILHEALTH-2026' LIMIT 1);

-- Enroll all active employees in SSS
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    beneficiary_name,
    beneficiary_relationship,
    created_at,
    updated_at
)
)
SELECT 
    e.id,
    @sss_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    'To be updated',
    'Family'oyees e
WHERE e.employment_status = 'Active'
AND NOT EXISTS (
    SELECT 1 FROM employee_insurance ei 
    WHERE ei.employee_id = e.id 
    AND ei.insurance_plan_id = @sss_plan_id
);

-- Enroll all active employees in Pag-IBIG
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    beneficiary_name,
    beneficiary_relationship,
    created_at,
    updated_at
)
)
SELECT 
    e.id,
    @pagibig_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    'To be updated',
    'Family'oyees e
WHERE e.employment_status = 'Active'
AND NOT EXISTS (
    SELECT 1 FROM employee_insurance ei 
    WHERE ei.employee_id = e.id 
    AND ei.insurance_plan_id = @pagibig_plan_id
);

-- Enroll all active employees in PhilHealth
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    beneficiary_name,
    beneficiary_relationship,
    created_at,
    updated_at
)
)
SELECT 
    e.id,
    @philhealth_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    'To be updated',
    'Family'oyees e
WHERE e.employment_status = 'Active'
AND NOT EXISTS (
    SELECT 1 FROM employee_insurance ei 
    WHERE ei.employee_id = e.id 
    AND ei.insurance_plan_id = @philhealth_plan_id
);

-- ==========================================
-- Verification Queries
-- ==========================================

-- Check if government benefits were added
SELECT 
    ip.plan_code,
    ip.plan_name,
    ip.monthly_premium,
    ip.employer_contribution,
    ip.employee_contribution,
    ip.is_active,
    COUNT(ei.id) as enrolled_employees
FROM insurance_plans ip
LEFT JOIN employee_insurance ei ON ip.id = ei.insurance_plan_id AND ei.status = 'Active'
WHERE ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026')
GROUP BY ip.id, ip.plan_code, ip.plan_name, ip.monthly_premium, ip.employer_contribution, ip.employee_contribution, ip.is_active
ORDER BY ip.plan_code;

-- Summary of total government benefits cost
SELECT 
    COUNT(DISTINCT e.id) as total_employees,
    SUM(ip.monthly_premium) as total_monthly_cost,
    SUM(ip.employer_contribution) as total_employer_contribution,
    SUM(ip.employee_contribution) as total_employee_contribution
FROM employees e
JOIN employee_insurance ei ON e.id = ei.employee_id AND ei.status = 'Active'
JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
WHERE e.employment_status = 'Active'
AND ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026');
