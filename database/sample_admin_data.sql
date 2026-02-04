-- Sample Data Generation for Admin Features
-- This script generates sample data for testing admin functionalities
-- Run this after creating the main database and admin feature tables

USE hcm_system;

-- Generate sample performance evaluations for existing employees
INSERT INTO performance_evaluations (
    employee_id, evaluator_id, evaluation_period_start, evaluation_period_end,
    overall_rating, productivity_rating, quality_rating, communication_rating,
    teamwork_rating, leadership_rating, innovation_rating, goals_achievement,
    strengths, areas_for_improvement, recommendations, status, submitted_at, approved_at
)
SELECT 
    e.id,
    1 as evaluator_id, -- Admin user
    DATE_SUB(CURDATE(), INTERVAL 6 MONTH) as evaluation_period_start,
    DATE_SUB(CURDATE(), INTERVAL 1 DAY) as evaluation_period_end,
    ROUND(2.5 + (RAND() * 2.5), 2) as overall_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as productivity_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as quality_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as communication_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as teamwork_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as leadership_rating,
    ROUND(2.5 + (RAND() * 2.5), 2) as innovation_rating,
    ROUND(50 + (RAND() * 50), 2) as goals_achievement,
    'Demonstrates strong work ethic and dedication to tasks.' as strengths,
    'Could improve time management and prioritization skills.' as areas_for_improvement,
    'Continue professional development and attend relevant training programs.' as recommendations,
    'approved' as status,
    DATE_SUB(CURDATE(), INTERVAL 7 DAY) as submitted_at,
    DATE_SUB(CURDATE(), INTERVAL 3 DAY) as approved_at
FROM employees e
WHERE e.employment_status = 'Active'
LIMIT 20;

-- Generate sample performance goals for employees
INSERT INTO performance_goals (
    employee_id, goal_title, goal_description, goal_type, category,
    target_value, current_value, progress_percentage, priority, status,
    start_date, target_date, set_by
)
SELECT 
    e.id,
    CONCAT('Improve ', 
        CASE FLOOR(RAND() * 5)
            WHEN 0 THEN 'sales performance'
            WHEN 1 THEN 'customer satisfaction'
            WHEN 2 THEN 'project delivery time'
            WHEN 3 THEN 'team collaboration'
            ELSE 'technical skills'
        END
    ) as goal_title,
    'Work towards achieving measurable improvements in key performance indicators.' as goal_description,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'individual'
        WHEN 1 THEN 'team'
        ELSE 'organizational'
    END as goal_type,
    CASE FLOOR(RAND() * 6)
        WHEN 0 THEN 'productivity'
        WHEN 1 THEN 'quality'
        WHEN 2 THEN 'innovation'
        WHEN 3 THEN 'leadership'
        WHEN 4 THEN 'collaboration'
        ELSE 'other'
    END as category,
    CONCAT(FLOOR(80 + RAND() * 20), '%') as target_value,
    CONCAT(FLOOR(40 + RAND() * 40), '%') as current_value,
    ROUND(40 + (RAND() * 60), 2) as progress_percentage,
    CASE FLOOR(RAND() * 4)
        WHEN 0 THEN 'low'
        WHEN 1 THEN 'medium'
        WHEN 2 THEN 'high'
        ELSE 'critical'
    END as priority,
    CASE FLOOR(RAND() * 4)
        WHEN 0 THEN 'not_started'
        WHEN 1 THEN 'in_progress'
        WHEN 2 THEN 'completed'
        ELSE 'in_progress'
    END as status,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(30 + RAND() * 60) DAY) as start_date,
    DATE_ADD(CURDATE(), INTERVAL FLOOR(30 + RAND() * 90) DAY) as target_date,
    1 as set_by
FROM employees e
WHERE e.employment_status = 'Active'
ORDER BY RAND()
LIMIT 25;

-- Generate sample tax records for the current year
INSERT INTO tax_records (
    employee_id, tax_year, tax_period, period_month,
    gross_income, taxable_income, tax_withheld, tax_exemptions,
    tax_deductions, sss_contribution, philhealth_contribution,
    pagibig_contribution, other_deductions, net_taxable_income,
    tax_due, tax_paid, tax_balance, filing_status, number_of_dependents
)
SELECT 
    e.id,
    YEAR(CURDATE()) as tax_year,
    'monthly' as tax_period,
    MONTH(CURDATE()) as period_month,
    COALESCE(ec.basic_salary, 25000) as gross_income,
    COALESCE(ec.basic_salary, 25000) * 0.85 as taxable_income,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_withheld,
    1000 as tax_exemptions,
    500 as tax_deductions,
    CASE 
        WHEN COALESCE(ec.basic_salary, 25000) <= 20250 THEN 1125
        WHEN COALESCE(ec.basic_salary, 25000) <= 29750 THEN 1575
        ELSE 2250
    END as sss_contribution,
    COALESCE(ec.basic_salary, 25000) * 0.035 as philhealth_contribution,
    CASE 
        WHEN COALESCE(ec.basic_salary, 25000) <= 1500 THEN 30
        ELSE 200
    END as pagibig_contribution,
    0 as other_deductions,
    (COALESCE(ec.basic_salary, 25000) * 0.85) - 1500 as net_taxable_income,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_due,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_paid,
    0 as tax_balance,
    CASE e.marital_status
        WHEN 'married' THEN 'married'
        ELSE 'single'
    END as filing_status,
    FLOOR(RAND() * 3) as number_of_dependents
FROM employees e
LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
WHERE e.employment_status = 'Active'
LIMIT 30;

-- Generate tax records for previous months (last 6 months)
INSERT INTO tax_records (
    employee_id, tax_year, tax_period, period_month,
    gross_income, taxable_income, tax_withheld, tax_exemptions,
    tax_deductions, sss_contribution, philhealth_contribution,
    pagibig_contribution, other_deductions, net_taxable_income,
    tax_due, tax_paid, tax_balance, filing_status, number_of_dependents
)
SELECT 
    e.id,
    CASE 
        WHEN MONTH(DATE_SUB(CURDATE(), INTERVAL months.n MONTH)) = 1 THEN YEAR(CURDATE()) - 1
        ELSE YEAR(CURDATE())
    END as tax_year,
    'monthly' as tax_period,
    MONTH(DATE_SUB(CURDATE(), INTERVAL months.n MONTH)) as period_month,
    COALESCE(ec.basic_salary, 25000) as gross_income,
    COALESCE(ec.basic_salary, 25000) * 0.85 as taxable_income,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_withheld,
    1000 as tax_exemptions,
    500 as tax_deductions,
    CASE 
        WHEN COALESCE(ec.basic_salary, 25000) <= 20250 THEN 1125
        WHEN COALESCE(ec.basic_salary, 25000) <= 29750 THEN 1575
        ELSE 2250
    END as sss_contribution,
    COALESCE(ec.basic_salary, 25000) * 0.035 as philhealth_contribution,
    CASE 
        WHEN COALESCE(ec.basic_salary, 25000) <= 1500 THEN 30
        ELSE 200
    END as pagibig_contribution,
    0 as other_deductions,
    (COALESCE(ec.basic_salary, 25000) * 0.85) - 1500 as net_taxable_income,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_due,
    COALESCE(ec.basic_salary, 25000) * 0.15 as tax_paid,
    0 as tax_balance,
    CASE e.marital_status
        WHEN 'married' THEN 'married'
        ELSE 'single'
    END as filing_status,
    FLOOR(RAND() * 3) as number_of_dependents
FROM employees e
LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
CROSS JOIN (
    SELECT 1 as n UNION SELECT 2 UNION SELECT 3 
    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
) as months
WHERE e.employment_status = 'Active'
ORDER BY RAND()
LIMIT 100;

-- Assign some employees to salary structures
INSERT INTO employee_salary_structures (
    employee_id, salary_structure_id, assigned_date, effective_from,
    assigned_by, is_active
)
SELECT 
    e.id,
    CASE 
        WHEN COALESCE(ec.basic_salary, 25000) < 25000 THEN 1
        WHEN COALESCE(ec.basic_salary, 25000) < 40000 THEN 2
        WHEN COALESCE(ec.basic_salary, 25000) < 65000 THEN 3
        WHEN COALESCE(ec.basic_salary, 25000) < 95000 THEN 4
        WHEN COALESCE(ec.basic_salary, 25000) < 130000 THEN 5
        WHEN COALESCE(ec.basic_salary, 25000) < 180000 THEN 6
        ELSE 7
    END as salary_structure_id,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(30 + RAND() * 180) DAY) as assigned_date,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(30 + RAND() * 180) DAY) as effective_from,
    1 as assigned_by,
    1 as is_active
FROM employees e
LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
WHERE e.employment_status = 'Active'
ORDER BY RAND()
LIMIT 25;

-- Generate some AI interaction logs for demo
INSERT INTO ai_interaction_logs (
    user_id, interaction_type, query_text, response_text,
    confidence_score, execution_time_ms, was_helpful
)
VALUES
(1, 'query', 'What is the average salary in our organization?', 'Based on current data, the average salary across your organization is approximately ₱45,000. The IT department has the highest average at ₱65,000.', 85, 125, 1),
(1, 'query', 'Show me top performers', 'Currently, you have 8 top performers with an average rating above 4.0. Top performers include employees from Sales, IT, and Marketing departments.', 90, 98, 1),
(1, 'query', 'What is our turnover rate?', 'Your annual turnover rate is approximately 8.5%. This is within healthy industry standards. Continue your current retention strategies.', 80, 156, 1),
(1, 'analysis', 'Analyze department performance', 'IT department shows the highest performance rating at 4.2, followed by Sales at 3.8. The Operations department shows opportunity for improvement with an average rating of 3.2.', 88, 234, NULL),
(1, 'recommendation', 'Suggest salary adjustments', 'I recommend reviewing compensation for 3 employees whose current salary is below their assigned structure minimum. Additionally, 5 top performers may benefit from performance-based bonuses.', 92, 187, 1);

-- Generate AI recommendations
INSERT INTO ai_recommendations (
    recommendation_type, employee_id, title, description,
    recommendation, confidence_score, priority, status
)
SELECT 
    'performance_concern' as recommendation_type,
    e.id,
    CONCAT('Schedule Performance Review: ', e.first_name, ' ', e.last_name) as title,
    CONCAT('No performance evaluation recorded in the last 6 months for ', e.first_name, ' ', e.last_name) as description,
    'Schedule a comprehensive performance review to assess current performance, set goals, and provide feedback.' as recommendation,
    90 as confidence_score,
    'medium' as priority,
    'pending' as status
FROM employees e
LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
WHERE e.employment_status = 'Active'
AND pe.id IS NULL
ORDER BY RAND()
LIMIT 5;

-- Add salary adjustment recommendations
INSERT INTO ai_recommendations (
    recommendation_type, employee_id, title, description,
    recommendation, confidence_score, priority, status
)
SELECT 
    'salary_adjustment' as recommendation_type,
    e.id,
    CONCAT('Salary Below Structure: ', e.first_name, ' ', e.last_name) as title,
    CONCAT(e.first_name, ' ', e.last_name, ' is earning ₱', FORMAT(ec.basic_salary, 2), ' which is below their structure minimum') as description,
    CONCAT('Increase salary to align with assigned salary structure minimum of ₱', FORMAT(ss.min_salary, 2)) as recommendation,
    95 as confidence_score,
    'high' as priority,
    'pending' as status
FROM employees e
JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
LEFT JOIN employee_salary_structures ess ON e.id = ess.employee_id AND ess.is_active = 1
LEFT JOIN salary_structures ss ON ess.salary_structure_id = ss.id
WHERE e.employment_status = 'Active'
AND ec.basic_salary < ss.min_salary
LIMIT 3;

-- Update some goals to completed status
UPDATE performance_goals 
SET status = 'completed', 
    completion_date = CURDATE(),
    progress_percentage = 100
WHERE status = 'in_progress' 
AND RAND() < 0.3
LIMIT 5;

COMMIT;

-- Summary query to show what was created
SELECT 
    'Performance Evaluations' as feature,
    COUNT(*) as records_created
FROM performance_evaluations
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)

UNION ALL

SELECT 
    'Performance Goals' as feature,
    COUNT(*) as records_created
FROM performance_goals
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)

UNION ALL

SELECT 
    'Tax Records' as feature,
    COUNT(*) as records_created
FROM tax_records
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)

UNION ALL

SELECT 
    'Salary Structure Assignments' as feature,
    COUNT(*) as records_created
FROM employee_salary_structures
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)

UNION ALL

SELECT 
    'AI Interaction Logs' as feature,
    COUNT(*) as records_created
FROM ai_interaction_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)

UNION ALL

SELECT 
    'AI Recommendations' as feature,
    COUNT(*) as records_created
FROM ai_recommendations
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);
