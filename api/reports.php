<?php
// reports.php - API endpoint for reports and analytics

session_start();
require_once __DIR__ . '/../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
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
        'error' => 'Unauthorized - Please login first',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $user_id);
            break;
        case 'POST':
            handlePost($pdo, $user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}

function handleGet($pdo, $user_id) {
    // Get query parameters
    $type = $_GET['type'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $format = $_GET['format'] ?? 'json';

    switch ($type) {
        case 'dashboard_metrics':
            getDashboardMetrics($pdo, $user_id);
            break;
        case 'employee':
            getEmployeeReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'attendance':
            getAttendanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'payroll':
            getPayrollReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'leave':
            getLeaveReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'department':
            getDepartmentReport($pdo, $user_id, $from_date, $to_date, $format);
            break;
        case 'performance':
            getPerformanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'benefits':
            getBenefitsReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
            break;
        case 'charts':
            getChartsData($pdo, $user_id);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid report type'
            ]);
    }
}

function handlePost($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        return;
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'generate_custom_report':
            generateCustomReport($pdo, $user_id, $input);
            break;
        case 'export_report':
            exportReport($pdo, $user_id, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
}

function getDashboardMetrics($pdo, $user_id) {
    try {
        // Get total employees
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_employees FROM employees WHERE employment_status = 'Active'");
        $stmt->execute();
        $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

        // Get total departments
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_departments FROM departments");
        $stmt->execute();
        $total_departments = $stmt->fetch(PDO::FETCH_ASSOC)['total_departments'];

        // Get average attendance (mock data for now - would need attendance_records table)
        $avg_attendance = 94.2;

        // Get total payroll
        $stmt = $pdo->prepare("
            SELECT SUM(ec.basic_salary) as total_salary
            FROM employee_compensation ec
            JOIN employees e ON ec.employee_id = e.id
            WHERE ec.is_active = 1 AND e.employment_status = 'Active'
        ");
        $stmt->execute();
        $total_salary = $stmt->fetch(PDO::FETCH_ASSOC)['total_salary'] ?? 0;
        $total_payroll = '₱' . number_format($total_salary / 1000000, 1) . 'M';

        $data = [
            'total_employees' => (int)$total_employees,
            'total_departments' => (int)$total_departments,
            'avg_attendance' => $avg_attendance,
            'total_payroll' => $total_payroll
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Dashboard metrics retrieved successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error retrieving dashboard metrics: ' . $e->getMessage()
        ]);
    }
}

function getEmployeeReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "WHERE e.employment_status = 'Active'";
        $params = [];

        if ($department_id) {
            $where_clause .= " AND e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        if ($from_date) {
            $where_clause .= " AND e.hire_date >= :from_date";
            $params[':from_date'] = $from_date;
        }

        if ($to_date) {
            $where_clause .= " AND e.hire_date <= :to_date";
            $params[':to_date'] = $to_date;
        }

        $stmt = $pdo->prepare("
            SELECT
                e.id,
                e.employee_id,
                CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as full_name,
                e.email,
                e.phone,
                e.hire_date,
                e.employment_status,
                e.employee_type,
                d.dept_name as department,
                p.position_title as position,
                ec.basic_salary
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            $where_clause
            ORDER BY e.first_name, e.last_name
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'report_type' => 'employee',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'department_id' => $department_id
            ],
            'total_records' => count($employees),
            'employees' => $employees
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Employee report generated successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating employee report: ' . $e->getMessage()
        ]);
    }
}

function getAttendanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "WHERE 1=1";
        $params = [];

        if ($from_date && $to_date) {
            $where_clause .= " AND ar.attendance_date BETWEEN :from_date AND :to_date";
            $params[':from_date'] = $from_date;
            $params[':to_date'] = $to_date;
        } else {
            // Default to last 30 days if no date range specified
            $where_clause .= " AND ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }

        if ($department_id) {
            $where_clause .= " AND e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        // Get summary statistics
        $stmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT e.id) as total_employees,
                COUNT(ar.id) as total_records,
                COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) as present_days,
                COUNT(CASE WHEN ar.status = 'Absent' THEN 1 END) as absent_days,
                (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as avg_attendance_rate
            FROM employees e
            LEFT JOIN attendance_records ar ON e.id = ar.employee_id
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get monthly trends
        $monthly_where = str_replace("WHERE 1=1", "WHERE ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)", $where_clause);
        if ($department_id) {
            $monthly_where .= " AND e.department_id = :department_id";
        }

        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(ar.attendance_date, '%b') as month,
                (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            $monthly_where
            GROUP BY YEAR(ar.attendance_date), MONTH(ar.attendance_date), DATE_FORMAT(ar.attendance_date, '%b')
            ORDER BY ar.attendance_date
        ");

        if ($department_id) {
            $stmt->bindValue(':department_id', $department_id);
        }

        $stmt->execute();
        $monthly_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthly_trends = [];
        foreach ($monthly_results as $result) {
            $monthly_trends[$result['month']] = round($result['attendance_rate'], 1);
        }

        // Get department breakdown
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name,
                (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id
            LEFT JOIN attendance_records ar ON e.id = ar.employee_id
            WHERE ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY d.id, d.dept_name
            ORDER BY attendance_rate DESC
        ");

        $stmt->execute();
        $dept_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $department_breakdown = [];
        foreach ($dept_results as $result) {
            $department_breakdown[$result['dept_name']] = round($result['attendance_rate'], 1);
        }

        $attendance_data = [
            'report_type' => 'attendance',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'department_id' => $department_id
            ],
            'summary' => [
                'total_employees' => (int)$summary['total_employees'],
                'avg_attendance_rate' => round($summary['avg_attendance_rate'], 1),
                'total_working_days' => (int)$summary['total_records'],
                'total_present_days' => (int)$summary['present_days'],
                'total_absent_days' => (int)$summary['absent_days']
            ],
            'monthly_trends' => $monthly_trends,
            'department_breakdown' => $department_breakdown
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Attendance report generated successfully',
            'data' => $attendance_data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating attendance report: ' . $e->getMessage()
        ]);
    }
}

function getPayrollReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "WHERE e.employment_status = 'Active'";
        $params = [];

        if ($department_id) {
            $where_clause .= " AND e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        // Get payroll summary
        $stmt = $pdo->prepare("
            SELECT
                COUNT(e.id) as total_employees,
                SUM(ec.basic_salary) as total_basic_salary,
                AVG(ec.basic_salary) as avg_salary,
                MIN(ec.basic_salary) as min_salary,
                MAX(ec.basic_salary) as max_salary
            FROM employees e
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get department breakdown
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name as department,
                COUNT(e.id) as employee_count,
                SUM(ec.basic_salary) as total_salary,
                AVG(ec.basic_salary) as avg_salary
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            $where_clause
            GROUP BY d.id, d.dept_name
            ORDER BY total_salary DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $department_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'report_type' => 'payroll',
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => $summary,
            'department_breakdown' => $department_breakdown,
            'breakdown_categories' => [
                'Basic Salary' => floatval($summary['total_basic_salary'] ?? 0),
                'Allowances' => floatval($summary['total_basic_salary'] ?? 0) * 0.15, // Mock 15%
                'Overtime' => floatval($summary['total_basic_salary'] ?? 0) * 0.08, // Mock 8%
                'Bonuses' => floatval($summary['total_basic_salary'] ?? 0) * 0.05  // Mock 5%
            ]
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Payroll report generated successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating payroll report: ' . $e->getMessage()
        ]);
    }
}

function getLeaveReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "";
        $params = [];

        if ($from_date && $to_date) {
            $where_clause = "WHERE el.start_date >= :from_date AND el.end_date <= :to_date";
            $params[':from_date'] = $from_date;
            $params[':to_date'] = $to_date;
        }

        if ($department_id) {
            $where_clause .= $where_clause ? " AND" : "WHERE";
            $where_clause .= " e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        // Get leave statistics
        $stmt = $pdo->prepare("
            SELECT
                lt.leave_name as leave_type,
                COUNT(el.id) as total_requests,
                SUM(el.total_days) as total_days,
                AVG(el.total_days) as avg_days_per_request
            FROM employee_leaves el
            LEFT JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN employees e ON el.employee_id = e.id
            $where_clause
            GROUP BY el.leave_type_id, lt.leave_name
            ORDER BY total_requests DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $leave_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get status breakdown
        $stmt = $pdo->prepare("
            SELECT
                el.status,
                COUNT(el.id) as count
            FROM employee_leaves el
            LEFT JOIN employees e ON el.employee_id = e.id
            $where_clause
            GROUP BY el.status
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'report_type' => 'leave',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'department_id' => $department_id
            ],
            'leave_statistics' => $leave_stats,
            'status_breakdown' => $status_breakdown
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Leave report generated successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating leave report: ' . $e->getMessage()
        ]);
    }
}

function getDepartmentReport($pdo, $user_id, $from_date, $to_date, $format) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                d.id,
                d.dept_name as department,
                d.dept_code,
                COUNT(e.id) as employee_count,
                AVG(ec.basic_salary) as avg_salary,
                SUM(ec.basic_salary) as total_salary,
                MIN(ec.basic_salary) as min_salary,
                MAX(ec.basic_salary) as max_salary
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'Active'
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            GROUP BY d.id, d.dept_name, d.dept_code
            ORDER BY employee_count DESC
        ");

        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add real attendance rates
        foreach ($departments as &$dept) {
            // Get real attendance rate for this department
            $stmt_att = $pdo->prepare("
                SELECT
                    (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
                FROM employees e
                LEFT JOIN attendance_records ar ON e.id = ar.employee_id
                WHERE e.department_id = :dept_id
                AND ar.attendance_date IS NOT NULL
            ");
            $stmt_att->bindParam(':dept_id', $dept['id']);
            $stmt_att->execute();
            $attendance_result = $stmt_att->fetch(PDO::FETCH_ASSOC);

            $dept['attendance_rate'] = round($attendance_result['attendance_rate'] ?? 0, 1);
            $dept['performance_rating'] = $dept['attendance_rate'] >= 95 ? 'Excellent' :
                                        ($dept['attendance_rate'] >= 90 ? 'Good' : 'Needs Improvement');
        }

        $data = [
            'report_type' => 'department',
            'generated_at' => date('Y-m-d H:i:s'),
            'total_departments' => count($departments),
            'departments' => $departments
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Department report generated successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating department report: ' . $e->getMessage()
        ]);
    }
}

function getPerformanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "WHERE pe.status = 'Completed'";
        $params = [];

        if ($from_date && $to_date) {
            $where_clause .= " AND pe.evaluation_period_start >= :from_date AND pe.evaluation_period_end <= :to_date";
            $params[':from_date'] = $from_date;
            $params[':to_date'] = $to_date;
        }

        if ($department_id) {
            $where_clause .= " AND e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        // Get performance summary
        $stmt = $pdo->prepare("
            SELECT
                COUNT(pe.id) as total_evaluations,
                AVG(pe.overall_rating) as avg_rating,
                COUNT(CASE WHEN pe.status = 'Completed' THEN 1 END) as completed_evaluations,
                COUNT(CASE WHEN pe.status = 'Draft' THEN 1 END) as pending_evaluations
            FROM performance_evaluations pe
            LEFT JOIN employees e ON pe.employee_id = e.id
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get rating distribution
        $stmt = $pdo->prepare("
            SELECT
                CASE
                    WHEN pe.overall_rating = 5.0 THEN 'Excellent (5.0)'
                    WHEN pe.overall_rating >= 4.0 THEN 'Very Good (4.0-4.9)'
                    WHEN pe.overall_rating >= 3.0 THEN 'Good (3.0-3.9)'
                    WHEN pe.overall_rating >= 2.0 THEN 'Needs Improvement (2.0-2.9)'
                    ELSE 'Poor (1.0-1.9)'
                END as rating_category,
                COUNT(*) as count
            FROM performance_evaluations pe
            LEFT JOIN employees e ON pe.employee_id = e.id
            $where_clause
            GROUP BY rating_category
            ORDER BY pe.overall_rating DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $rating_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rating_distribution = [];
        foreach ($rating_results as $result) {
            $rating_distribution[$result['rating_category']] = (int)$result['count'];
        }

        // Get department performance
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name,
                AVG(pe.overall_rating) as avg_rating
            FROM performance_evaluations pe
            LEFT JOIN employees e ON pe.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            $where_clause
            GROUP BY d.id, d.dept_name
            ORDER BY avg_rating DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $dept_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $department_performance = [];
        foreach ($dept_results as $result) {
            $department_performance[$result['dept_name']] = round($result['avg_rating'], 1);
        }

        $performance_data = [
            'report_type' => 'performance',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'department_id' => $department_id
            ],
            'summary' => [
                'total_evaluations' => (int)$summary['total_evaluations'],
                'avg_rating' => round($summary['avg_rating'], 1),
                'completed_evaluations' => (int)$summary['completed_evaluations'],
                'pending_evaluations' => (int)$summary['pending_evaluations']
            ],
            'rating_distribution' => $rating_distribution,
            'department_performance' => $department_performance
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Performance report generated successfully',
            'data' => $performance_data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating performance report: ' . $e->getMessage()
        ]);
    }
}

function getBenefitsReport($pdo, $user_id, $from_date, $to_date, $department_id, $format) {
    try {
        $where_clause = "";
        $params = [];

        if ($department_id) {
            $where_clause = "WHERE e.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        // Get insurance summary
        $stmt = $pdo->prepare("
            SELECT
                ip.plan_name,
                COUNT(ei.id) as enrolled_count,
                SUM(ei.employee_premium) as total_employee_contributions,
                SUM(ei.employer_premium) as total_employer_contributions,
                AVG(ei.employee_premium) as avg_employee_contribution,
                AVG(ei.employer_premium) as avg_employer_contribution
            FROM employee_insurance ei
            LEFT JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
            LEFT JOIN employees e ON ei.employee_id = e.id
            $where_clause
            GROUP BY ip.id, ip.plan_name
            ORDER BY enrolled_count DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $insurance_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get dependents covered by HMO
        $stmt = $pdo->prepare("
            SELECT
                COUNT(ed.id) as total_dependents,
                SUM(CASE WHEN ed.is_hmo_covered = 1 THEN 1 ELSE 0 END) as hmo_covered_dependents,
                AVG(CASE WHEN ed.is_hmo_covered = 1 THEN 1 ELSE 0 END) * 100 as hmo_coverage_percentage
            FROM employee_dependents ed
            LEFT JOIN employees e ON ed.employee_id = e.id
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $dependents_summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get benefits utilization by department
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name as department,
                COUNT(DISTINCT ei.employee_id) as employees_with_benefits,
                COUNT(DISTINCT e.id) as total_employees,
                COUNT(DISTINCT ei.employee_id) / COUNT(DISTINCT e.id) * 100 as utilization_rate,
                SUM(ei.employee_premium + ei.employer_premium) as total_benefit_cost
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'Active'
            LEFT JOIN employee_insurance ei ON e.id = ei.employee_id
            " . ($department_id ? "WHERE d.id = :department_id" : "") . "
            GROUP BY d.id, d.dept_name
            ORDER BY utilization_rate DESC
        ");

        if ($department_id) {
            $stmt->bindParam(':department_id', $department_id);
        }

        $stmt->execute();
        $department_utilization = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get top benefit plans
        $benefit_plans_distribution = [];
        foreach ($insurance_summary as $plan) {
            $benefit_plans_distribution[$plan['plan_name']] = (int)$plan['enrolled_count'];
        }

        // Calculate overall statistics
        $total_enrolled = array_sum($benefit_plans_distribution);
        $total_cost = array_sum(array_column($insurance_summary, 'total_employee_contributions')) +
                     array_sum(array_column($insurance_summary, 'total_employer_contributions'));

        $data = [
            'report_type' => 'benefits',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'department_id' => $department_id
            ],
            'summary' => [
                'total_enrolled_employees' => $total_enrolled,
                'total_benefit_cost' => $total_cost,
                'avg_cost_per_employee' => $total_enrolled > 0 ? $total_cost / $total_enrolled : 0,
                'total_dependents' => (int)$dependents_summary['total_dependents'],
                'hmo_covered_dependents' => (int)$dependents_summary['hmo_covered_dependents'],
                'dependent_hmo_coverage_rate' => round((float)$dependents_summary['hmo_coverage_percentage'], 2)
            ],
            'insurance_plans' => $insurance_summary,
            'benefit_plans_distribution' => $benefit_plans_distribution,
            'department_utilization' => $department_utilization,
            'cost_breakdown' => [
                'employee_contributions' => array_sum(array_column($insurance_summary, 'total_employee_contributions')),
                'employer_contributions' => array_sum(array_column($insurance_summary, 'total_employer_contributions'))
            ]
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Benefits report generated successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating benefits report: ' . $e->getMessage()
        ]);
    }
}

function getChartsData($pdo, $user_id) {
    try {
        // Get real attendance trends - show all available data
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(attendance_date, '%b') as month,
                (COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(*)) as attendance_rate
            FROM attendance_records
            WHERE attendance_date IS NOT NULL
            GROUP BY YEAR(attendance_date), MONTH(attendance_date), DATE_FORMAT(attendance_date, '%b')
            ORDER BY attendance_date
        ");
        $stmt->execute();
        $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attendance_trends = [];
        foreach ($attendance_results as $result) {
            $attendance_trends[$result['month']] = round($result['attendance_rate'], 1);
        }

        // Fill missing months with 0 for months with no data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($months as $month) {
            if (!isset($attendance_trends[$month])) {
                $attendance_trends[$month] = 0;
            }
        }

        // Get leave statistics
        $stmt = $pdo->prepare("
            SELECT
                lt.leave_name as leave_type,
                COUNT(el.id) as count
            FROM employee_leaves el
            LEFT JOIN leave_types lt ON el.leave_type_id = lt.id
            WHERE el.status = 'Approved'
            GROUP BY el.leave_type_id, lt.leave_name
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $leave_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert to chart format
        $leave_chart_data = [];
        foreach ($leave_stats as $stat) {
            $leave_chart_data[$stat['leave_type']] = intval($stat['count']);
        }

        // Get payroll breakdown
        $stmt = $pdo->prepare("
            SELECT SUM(ec.basic_salary) as total_basic_salary
            FROM employee_compensation ec
            JOIN employees e ON ec.employee_id = e.id
            WHERE ec.is_active = 1 AND e.employment_status = 'Active'
        ");
        $stmt->execute();
        $total_salary = $stmt->fetch(PDO::FETCH_ASSOC)['total_basic_salary'] ?? 0;

        $payroll_breakdown = [
            'Basic Salary' => floatval($total_salary),
            'Allowances' => floatval($total_salary) * 0.15,
            'Overtime' => floatval($total_salary) * 0.08,
            'Bonuses' => floatval($total_salary) * 0.05
        ];

        // Get real department attendance data - show all available data
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name,
                (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id
            LEFT JOIN attendance_records ar ON e.id = ar.employee_id
            WHERE ar.attendance_date IS NOT NULL
            GROUP BY d.id, d.dept_name
            HAVING COUNT(ar.id) > 0
            ORDER BY d.dept_name
        ");
        $stmt->execute();
        $dept_attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $department_attendance = [];
        foreach ($dept_attendance_results as $result) {
            $department_attendance[$result['dept_name']] = round($result['attendance_rate'], 1);
        }

        $data = [
            'attendance_trends' => $attendance_trends,
            'leave_statistics' => $leave_chart_data,
            'payroll_breakdown' => $payroll_breakdown,
            'department_attendance' => $department_attendance
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Charts data retrieved successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error retrieving charts data: ' . $e->getMessage()
        ]);
    }
}

function generateCustomReport($pdo, $user_id, $input) {
    try {
        $report_type = $input['report_type'] ?? '';
        $from_date = $input['from_date'] ?? '';
        $to_date = $input['to_date'] ?? '';
        $department_id = $input['department_id'] ?? '';
        $format = $input['format'] ?? 'json';

        if (!$report_type) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Report type is required'
            ]);
            return;
        }

        // Generate the appropriate report based on type
        switch ($report_type) {
            case 'employee':
                getEmployeeReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            case 'attendance':
                getAttendanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            case 'payroll':
                getPayrollReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            case 'leave':
                getLeaveReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            case 'department':
                getDepartmentReport($pdo, $user_id, $from_date, $to_date, $format);
                break;
            case 'performance':
                getPerformanceReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            case 'benefits':
                getBenefitsReport($pdo, $user_id, $from_date, $to_date, $department_id, $format);
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid report type'
                ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error generating custom report: ' . $e->getMessage()
        ]);
    }
}

function exportReport($pdo, $user_id, $input) {
    try {
        $report_data = $input['report_data'] ?? [];
        $format = $input['format'] ?? 'csv';
        $filename = $input['filename'] ?? 'report_' . date('Y-m-d_H-i-s');

        if (empty($report_data)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Report data is required'
            ]);
            return;
        }

        $data = [
            'export_format' => $format,
            'filename' => $filename . '.' . $format,
            'download_url' => '/api/reports/download/' . $filename . '.' . $format,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Report export prepared successfully',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error exporting report: ' . $e->getMessage()
        ]);
    }
}
?>