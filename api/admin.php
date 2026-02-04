<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Please login first'
    ]);
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden - Admin access required'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetRequest($pdo);
    } else if ($method === 'POST') {
        handlePostRequest($pdo, $user_id);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function handleGetRequest($pdo) {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'search_employees':
            searchEmployees($pdo);
            break;
        case 'get_tax_records':
            getTaxRecords($pdo);
            break;
        case 'get_performance_data':
            getPerformanceData($pdo);
            break;
        case 'get_goals':
            getGoals($pdo);
            break;
        case 'get_salary_structures':
            getSalaryStructures($pdo);
            break;
        case 'get_salary_comparison':
            getSalaryComparison($pdo);
            break;
        case 'get_ai_history':
            getAIHistory($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

function handlePostRequest($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'set_goal':
            setGoal($pdo, $user_id, $input);
            break;
        case 'delete_goal':
            deleteGoal($pdo, $input);
            break;
        case 'assign_salary_structure':
            assignSalaryStructure($pdo, $user_id, $input);
            break;
        case 'ai_query':
            processAIQuery($pdo, $user_id, $input);
            break;
        case 'rate_ai_response':
            rateAIResponse($pdo, $input);
            break;
        case 'generate_ai_recommendations':
            generateAIRecommendations($pdo, $user_id, $input);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

// Employee Search Function
function searchEmployees($pdo) {
    $search = $_GET['search'] ?? '';
    $department = $_GET['department'] ?? '';
    $position = $_GET['position'] ?? '';
    $status = $_GET['status'] ?? '';
    $salaryRange = $_GET['salary_range'] ?? '';
    $hireDate = $_GET['hire_date'] ?? '';
    $gender = $_GET['gender'] ?? '';

    $whereConditions = ["e.employment_status != 'Terminated'"];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ? OR e.email LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($department)) {
        $whereConditions[] = "d.dept_name = ?";
        $params[] = $department;
    }

    if (!empty($position)) {
        $whereConditions[] = "p.position_title = ?";
        $params[] = $position;
    }

    if (!empty($status)) {
        $whereConditions[] = "e.employment_status = ?";
        $params[] = $status;
    }

    if (!empty($salaryRange)) {
        list($min, $max) = explode('-', $salaryRange);
        $whereConditions[] = "ec.basic_salary BETWEEN ? AND ?";
        $params[] = $min;
        $params[] = $max;
    }

    if (!empty($hireDate)) {
        $whereConditions[] = "e.hire_date >= ?";
        $params[] = $hireDate;
    }

    if (!empty($gender)) {
        $whereConditions[] = "e.gender = ?";
        $params[] = $gender;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            e.id,
            e.employee_id,
            e.first_name,
            e.last_name,
            e.email,
            e.phone,
            e.employment_status,
            e.hire_date,
            e.gender,
            e.profile_picture,
            d.dept_name as department,
            p.position_title as position,
            ec.basic_salary
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
        WHERE $whereClause
        ORDER BY e.last_name, e.first_name
        LIMIT 100
    ");

    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $employees,
        'count' => count($employees)
    ]);
}

// Tax Records Function
function getTaxRecords($pdo) {
    $year = $_GET['year'] ?? date('Y');
    $period = $_GET['period'] ?? '';

    $whereConditions = ["tr.tax_year = ?"];
    $params = [$year];

    if (!empty($period)) {
        $whereConditions[] = "tr.tax_period = ?";
        $params[] = $period;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get tax records
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.employee_id,
            d.dept_name as department
        FROM tax_records tr
        JOIN employees e ON tr.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE $whereClause
        ORDER BY tr.tax_year DESC, tr.period_month DESC, e.last_name
    ");

    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get summary
    $summaryStmt = $pdo->prepare("
        SELECT 
            SUM(tax_withheld) as total_tax_withheld,
            SUM(taxable_income) as total_taxable_income,
            SUM(sss_contribution) as total_sss,
            SUM(philhealth_contribution + pagibig_contribution) as total_other
        FROM tax_records
        WHERE $whereClause
    ");

    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $records,
        'summary' => $summary
    ]);
}

// Performance Data Function
function getPerformanceData($pdo) {
    // Get top performers
    $topPerformers = $pdo->query("
        SELECT * FROM top_performers
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get underperformers
    $underperformers = $pdo->query("
        SELECT * FROM underperformers
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get all performance data
    $allPerformance = $pdo->query("
        SELECT * FROM employee_performance_summary
        ORDER BY avg_performance_rating DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get department performance
    $deptPerformance = $pdo->query("
        SELECT 
            d.dept_name as department,
            AVG(pe.overall_rating) as avg_rating,
            COUNT(DISTINCT pe.employee_id) as employee_count
        FROM departments d
        LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'Active'
        LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
        GROUP BY d.id, d.dept_name
        HAVING employee_count > 0
        ORDER BY avg_rating DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'top_performers' => $topPerformers,
            'underperformers' => $underperformers,
            'all_performance' => $allPerformance,
            'department_performance' => $deptPerformance
        ]
    ]);
}

// Goals Functions
function getGoals($pdo) {
    $employeeId = $_GET['employee_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $priority = $_GET['priority'] ?? '';

    $whereConditions = ["1=1"];
    $params = [];

    if (!empty($employeeId)) {
        $whereConditions[] = "pg.employee_id = ?";
        $params[] = $employeeId;
    }

    if (!empty($status)) {
        $whereConditions[] = "pg.status = ?";
        $params[] = $status;
    }

    if (!empty($priority)) {
        $whereConditions[] = "pg.priority = ?";
        $params[] = $priority;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $stmt = $pdo->prepare("
        SELECT 
            pg.*,
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.employee_id as emp_number,
            d.dept_name as department
        FROM performance_goals pg
        JOIN employees e ON pg.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE $whereClause
        ORDER BY 
            CASE pg.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            pg.target_date ASC
    ");

    $stmt->execute($params);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            AVG(progress_percentage) as avg_progress
        FROM performance_goals
    ")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $goals,
        'statistics' => $stats
    ]);
}

function setGoal($pdo, $user_id, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO performance_goals (
            employee_id, goal_title, goal_description, goal_type, category,
            target_value, priority, start_date, target_date, set_by, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'not_started')
    ");

    $result = $stmt->execute([
        $data['employee_id'],
        $data['goal_title'],
        $data['goal_description'] ?? null,
        $data['goal_type'],
        $data['category'],
        $data['target_value'] ?? null,
        $data['priority'],
        $data['start_date'],
        $data['target_date'],
        $user_id
    ]);

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Goal set successfully' : 'Failed to set goal',
        'goal_id' => $pdo->lastInsertId()
    ]);
}

function deleteGoal($pdo, $data) {
    $stmt = $pdo->prepare("DELETE FROM performance_goals WHERE id = ?");
    $result = $stmt->execute([$data['goal_id']]);

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Goal deleted successfully' : 'Failed to delete goal'
    ]);
}

// Salary Structure Functions
function getSalaryStructures($pdo) {
    // Get all structures
    $structures = $pdo->query("
        SELECT * FROM salary_structures
        ORDER BY 
            CASE 
                WHEN grade_level = 'G1' THEN 1
                WHEN grade_level = 'G2' THEN 2
                WHEN grade_level = 'G3' THEN 3
                WHEN grade_level = 'G4' THEN 4
                WHEN grade_level = 'G5' THEN 5
                WHEN grade_level = 'G6' THEN 6
                WHEN grade_level = 'G7' THEN 7
                ELSE 999
            END
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get assignments
    $assignments = $pdo->query("
        SELECT 
            e.id as employee_id,
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.employee_id as emp_number,
            d.dept_name as department,
            ss.structure_name,
            ss.grade_level,
            ss.min_salary,
            ss.max_salary,
            ec.basic_salary as current_salary,
            ess.assigned_date,
            ess.effective_from
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
        LEFT JOIN employee_salary_structures ess ON e.id = ess.employee_id AND ess.is_active = 1
        LEFT JOIN salary_structures ss ON ess.salary_structure_id = ss.id
        WHERE e.employment_status = 'Active'
        ORDER BY e.last_name, e.first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT ss.id) as total_structures,
            COUNT(DISTINCT CASE WHEN ess.id IS NOT NULL THEN e.id END) as assigned_employees,
            COUNT(DISTINCT CASE WHEN ess.id IS NULL THEN e.id END) as unassigned_employees
        FROM employees e
        LEFT JOIN employee_salary_structures ess ON e.id = ess.employee_id AND ess.is_active = 1
        LEFT JOIN salary_structures ss ON ss.is_active = 1
        WHERE e.employment_status = 'Active'
    ")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'structures' => $structures,
            'assignments' => $assignments,
            'statistics' => $stats
        ]
    ]);
}

function assignSalaryStructure($pdo, $user_id, $data) {
    try {
        $pdo->beginTransaction();

        // Deactivate any existing assignments
        $stmt = $pdo->prepare("
            UPDATE employee_salary_structures 
            SET is_active = 0, effective_to = CURDATE()
            WHERE employee_id = ? AND is_active = 1
        ");
        $stmt->execute([$data['employee_id']]);

        // Insert new assignment
        $stmt = $pdo->prepare("
            INSERT INTO employee_salary_structures (
                employee_id, salary_structure_id, assigned_date, 
                effective_from, assigned_by, notes, is_active
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, 1)
        ");
        $stmt->execute([
            $data['employee_id'],
            $data['salary_structure_id'],
            $data['effective_from'],
            $user_id,
            $data['notes'] ?? null
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Salary structure assigned successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Failed to assign salary structure: ' . $e->getMessage()
        ]);
    }
}

// Salary Comparison Function
function getSalaryComparison($pdo) {
    $data = $pdo->query("
        SELECT * FROM department_salary_comparison
        ORDER BY avg_salary DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

// AI Functions
function processAIQuery($pdo, $user_id, $data) {
    $query = $data['query'];
    
    // Simulate AI processing
    $response = generateAIResponse($pdo, $query);
    
    // Log the interaction
    $stmt = $pdo->prepare("
        INSERT INTO ai_interaction_logs (
            user_id, interaction_type, query_text, response_text, 
            confidence_score, execution_time_ms
        ) VALUES (?, 'query', ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $query,
        $response['text'],
        $response['confidence'],
        $response['execution_time']
    ]);
    
    echo json_encode([
        'success' => true,
        'response' => $response['text'],
        'confidence' => $response['confidence'],
        'interaction_id' => $pdo->lastInsertId()
    ]);
}

function generateAIResponse($pdo, $query) {
    $startTime = microtime(true);
    $query = strtolower($query);
    
    // Simple keyword-based AI responses
    if (strpos($query, 'salary') !== false || strpos($query, 'compensation') !== false) {
        $avgSalary = $pdo->query("SELECT AVG(basic_salary) as avg FROM employee_compensation WHERE is_active = 1")->fetch();
        $response = "Based on current data, the average salary across your organization is ₱" . number_format($avgSalary['avg'], 2) . ". ";
        
        if (strpos($query, 'highest') !== false || strpos($query, 'top') !== false) {
            $highest = $pdo->query("
                SELECT d.dept_name, AVG(ec.basic_salary) as avg_salary
                FROM departments d
                JOIN employees e ON d.id = e.department_id
                JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
                GROUP BY d.id
                ORDER BY avg_salary DESC
                LIMIT 1
            ")->fetch();
            $response .= "The department with the highest average salary is {$highest['dept_name']} at ₱" . number_format($highest['avg_salary'], 2) . ".";
        }
        
        $confidence = 85;
    } else if (strpos($query, 'performance') !== false) {
        $topCount = $pdo->query("SELECT COUNT(*) as cnt FROM top_performers")->fetch()['cnt'];
        $underCount = $pdo->query("SELECT COUNT(*) as cnt FROM underperformers")->fetch()['cnt'];
        
        $response = "Currently, you have {$topCount} top performers and {$underCount} employees who need attention. ";
        $response .= "I recommend focusing on coaching and development programs for underperformers while recognizing and rewarding top talent.";
        
        $confidence = 90;
    } else if (strpos($query, 'retention') !== false || strpos($query, 'turnover') !== false) {
        $terminated = $pdo->query("SELECT COUNT(*) as cnt FROM employees WHERE employment_status = 'Terminated' AND termination_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)")->fetch()['cnt'];
        $total = $pdo->query("SELECT COUNT(*) as cnt FROM employees WHERE employment_status IN ('Active', 'On Leave')")->fetch()['cnt'];
        
        $turnoverRate = ($terminated / ($total + $terminated)) * 100;
        $response = "Your annual turnover rate is approximately " . number_format($turnoverRate, 1) . "%. ";
        
        if ($turnoverRate > 15) {
            $response .= "This is higher than industry average. I recommend reviewing compensation packages, conducting exit interviews, and improving employee engagement programs.";
        } else {
            $response .= "This is within healthy industry standards. Continue your current retention strategies.";
        }
        
        $confidence = 80;
    } else if (strpos($query, 'tax') !== false) {
        $taxData = $pdo->query("
            SELECT 
                SUM(tax_withheld) as total_tax,
                SUM(taxable_income) as total_taxable
            FROM tax_records
            WHERE tax_year = YEAR(CURDATE())
        ")->fetch();
        
        $response = "For the current year, total tax withheld is ₱" . number_format($taxData['total_tax'] ?? 0, 2) . " ";
        $response .= "on a total taxable income of ₱" . number_format($taxData['total_taxable'] ?? 0, 2) . ".";
        
        $confidence = 95;
    } else {
        $response = "I understand you're asking about: '" . htmlspecialchars($data['query'] ?? $query) . "'. ";
        $response .= "I can help you with salary analysis, performance reviews, retention strategies, tax records, and workforce insights. ";
        $response .= "Could you please be more specific about what you'd like to know?";
        
        $confidence = 60;
    }
    
    $executionTime = (microtime(true) - $startTime) * 1000;
    
    return [
        'text' => $response,
        'confidence' => $confidence,
        'execution_time' => round($executionTime, 2)
    ];
}

function rateAIResponse($pdo, $data) {
    $stmt = $pdo->prepare("
        UPDATE ai_interaction_logs 
        SET was_helpful = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $data['helpful'] ? 1 : 0,
        $data['interaction_id']
    ]);
    
    echo json_encode([
        'success' => $result,
        'message' => 'Thank you for your feedback'
    ]);
}

function generateAIRecommendations($pdo, $user_id, $data) {
    $type = $data['type'];
    $recommendations = [];
    
    switch ($type) {
        case 'salary':
            // Check for employees below structure minimum
            $belowMin = $pdo->query("
                SELECT 
                    e.id,
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    ec.basic_salary,
                    ss.min_salary,
                    d.dept_name
                FROM employees e
                JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
                LEFT JOIN employee_salary_structures ess ON e.id = ess.employee_id AND ess.is_active = 1
                LEFT JOIN salary_structures ss ON ess.salary_structure_id = ss.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE ec.basic_salary < ss.min_salary
                AND e.employment_status = 'Active'
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($belowMin as $emp) {
                $recommendations[] = [
                    'recommendation_type' => 'salary_adjustment',
                    'employee_id' => $emp['id'],
                    'title' => 'Salary Below Structure Minimum: ' . $emp['name'],
                    'description' => $emp['name'] . ' in ' . $emp['dept_name'] . ' is earning ₱' . number_format($emp['basic_salary'], 2) . ' which is below the structure minimum of ₱' . number_format($emp['min_salary'], 2),
                    'recommendation' => 'Increase salary to at least ₱' . number_format($emp['min_salary'], 2) . ' to align with salary structure',
                    'priority' => 'high',
                    'confidence_score' => 95
                ];
            }
            break;
            
        case 'performance':
            // Recommend performance reviews for employees without recent evaluations
            $needsReview = $pdo->query("
                SELECT 
                    e.id,
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    d.dept_name,
                    COALESCE(MAX(pe.evaluation_period_end), e.hire_date) as last_review
                FROM employees e
                LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.employment_status = 'Active'
                GROUP BY e.id
                HAVING last_review < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                ORDER BY last_review ASC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($needsReview as $emp) {
                $recommendations[] = [
                    'recommendation_type' => 'performance_concern',
                    'employee_id' => $emp['id'],
                    'title' => 'Performance Review Overdue: ' . $emp['name'],
                    'description' => 'No performance evaluation in the last 6 months',
                    'recommendation' => 'Schedule a performance review to assess goals and provide feedback',
                    'priority' => 'medium',
                    'confidence_score' => 90
                ];
            }
            break;
            
        case 'retention':
            // Identify potential retention risks
            $risks = $pdo->query("
                SELECT 
                    e.id,
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    d.dept_name,
                    AVG(pe.overall_rating) as avg_rating,
                    ec.basic_salary
                FROM employees e
                LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
                WHERE e.employment_status = 'Active'
                GROUP BY e.id
                HAVING avg_rating >= 4.0
                ORDER BY ec.basic_salary ASC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($risks as $emp) {
                $recommendations[] = [
                    'recommendation_type' => 'retention_risk',
                    'employee_id' => $emp['id'],
                    'title' => 'High Performer, Potential Flight Risk: ' . $emp['name'],
                    'description' => 'Top performer with average rating of ' . number_format($emp['avg_rating'], 1) . ' but lower compensation',
                    'recommendation' => 'Consider salary increase or promotion to retain this valuable employee',
                    'priority' => 'critical',
                    'confidence_score' => 85
                ];
            }
            break;
            
        case 'training':
            // Recommend training for underperformers
            $needsTraining = $pdo->query("
                SELECT 
                    e.id,
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    d.dept_name,
                    AVG(pe.overall_rating) as avg_rating
                FROM employees e
                LEFT JOIN performance_evaluations pe ON e.id = pe.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.employment_status = 'Active'
                GROUP BY e.id
                HAVING avg_rating < 3.0 AND avg_rating IS NOT NULL
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($needsTraining as $emp) {
                $recommendations[] = [
                    'recommendation_type' => 'training',
                    'employee_id' => $emp['id'],
                    'title' => 'Training Needed: ' . $emp['name'],
                    'description' => 'Performance rating of ' . number_format($emp['avg_rating'], 1) . ' indicates need for skill development',
                    'recommendation' => 'Enroll in relevant training programs and provide mentorship',
                    'priority' => 'high',
                    'confidence_score' => 88
                ];
            }
            break;
    }
    
    // Save recommendations to database
    foreach ($recommendations as $rec) {
        $stmt = $pdo->prepare("
            INSERT INTO ai_recommendations (
                recommendation_type, employee_id, title, description, 
                recommendation, confidence_score, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $rec['recommendation_type'],
            $rec['employee_id'] ?? null,
            $rec['title'],
            $rec['description'],
            $rec['recommendation'],
            $rec['confidence_score'],
            $rec['priority']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
}

function getAIHistory($pdo) {
    $stmt = $pdo->query("
        SELECT 
            id,
            interaction_type,
            query_text,
            confidence_score,
            was_helpful,
            created_at
        FROM ai_interaction_logs
        ORDER BY created_at DESC
        LIMIT 50
    ");
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
}
