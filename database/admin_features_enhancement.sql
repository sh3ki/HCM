-- HCM System - Admin Features Enhancement
-- Additional tables for admin functionalities
-- Created: 2026-02-04

USE hcm_system;

-- Salary Structures Table
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    structure_name VARCHAR(100) NOT NULL,
    description TEXT,
    min_salary DECIMAL(12,2) NOT NULL,
    max_salary DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PHP',
    grade_level VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employee Salary Structure Assignment
CREATE TABLE IF NOT EXISTS employee_salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    salary_structure_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,
    assigned_by INT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (salary_structure_id) REFERENCES salary_structures(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Performance Goals Table
CREATE TABLE IF NOT EXISTS performance_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    goal_title VARCHAR(200) NOT NULL,
    goal_description TEXT,
    goal_type ENUM('individual', 'team', 'organizational') DEFAULT 'individual',
    category ENUM('productivity', 'quality', 'innovation', 'leadership', 'collaboration', 'other') DEFAULT 'other',
    target_value VARCHAR(100),
    current_value VARCHAR(100),
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('not_started', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'not_started',
    start_date DATE NOT NULL,
    target_date DATE NOT NULL,
    completion_date DATE,
    set_by INT NOT NULL,
    reviewed_by INT,
    review_date DATE,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (set_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Performance Evaluations Table (Enhanced)
CREATE TABLE IF NOT EXISTS performance_evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    evaluation_period_start DATE NOT NULL,
    evaluation_period_end DATE NOT NULL,
    overall_rating DECIMAL(3,2) NOT NULL,
    productivity_rating DECIMAL(3,2),
    quality_rating DECIMAL(3,2),
    communication_rating DECIMAL(3,2),
    teamwork_rating DECIMAL(3,2),
    leadership_rating DECIMAL(3,2),
    innovation_rating DECIMAL(3,2),
    goals_achievement DECIMAL(5,2) DEFAULT 0,
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    status ENUM('draft', 'submitted', 'reviewed', 'approved') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tax Records Table
CREATE TABLE IF NOT EXISTS tax_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    tax_year YEAR NOT NULL,
    tax_period ENUM('monthly', 'quarterly', 'annual') DEFAULT 'monthly',
    period_month INT,
    period_quarter INT,
    gross_income DECIMAL(15,2) NOT NULL DEFAULT 0,
    taxable_income DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_withheld DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_exemptions DECIMAL(12,2) DEFAULT 0,
    tax_deductions DECIMAL(12,2) DEFAULT 0,
    sss_contribution DECIMAL(10,2) DEFAULT 0,
    philhealth_contribution DECIMAL(10,2) DEFAULT 0,
    pagibig_contribution DECIMAL(10,2) DEFAULT 0,
    other_deductions DECIMAL(10,2) DEFAULT 0,
    net_taxable_income DECIMAL(15,2) DEFAULT 0,
    tax_due DECIMAL(12,2) DEFAULT 0,
    tax_paid DECIMAL(12,2) DEFAULT 0,
    tax_balance DECIMAL(12,2) DEFAULT 0,
    filing_status ENUM('single', 'married', 'head_of_family') DEFAULT 'single',
    number_of_dependents INT DEFAULT 0,
    bir_form_type VARCHAR(20),
    filing_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_tax_period (employee_id, tax_year, tax_period, period_month, period_quarter)
);

-- AI Interaction Logs Table
CREATE TABLE IF NOT EXISTS ai_interaction_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    interaction_type ENUM('query', 'recommendation', 'analysis', 'prediction') DEFAULT 'query',
    query_text TEXT NOT NULL,
    response_text TEXT,
    context_data JSON,
    confidence_score DECIMAL(5,2),
    execution_time_ms INT,
    was_helpful BOOLEAN,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Recommendations Table
CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recommendation_type ENUM('salary_adjustment', 'promotion', 'training', 'performance_concern', 'retention_risk', 'other') NOT NULL,
    employee_id INT,
    department_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    confidence_score DECIMAL(5,2),
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'reviewed', 'accepted', 'rejected', 'implemented') DEFAULT 'pending',
    data_points JSON,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default salary structures
INSERT INTO salary_structures (structure_name, description, min_salary, max_salary, grade_level) VALUES
('Entry Level - Grade 1', 'Entry level positions for fresh graduates', 15000.00, 25000.00, 'G1'),
('Junior Level - Grade 2', 'Junior positions with 1-3 years experience', 25000.00, 40000.00, 'G2'),
('Mid Level - Grade 3', 'Mid-level positions with 3-5 years experience', 40000.00, 65000.00, 'G3'),
('Senior Level - Grade 4', 'Senior positions with 5-8 years experience', 65000.00, 95000.00, 'G4'),
('Lead Level - Grade 5', 'Lead/Principal positions with 8+ years experience', 95000.00, 130000.00, 'G5'),
('Management - Grade 6', 'Management positions', 130000.00, 180000.00, 'G6'),
('Senior Management - Grade 7', 'Senior management and executive positions', 180000.00, 300000.00, 'G7');

-- Create indexes for better performance
CREATE INDEX idx_salary_structures_active ON salary_structures(is_active);
CREATE INDEX idx_employee_salary_structures_employee ON employee_salary_structures(employee_id);
CREATE INDEX idx_employee_salary_structures_active ON employee_salary_structures(is_active);
CREATE INDEX idx_performance_goals_employee ON performance_goals(employee_id);
CREATE INDEX idx_performance_goals_status ON performance_goals(status);
CREATE INDEX idx_performance_goals_dates ON performance_goals(start_date, target_date);
CREATE INDEX idx_performance_evaluations_employee ON performance_evaluations(employee_id);
CREATE INDEX idx_performance_evaluations_period ON performance_evaluations(evaluation_period_start, evaluation_period_end);
CREATE INDEX idx_tax_records_employee ON tax_records(employee_id);
CREATE INDEX idx_tax_records_year ON tax_records(tax_year);
CREATE INDEX idx_ai_interaction_logs_user ON ai_interaction_logs(user_id);
CREATE INDEX idx_ai_recommendations_type ON ai_recommendations(recommendation_type);
CREATE INDEX idx_ai_recommendations_status ON ai_recommendations(status);

-- Create views for admin analytics
CREATE OR REPLACE VIEW employee_performance_summary AS
SELECT
    e.id AS employee_id,
    e.employee_number,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.name AS department,
    p.title AS position,
    AVG(pe.overall_rating) AS avg_performance_rating,
    COUNT(pe.id) AS total_evaluations,
    MAX(pe.evaluation_period_end) AS last_evaluation_date,
    AVG(pe.goals_achievement) AS avg_goals_achievement,
    COUNT(pg.id) AS total_goals,
    SUM(CASE WHEN pg.status = 'completed' THEN 1 ELSE 0 END) AS completed_goals,
    (SUM(CASE WHEN pg.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(pg.id), 0)) AS goal_completion_rate
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN positions p ON e.position_id = p.id
LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
LEFT JOIN performance_goals pg ON e.id = pg.employee_id
WHERE e.employment_status = 'active'
GROUP BY e.id, e.employee_number, e.first_name, e.last_name, d.name, p.title;

CREATE OR REPLACE VIEW department_salary_comparison AS
SELECT
    d.id AS department_id,
    d.name AS department_name,
    COUNT(DISTINCT e.id) AS employee_count,
    AVG(c.base_salary) AS avg_salary,
    MIN(c.base_salary) AS min_salary,
    MAX(c.base_salary) AS max_salary,
    SUM(c.base_salary) AS total_salary_cost,
    AVG(CASE WHEN c.salary_type = 'monthly' THEN c.base_salary * 12 ELSE c.base_salary END) AS avg_annual_cost
FROM departments d
LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'active'
LEFT JOIN compensation c ON e.id = c.employee_id AND c.is_active = TRUE
GROUP BY d.id, d.name;

CREATE OR REPLACE VIEW top_performers AS
SELECT
    e.id,
    e.employee_number,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.name AS department,
    p.title AS position,
    AVG(pe.overall_rating) AS avg_rating,
    COUNT(pe.id) AS evaluation_count,
    AVG(pe.goals_achievement) AS avg_goals_achievement
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN positions p ON e.position_id = p.id
LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
WHERE e.employment_status = 'active'
GROUP BY e.id, e.employee_number, e.first_name, e.last_name, d.name, p.title
HAVING AVG(pe.overall_rating) >= 4.0 AND evaluation_count >= 1
ORDER BY avg_rating DESC, avg_goals_achievement DESC;

CREATE OR REPLACE VIEW underperformers AS
SELECT
    e.id,
    e.employee_number,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.name AS department,
    p.title AS position,
    AVG(pe.overall_rating) AS avg_rating,
    COUNT(pe.id) AS evaluation_count,
    AVG(pe.goals_achievement) AS avg_goals_achievement
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN positions p ON e.position_id = p.id
LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
WHERE e.employment_status = 'active'
GROUP BY e.id, e.employee_number, e.first_name, e.last_name, d.name, p.title
HAVING AVG(pe.overall_rating) < 3.0 AND evaluation_count >= 1
ORDER BY avg_rating ASC, avg_goals_achievement ASC;

COMMIT;
