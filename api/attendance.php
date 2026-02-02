<?php
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
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'today':
                        handleGetTodayAttendance($pdo, $user_id);
                        break;
                    case 'status':
                        handleGetAttendanceStatus($pdo, $user_id);
                        break;
                    case 'history':
                        handleGetAttendanceHistory($pdo, $user_id);
                        break;
                    case 'summary':
                        handleGetAttendanceSummary($pdo);
                        break;
                    case 'details':
                        if (isset($_GET['id'])) {
                            handleGetAttendanceDetails($pdo, $_GET['id']);
                        } else {
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Attendance ID required',
                                'code' => 'MISSING_ID'
                            ]);
                        }
                        break;
                    default:
                        handleGetAttendanceRecords($pdo);
                        break;
                }
            } else {
                handleGetAttendanceRecords($pdo);
            }
            break;

        case 'POST':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'clock-in':
                        handleClockIn($pdo, $user_id);
                        break;
                    case 'clock-out':
                        handleClockOut($pdo, $user_id);
                        break;
                    case 'break-start':
                        handleBreakStart($pdo, $user_id);
                        break;
                    case 'break-end':
                        handleBreakEnd($pdo, $user_id);
                        break;
                    case 'add-note':
                        handleAddNote($pdo, $user_id);
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Invalid action',
                            'code' => 'INVALID_ACTION'
                        ]);
                        break;
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Action required',
                    'code' => 'ACTION_REQUIRED'
                ]);
            }
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                handleUpdateAttendance($pdo, $_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Attendance record ID required for update',
                    'code' => 'MISSING_ID'
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed',
                'code' => 'METHOD_NOT_ALLOWED'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Attendance API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SERVER_ERROR'
    ]);
}

function handleGetAttendanceStatus($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');

        // Get today's attendance record
        $stmt = $pdo->prepare("
            SELECT * FROM attendance_records
            WHERE employee_id = ? AND attendance_date = ?
        ");
        $stmt->execute([$employee_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        $status = [
            'is_clocked_in' => false,
            'is_on_break' => false,
            'can_clock_in' => true,
            'can_clock_out' => false,
            'can_start_break' => false,
            'can_end_break' => false,
            'time_in' => null,
            'time_out' => null,
            'break_start' => null,
            'break_end' => null,
            'total_hours' => 0
        ];

        if ($attendance) {
            $status['time_in'] = $attendance['time_in'];
            $status['time_out'] = $attendance['time_out'];
            $status['break_start'] = $attendance['break_start'];
            $status['break_end'] = $attendance['break_end'];
            $status['total_hours'] = $attendance['total_hours'];

            if ($attendance['time_in'] && !$attendance['time_out']) {
                $status['is_clocked_in'] = true;
                $status['can_clock_in'] = false;
                $status['can_clock_out'] = true;

                if (!$attendance['break_start']) {
                    $status['can_start_break'] = true;
                } elseif ($attendance['break_start'] && !$attendance['break_end']) {
                    $status['is_on_break'] = true;
                    $status['can_end_break'] = true;
                    $status['can_clock_out'] = false;
                } elseif ($attendance['break_start'] && $attendance['break_end']) {
                    $status['can_start_break'] = true; // Can take another break
                }
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $status,
            'message' => 'Attendance status retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Attendance Status Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetTodayAttendance($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');

        $stmt = $pdo->prepare("
            SELECT ar.*,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   e.employee_id as employee_code
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            WHERE ar.employee_id = ? AND ar.attendance_date = ?
        ");
        $stmt->execute([$employee_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $attendance,
            'message' => 'Today\'s attendance retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Today Attendance Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetAttendanceHistory($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        // Get attendance records for the past 30 days
        $stmt = $pdo->prepare("
            SELECT ar.*,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   e.employee_id as employee_code
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            WHERE ar.employee_id = ?
            AND ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ar.attendance_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$employee_id, $limit, $offset]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM attendance_records ar
            WHERE ar.employee_id = ?
            AND ar.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $countStmt->execute([$employee_id]);
        $totalCount = $countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => [
                'records' => $records,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$totalCount,
                    'totalPages' => ceil($totalCount / $limit)
                ]
            ],
            'message' => 'Attendance history retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Attendance History Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleClockIn($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');
        $current_time = date('H:i:s');

        // Check if already clocked in today
        $stmt = $pdo->prepare("
            SELECT id FROM attendance_records
            WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL
        ");
        $stmt->execute([$employee_id, $today]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Already clocked in today',
                'code' => 'ALREADY_CLOCKED_IN'
            ]);
            return;
        }

        // Calculate if late (assuming 8:00 AM is standard time)
        $standard_time = '08:00:00';
        $late_minutes = 0;
        if ($current_time > $standard_time) {
            $datetime1 = new DateTime($standard_time);
            $datetime2 = new DateTime($current_time);
            $interval = $datetime1->diff($datetime2);
            $late_minutes = ($interval->h * 60) + $interval->i;
        }

        $status = $late_minutes > 0 ? 'Late' : 'Present';

        // Insert or update attendance record
        $stmt = $pdo->prepare("
            INSERT INTO attendance_records
            (employee_id, attendance_date, time_in, late_minutes, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
            time_in = VALUES(time_in),
            late_minutes = VALUES(late_minutes),
            status = VALUES(status),
            updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$employee_id, $today, $current_time, $late_minutes, $status]);

        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'date' => $today,
                'time_in' => $current_time,
                'late_minutes' => $late_minutes,
                'status' => $status
            ],
            'message' => 'Clocked in successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Clock In Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleClockOut($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');
        $current_time = date('H:i:s');

        // Check if clocked in and not already clocked out
        $stmt = $pdo->prepare("
            SELECT * FROM attendance_records
            WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL
        ");
        $stmt->execute([$employee_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Must clock in first',
                'code' => 'NOT_CLOCKED_IN'
            ]);
            return;
        }

        if ($attendance['time_out']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Already clocked out today',
                'code' => 'ALREADY_CLOCKED_OUT'
            ]);
            return;
        }

        // Check if on break
        if ($attendance['break_start'] && !$attendance['break_end']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Must end break before clocking out',
                'code' => 'ON_BREAK'
            ]);
            return;
        }

        // Calculate total hours worked
        $time_in = new DateTime($attendance['time_in']);
        $time_out = new DateTime($current_time);
        $total_minutes = $time_in->diff($time_out)->h * 60 + $time_in->diff($time_out)->i;

        // Subtract break time if taken
        if ($attendance['break_start'] && $attendance['break_end']) {
            $break_start = new DateTime($attendance['break_start']);
            $break_end = new DateTime($attendance['break_end']);
            $break_minutes = $break_start->diff($break_end)->h * 60 + $break_start->diff($break_end)->i;
            $total_minutes -= $break_minutes;
        }

        $total_hours = $total_minutes / 60;
        $regular_hours = min($total_hours, 8); // Assuming 8 hours is regular
        $overtime_hours = max(0, $total_hours - 8);

        // Update attendance record
        $stmt = $pdo->prepare("
            UPDATE attendance_records
            SET time_out = ?,
                total_hours = ?,
                regular_hours = ?,
                overtime_hours = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$current_time, $total_hours, $regular_hours, $overtime_hours, $attendance['id']]);

        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'date' => $today,
                'time_out' => $current_time,
                'total_hours' => round($total_hours, 2),
                'regular_hours' => round($regular_hours, 2),
                'overtime_hours' => round($overtime_hours, 2)
            ],
            'message' => 'Clocked out successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Clock Out Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleBreakStart($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');
        $current_time = date('H:i:s');

        // Check if clocked in and not on break
        $stmt = $pdo->prepare("
            SELECT * FROM attendance_records
            WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL AND time_out IS NULL
        ");
        $stmt->execute([$employee_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Must clock in first',
                'code' => 'NOT_CLOCKED_IN'
            ]);
            return;
        }

        if ($attendance['break_start'] && !$attendance['break_end']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Already on break',
                'code' => 'ALREADY_ON_BREAK'
            ]);
            return;
        }

        // Update attendance record with break start time
        $stmt = $pdo->prepare("
            UPDATE attendance_records
            SET break_start = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$current_time, $attendance['id']]);

        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'date' => $today,
                'break_start' => $current_time
            ],
            'message' => 'Break started successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Break Start Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleBreakEnd($pdo, $user_id) {
    try {
        // Get employee ID from user ID
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee record not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        $employee_id = $employee['id'];
        $today = date('Y-m-d');
        $current_time = date('H:i:s');

        // Check if on break
        $stmt = $pdo->prepare("
            SELECT * FROM attendance_records
            WHERE employee_id = ? AND attendance_date = ? AND break_start IS NOT NULL AND break_end IS NULL
        ");
        $stmt->execute([$employee_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Not currently on break',
                'code' => 'NOT_ON_BREAK'
            ]);
            return;
        }

        // Update attendance record with break end time
        $stmt = $pdo->prepare("
            UPDATE attendance_records
            SET break_end = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$current_time, $attendance['id']]);

        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'date' => $today,
                'break_end' => $current_time
            ],
            'message' => 'Break ended successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Break End Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetAttendanceRecords($pdo) {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $employee_id = $_GET['employee_id'] ?? '';

        // Build WHERE clause
        $whereConditions = ['ar.attendance_date BETWEEN ? AND ?'];
        $params = [$date_from, $date_to];

        if (!empty($employee_id)) {
            $whereConditions[] = "ar.employee_id = ?";
            $params[] = $employee_id;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            {$whereClause}
        ");
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get attendance records
        $stmt = $pdo->prepare("
            SELECT
                ar.*,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id as employee_code,
                d.dept_name as department
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            {$whereClause}
            ORDER BY ar.attendance_date DESC, ar.time_in DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'records' => $records,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$totalCount,
                    'totalPages' => ceil($totalCount / $limit)
                ]
            ],
            'message' => 'Attendance records retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Attendance Records Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetAttendanceSummary($pdo) {
    try {
        $today = '2025-09-16'; // For testing - normally would be: date('Y-m-d');

        // Get all employees count
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees WHERE employment_status = 'Active'");
        $stmt->execute();
        $totalEmployees = $stmt->fetchColumn();

        // Get today's attendance summary
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_records,
                SUM(CASE WHEN status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_today,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_arrivals,
                SUM(CASE WHEN time_out IS NOT NULL AND total_hours < 7 THEN 1 ELSE 0 END) as early_departures
            FROM attendance_records
            WHERE attendance_date = ?
        ");
        $stmt->execute([$today]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_employees' => (int)$totalEmployees,
                'present_today' => (int)$summary['present_today'],
                'absent_today' => (int)($totalEmployees - $summary['total_records']), // Those who didn't clock in
                'late_arrivals' => (int)$summary['late_arrivals'],
                'early_departures' => (int)$summary['early_departures']
            ],
            'message' => 'Attendance summary retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Attendance Summary Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetAttendanceDetails($pdo, $attendanceId) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                ar.*,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id as employee_code,
                d.dept_name as department
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE ar.id = ?
        ");
        $stmt->execute([$attendanceId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Attendance record not found',
                'code' => 'RECORD_NOT_FOUND'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $record,
            'message' => 'Attendance details retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Attendance Details Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleAddNote($pdo, $user_id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid JSON input',
                'code' => 'INVALID_INPUT'
            ]);
            return;
        }

        $attendance_id = $input['attendance_id'] ?? null;
        $note_type = $input['note_type'] ?? 'general';
        $note_content = trim($input['note_content'] ?? '');
        $priority = $input['priority'] ?? 'low';
        $visibility = $input['visibility'] ?? 'admin';

        if (!$attendance_id || !$note_content) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Attendance ID and note content are required',
                'code' => 'MISSING_REQUIRED_FIELDS'
            ]);
            return;
        }

        // Verify attendance record exists
        $stmt = $pdo->prepare("SELECT id FROM attendance_records WHERE id = ?");
        $stmt->execute([$attendance_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Attendance record not found',
                'code' => 'RECORD_NOT_FOUND'
            ]);
            return;
        }

        // Get user information
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $created_by = $user ? $user['username'] : 'Unknown';

        // Append note to existing notes or create new
        $stmt = $pdo->prepare("SELECT notes FROM attendance_records WHERE id = ?");
        $stmt->execute([$attendance_id]);
        $currentNotes = $stmt->fetchColumn();

        $timestamp = date('Y-m-d H:i:s');
        $newNote = "\n\n[{$timestamp}] ({$note_type}) - {$created_by}:\n{$note_content}";

        if ($priority !== 'low') {
            $newNote = "[PRIORITY: " . strtoupper($priority) . "] " . $newNote;
        }

        $updatedNotes = $currentNotes ? $currentNotes . $newNote : ltrim($newNote);

        // Update attendance record with new note
        $stmt = $pdo->prepare("
            UPDATE attendance_records
            SET notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$updatedNotes, $attendance_id]);

        echo json_encode([
            'success' => true,
            'data' => [
                'attendance_id' => $attendance_id,
                'note_added_at' => $timestamp,
                'added_by' => $created_by
            ],
            'message' => 'Note added successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Add Note Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleUpdateAttendance($pdo, $attendanceId) {
    // For admin users to update attendance records
    http_response_code(501);
    echo json_encode([
        'success' => false,
        'error' => 'Update attendance not implemented yet',
        'code' => 'NOT_IMPLEMENTED'
    ]);
}
?>