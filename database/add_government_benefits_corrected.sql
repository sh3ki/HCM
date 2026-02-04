-- ==========================================
-- Add Mandatory Philippine Government Benefits
-- SSS, Pag-IBIG, PhilHealth for all Active Employees
-- ==========================================

-- Step 1: Add Philippine Government as Insurance Provider
INSERT INTO insurance_providers (
    provider_code,
    provider_name,
    provider_type,
    contact_person,
    contact_email,
    contact_phone,
    address,
    is_active,
    created_at
)
VALUES (
    'GOV-PH-001',
    'Philippine Government',
    'Other',
    'Government Agency',
    'info@government.ph',
    '02-1234-5678',
    'Philippines',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE provider_name = provider_name;

-- Get the provider ID
SET @gov_provider_id = (SELECT id FROM insurance_providers WHERE provider_code = 'GOV-PH-001' LIMIT 1);

-- ==========================================
-- Step 2: Add SSS (Social Security System) Plan
-- ==========================================
-- Monthly contribution rates based on ₱30,000 salary bracket
-- Employee: ₱1,350.00 (4.5% of ₱30,000)
-- Employer: ₱2,700.00 (9.0% of ₱30,000)
-- Total: ₱4,050.00 (13.5% of ₱30,000)

INSERT INTO insurance_plans (
    provider_id,
    plan_code,
    plan_name,
    plan_type,
    description,
    coverage_amount,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    effective_date,
    is_active,
    created_at
)
VALUES (
    @gov_provider_id,
    'GOV-SSS-2026',
    'SSS (Social Security System)',
    'Individual',
    'Mandatory social insurance program providing benefits for sickness, maternity, disability, retirement, death, and funeral. Contribution rates based on monthly salary credit of ₱30,000.',
    0.00,
    4050.00,
    2700.00,
    1350.00,
    '2024-01-01',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution);

-- Get SSS plan ID
SET @sss_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-SSS-2026' LIMIT 1);

-- ==========================================
-- Step 3: Add Pag-IBIG (Home Development Mutual Fund) Plan
-- ==========================================
-- Monthly contribution rates:
-- Employee: ₱100.00 (1% of ₱10,000 minimum, capped at ₱100)
-- Employer: ₱200.00 (2% of ₱10,000 minimum, capped at ₱200)
-- Total: ₱300.00

INSERT INTO insurance_plans (
    provider_id,
    plan_code,
    plan_name,
    plan_type,
    description,
    coverage_amount,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    effective_date,
    is_active,
    created_at
)
VALUES (
    @gov_provider_id,
    'GOV-PAGIBIG-2026',
    'Pag-IBIG Fund',
    'Individual',
    'National savings program and fund for housing benefits. Provides affordable housing loans, short-term loans, and provident savings. Mandatory for all employees.',
    0.00,
    300.00,
    200.00,
    100.00,
    '2024-01-01',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution);

-- Get Pag-IBIG plan ID
SET @pagibig_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-PAGIBIG-2026' LIMIT 1);

-- ==========================================
-- Step 4: Add PhilHealth Plan
-- ==========================================
-- Monthly contribution rates (based on ₱60,000 salary bracket):
-- Employee: ₱900.00 (1.5% of ₱60,000)
-- Employer: ₱900.00 (1.5% of ₱60,000)
-- Total: ₱1,800.00

INSERT INTO insurance_plans (
    provider_id,
    plan_code,
    plan_name,
    plan_type,
    description,
    coverage_amount,
    monthly_premium,
    employer_contribution,
    employee_contribution,
    effective_date,
    is_active,
    created_at
)
VALUES (
    @gov_provider_id,
    'GOV-PHILHEALTH-2026',
    'PhilHealth',
    'Individual',
    'National Health Insurance Program providing universal health care coverage. Covers inpatient and outpatient services, preventive care, emergency care, and other medical services.',
    0.00,
    1800.00,
    900.00,
    900.00,
    '2024-01-01',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    plan_name = VALUES(plan_name),
    description = VALUES(description),
    monthly_premium = VALUES(monthly_premium),
    employer_contribution = VALUES(employer_contribution),
    employee_contribution = VALUES(employee_contribution);

-- Get PhilHealth plan ID
SET @philhealth_plan_id = (SELECT id FROM insurance_plans WHERE plan_code = 'GOV-PHILHEALTH-2026' LIMIT 1);

-- ==========================================
-- Step 5: Enroll All Active Employees in SSS
-- ==========================================
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    employee_premium,
    employer_premium,
    dependents_count,
    created_at
)
SELECT 
    e.id,
    @sss_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    1350.00,
    2700.00,
    0,
    NOW()
FROM employees e
WHERE e.employment_status = 'active'
  AND NOT EXISTS (
      SELECT 1 
      FROM employee_insurance ei 
      WHERE ei.employee_id = e.id 
        AND ei.insurance_plan_id = @sss_plan_id
  );

-- ==========================================
-- Step 6: Enroll All Active Employees in Pag-IBIG
-- ==========================================
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    employee_premium,
    employer_premium,
    dependents_count,
    created_at
)
SELECT 
    e.id,
    @pagibig_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    100.00,
    200.00,
    0,
    NOW()
FROM employees e
WHERE e.employment_status = 'active'
  AND NOT EXISTS (
      SELECT 1 
      FROM employee_insurance ei 
      WHERE ei.employee_id = e.id 
        AND ei.insurance_plan_id = @pagibig_plan_id
  );

-- ==========================================
-- Step 7: Enroll All Active Employees in PhilHealth
-- ==========================================
INSERT INTO employee_insurance (
    employee_id,
    insurance_plan_id,
    enrollment_date,
    effective_date,
    status,
    employee_premium,
    employer_premium,
    dependents_count,
    created_at
)
SELECT 
    e.id,
    @philhealth_plan_id,
    CURDATE(),
    CURDATE(),
    'Active',
    900.00,
    900.00,
    0,
    NOW()
FROM employees e
WHERE e.employment_status = 'active'
  AND NOT EXISTS (
      SELECT 1 
      FROM employee_insurance ei 
      WHERE ei.employee_id = e.id 
        AND ei.insurance_plan_id = @philhealth_plan_id
  );

-- ==========================================
-- Verification Queries
-- ==========================================

-- Count total active employees
SELECT COUNT(*) as total_active_employees 
FROM employees 
WHERE employment_status = 'active';

-- Count SSS enrollments
SELECT COUNT(*) as sss_enrollments 
FROM employee_insurance ei
JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
WHERE ip.plan_code = 'GOV-SSS-2026';

-- Count Pag-IBIG enrollments
SELECT COUNT(*) as pagibig_enrollments 
FROM employee_insurance ei
JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
WHERE ip.plan_code = 'GOV-PAGIBIG-2026';

-- Count PhilHealth enrollments
SELECT COUNT(*) as philhealth_enrollments 
FROM employee_insurance ei
JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
WHERE ip.plan_code = 'GOV-PHILHEALTH-2026';

-- Show all government benefits with premium details
SELECT 
    ip.plan_name,
    ip.monthly_premium,
    ip.employer_contribution,
    ip.employee_contribution
FROM insurance_plans ip
WHERE ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026');
