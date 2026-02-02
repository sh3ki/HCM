<?php
// reports_pdf.php - API endpoint for PDF report generation

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/PDFGenerator.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
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
    if ($method === 'GET') {
        handlePDFGeneration($pdo, $user_id);
    } else {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}

function handlePDFGeneration($pdo, $user_id) {
    $type = $_GET['type'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $department_id = $_GET['department_id'] ?? '';

    if (empty($type)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Report type is required'
        ]);
        return;
    }

    switch ($type) {
        case 'employee':
            generateEmployeePDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        case 'attendance':
            generateAttendancePDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        case 'payroll':
            generatePayrollPDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        case 'leave':
            generateLeavePDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        case 'department':
            generateDepartmentPDF($pdo, $user_id, $from_date, $to_date);
            break;
        case 'performance':
            generatePerformancePDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        case 'benefits':
            generateBenefitsPDF($pdo, $user_id, $from_date, $to_date, $department_id);
            break;
        default:
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid report type'
            ]);
    }
}

function generateEmployeePDF($pdo, $user_id, $from_date, $to_date, $department_id) {
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

        // Generate PDF
        $pdf = new PDFGenerator('Employee Report');
        $pdf->addReportHeader('employee', date('Y-m-d H:i:s'), [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'department_id' => $department_id
        ]);

        $pdf->addSummaryStats([
            'total_employees' => count($employees),
            'date_range' => $from_date && $to_date ? "$from_date to $to_date" : 'All time',
            'department_filter' => $department_id ? 'Applied' : 'None'
        ]);

        $pdf->addEmployeeTable($employees);

        $filename = 'employee_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating employee PDF: ' . $e->getMessage()
        ]);
    }
}

function generateDepartmentPDF($pdo, $user_id, $from_date, $to_date) {
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

        // Add attendance rates
        foreach ($departments as &$dept) {
            $stmt_att = $pdo->prepare("
                SELECT
                    (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
                FROM employees e
                LEFT JOIN attendance_records ar ON e.id = ar.employee_id
                WHERE e.department_id = :dept_id
                AND ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt_att->bindParam(':dept_id', $dept['id']);
            $stmt_att->execute();
            $attendance_result = $stmt_att->fetch(PDO::FETCH_ASSOC);

            $dept['attendance_rate'] = round($attendance_result['attendance_rate'] ?? 0, 1);
        }

        // Generate PDF
        $pdf = new PDFGenerator('Department Report');
        $pdf->addReportHeader('department', date('Y-m-d H:i:s'));

        $pdf->addSummaryStats([
            'total_departments' => count($departments),
            'total_employees' => array_sum(array_column($departments, 'employee_count')),
            'avg_department_size' => count($departments) > 0 ? round(array_sum(array_column($departments, 'employee_count')) / count($departments), 1) : 0
        ]);

        $pdf->addDepartmentTable($departments);

        $filename = 'department_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating department PDF: ' . $e->getMessage()
        ]);
    }
}

function generateAttendancePDF($pdo, $user_id, $from_date, $to_date, $department_id) {
    try {
        $where_clause = "WHERE 1=1";
        $params = [];

        if ($from_date && $to_date) {
            $where_clause .= " AND ar.attendance_date BETWEEN :from_date AND :to_date";
            $params[':from_date'] = $from_date;
            $params[':to_date'] = $to_date;
        } else {
            // If no date range specified, get all available data (not just last 30 days)
            // This ensures we show data even if it's from previous periods
            $where_clause .= " AND ar.attendance_date IS NOT NULL";
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

        // Generate PDF
        $pdf = new PDFGenerator('Attendance Report');
        $pdf->addReportHeader('attendance', date('Y-m-d H:i:s'), [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'department_id' => $department_id
        ]);

        $pdf->addSummaryStats([
            'total_employees' => (int)$summary['total_employees'],
            'avg_attendance_rate' => round($summary['avg_attendance_rate'], 1) . '%',
            'total_working_days' => (int)$summary['total_records'],
            'total_present_days' => (int)$summary['present_days'],
            'total_absent_days' => (int)$summary['absent_days']
        ]);

        // Add department breakdown table
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name as department,
                COUNT(DISTINCT e.id) as employees,
                (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(ar.id)) as attendance_rate
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id
            LEFT JOIN attendance_records ar ON e.id = ar.employee_id
            WHERE ar.attendance_date IS NOT NULL
            GROUP BY d.id, d.dept_name
            HAVING COUNT(ar.id) > 0
            ORDER BY attendance_rate DESC
        ");

        $stmt->execute();
        $dept_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($dept_breakdown)) {
            $html = '<h2>Department Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($dept_breakdown as $dept) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($dept['department']) . '</td>
                    <td>' . $dept['employees'] . '</td>
                    <td>' . round($dept['attendance_rate'], 1) . '%</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating attendance PDF: ' . $e->getMessage()
        ]);
    }
}

function generatePayrollPDF($pdo, $user_id, $from_date, $to_date, $department_id) {
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

        // Generate PDF
        $pdf = new PDFGenerator('Payroll Report');
        $pdf->addReportHeader('payroll', date('Y-m-d H:i:s'), [
            'department_id' => $department_id
        ]);

        $pdf->addSummaryStats([
            'total_employees' => (int)$summary['total_employees'],
            'total_basic_salary' => '₱' . number_format($summary['total_basic_salary'] ?? 0),
            'avg_salary' => '₱' . number_format($summary['avg_salary'] ?? 0),
            'min_salary' => '₱' . number_format($summary['min_salary'] ?? 0),
            'max_salary' => '₱' . number_format($summary['max_salary'] ?? 0)
        ]);

        // Add department table
        if (!empty($department_breakdown)) {
            $html = '<h2>Department Payroll Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Total Salary</th>
                        <th>Average Salary</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($department_breakdown as $dept) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($dept['department']) . '</td>
                    <td>' . $dept['employee_count'] . '</td>
                    <td>₱' . number_format($dept['total_salary'] ?? 0) . '</td>
                    <td>₱' . number_format($dept['avg_salary'] ?? 0) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        $filename = 'payroll_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating payroll PDF: ' . $e->getMessage()
        ]);
    }
}

function generateLeavePDF($pdo, $user_id, $from_date, $to_date, $department_id) {
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

        // Generate PDF
        $pdf = new PDFGenerator('Leave Report');
        $pdf->addReportHeader('leave', date('Y-m-d H:i:s'), [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'department_id' => $department_id
        ]);

        $totalRequests = array_sum(array_column($leave_stats, 'total_requests'));
        $totalDays = array_sum(array_column($leave_stats, 'total_days'));

        $pdf->addSummaryStats([
            'total_requests' => $totalRequests,
            'total_leave_days' => $totalDays,
            'avg_leave_per_request' => $totalRequests > 0 ? round($totalDays / $totalRequests, 1) . ' days' : '0 days'
        ]);

        // Add leave statistics table
        if (!empty($leave_stats)) {
            $html = '<h2>Leave Type Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Total Requests</th>
                        <th>Total Days</th>
                        <th>Avg Days per Request</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($leave_stats as $stat) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($stat['leave_type']) . '</td>
                    <td>' . $stat['total_requests'] . '</td>
                    <td>' . $stat['total_days'] . '</td>
                    <td>' . round($stat['avg_days_per_request'], 1) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        $filename = 'leave_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating leave PDF: ' . $e->getMessage()
        ]);
    }
}

function generatePerformancePDF($pdo, $user_id, $from_date, $to_date, $department_id) {
    try {
        $where_clause = "WHERE pe.overall_rating IS NOT NULL";
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
                COUNT(CASE WHEN pe.status = 'Draft' THEN 1 END) as pending_evaluations,
                MIN(pe.overall_rating) as min_rating,
                MAX(pe.overall_rating) as max_rating
            FROM performance_evaluations pe
            LEFT JOIN employees e ON pe.employee_id = e.id
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get individual performance evaluations
        $stmt = $pdo->prepare("
            SELECT
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                d.dept_name as department,
                pe.overall_rating,
                pe.goals_achievement,
                pe.teamwork,
                pe.communication,
                pe.technical_skills,
                pe.leadership,
                pe.punctuality,
                pe.status,
                pe.evaluation_period_start,
                pe.evaluation_period_end
            FROM performance_evaluations pe
            LEFT JOIN employees e ON pe.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            $where_clause
            ORDER BY pe.overall_rating DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate PDF
        $pdf = new PDFGenerator('Performance Report');
        $pdf->addReportHeader('performance', date('Y-m-d H:i:s'), [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'department_id' => $department_id
        ]);

        $pdf->addSummaryStats([
            'total_evaluations' => (int)$summary['total_evaluations'],
            'avg_rating' => round($summary['avg_rating'], 2) . '/5.0',
            'completed_evaluations' => (int)$summary['completed_evaluations'],
            'pending_evaluations' => (int)$summary['pending_evaluations'],
            'rating_range' => round($summary['min_rating'], 2) . ' - ' . round($summary['max_rating'], 2)
        ]);

        // Add performance evaluations table
        if (!empty($evaluations)) {
            $html = '<h2>Individual Performance Evaluations</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Overall Rating</th>
                        <th>Goals</th>
                        <th>Teamwork</th>
                        <th>Communication</th>
                        <th>Technical</th>
                        <th>Status</th>
                        <th>Period</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($evaluations as $eval) {
                $ratingClass = $eval['overall_rating'] >= 4.0 ? 'excellent' :
                              ($eval['overall_rating'] >= 3.0 ? 'good' : 'needs-improvement');

                $html .= '<tr>
                    <td>' . htmlspecialchars($eval['employee_name']) . '</td>
                    <td>' . htmlspecialchars($eval['department']) . '</td>
                    <td class="' . $ratingClass . '">' . $eval['overall_rating'] . '</td>
                    <td>' . ($eval['goals_achievement'] ?? 'N/A') . '</td>
                    <td>' . ($eval['teamwork'] ?? 'N/A') . '</td>
                    <td>' . ($eval['communication'] ?? 'N/A') . '</td>
                    <td>' . ($eval['technical_skills'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($eval['status']) . '</td>
                    <td>' . $eval['evaluation_period_start'] . ' to ' . $eval['evaluation_period_end'] . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        $filename = 'performance_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating performance PDF: ' . $e->getMessage()
        ]);
    }
}

function generateBenefitsPDF($pdo, $user_id, $from_date, $to_date, $department_id) {
    try {
        $where_clause = "WHERE ei.status = 'Active'";
        $params = [];

        if ($department_id) {
            $where_clause .= " AND e.department_id = :department_id";
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
                AVG(ei.employer_premium) as avg_employer_contribution,
                SUM(ei.dependents_count) as total_dependents
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

        // Get overall statistics
        $stmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT ei.employee_id) as total_enrolled_employees,
                SUM(ei.employee_premium + ei.employer_premium) as total_cost,
                SUM(ei.dependents_count) as total_dependents_covered,
                AVG(ei.employee_premium + ei.employer_premium) as avg_cost_per_employee
            FROM employee_insurance ei
            LEFT JOIN employees e ON ei.employee_id = e.id
            $where_clause
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get department breakdown
        $stmt = $pdo->prepare("
            SELECT
                d.dept_name as department,
                COUNT(DISTINCT ei.employee_id) as employees_with_benefits,
                COUNT(DISTINCT e.id) as total_employees,
                (COUNT(DISTINCT ei.employee_id) * 100.0 / COUNT(DISTINCT e.id)) as utilization_rate,
                SUM(ei.employee_premium + ei.employer_premium) as total_benefit_cost
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'Active'
            LEFT JOIN employee_insurance ei ON e.id = ei.employee_id AND ei.status = 'Active'
            " . ($department_id ? "WHERE d.id = :dept_filter" : "") . "
            GROUP BY d.id, d.dept_name
            HAVING COUNT(DISTINCT e.id) > 0
            ORDER BY utilization_rate DESC
        ");

        if ($department_id) {
            $stmt->bindParam(':dept_filter', $department_id);
        }

        $stmt->execute();
        $department_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate PDF
        $pdf = new PDFGenerator('Benefits Report');
        $pdf->addReportHeader('benefits', date('Y-m-d H:i:s'), [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'department_id' => $department_id
        ]);

        $pdf->addSummaryStats([
            'total_enrolled_employees' => (int)$overall_stats['total_enrolled_employees'],
            'total_benefit_cost' => '₱' . number_format($overall_stats['total_cost']),
            'avg_cost_per_employee' => '₱' . number_format($overall_stats['avg_cost_per_employee']),
            'total_dependents_covered' => (int)$overall_stats['total_dependents_covered'],
            'active_insurance_plans' => count($insurance_summary)
        ]);

        // Add insurance plans table
        if (!empty($insurance_summary)) {
            $html = '<h2>Insurance Plans Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plan Name</th>
                        <th>Enrolled</th>
                        <th>Employee Premium</th>
                        <th>Employer Premium</th>
                        <th>Total Cost</th>
                        <th>Dependents</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($insurance_summary as $plan) {
                $totalCost = $plan['total_employee_contributions'] + $plan['total_employer_contributions'];
                $html .= '<tr>
                    <td>' . htmlspecialchars($plan['plan_name']) . '</td>
                    <td>' . $plan['enrolled_count'] . '</td>
                    <td>₱' . number_format($plan['total_employee_contributions']) . '</td>
                    <td>₱' . number_format($plan['total_employer_contributions']) . '</td>
                    <td>₱' . number_format($totalCost) . '</td>
                    <td>' . $plan['total_dependents'] . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        // Add department utilization table
        if (!empty($department_breakdown)) {
            $html = '<h2>Department Benefits Utilization</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees with Benefits</th>
                        <th>Total Employees</th>
                        <th>Utilization Rate</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($department_breakdown as $dept) {
                $utilizationClass = $dept['utilization_rate'] >= 80 ? 'excellent' :
                                   ($dept['utilization_rate'] >= 60 ? 'good' : 'needs-improvement');

                $html .= '<tr>
                    <td>' . htmlspecialchars($dept['department']) . '</td>
                    <td>' . $dept['employees_with_benefits'] . '</td>
                    <td>' . $dept['total_employees'] . '</td>
                    <td class="' . $utilizationClass . '">' . round($dept['utilization_rate'], 1) . '%</td>
                    <td>₱' . number_format($dept['total_benefit_cost'] ?? 0) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
            $pdf->setHTML($pdf->html . $html);
        }

        $filename = 'benefits_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->generatePDF($filename);

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error generating benefits PDF: ' . $e->getMessage()
        ]);
    }
}
?>