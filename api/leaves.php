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
            if (isset($_GET['id'])) {
                handleGetLeave($pdo, $_GET['id']);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'types') {
                handleGetLeaveTypes($pdo);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'balance') {
                handleGetLeaveBalance($pdo, $_GET['employee_id'] ?? null);
            } else {
                handleGetLeaves($pdo);
            }
            break;

        case 'POST':
            handleCreateLeave($pdo);
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                if (isset($_GET['action']) && $_GET['action'] === 'approve') {
                    handleApproveLeave($pdo, $_GET['id']);
                } elseif (isset($_GET['action']) && $_GET['action'] === 'reject') {
                    handleRejectLeave($pdo, $_GET['id']);
                } else {
                    handleUpdateLeave($pdo, $_GET['id']);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Leave ID required for update',
                    'code' => 'MISSING_ID'
                ]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                handleDeleteLeave($pdo, $_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Leave ID required for deletion',
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
    error_log("Leave API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SERVER_ERROR'
    ]);
}

function handleGetLeaves($pdo) {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $employee_id = $_GET['employee_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $leave_type = $_GET['leave_type'] ?? '';
        $year = $_GET['year'] ?? '';

        // Build WHERE clause
        $whereConditions = [];
        $params = [];

        if (!empty($employee_id)) {
            $whereConditions[] = "el.employee_id = ?";
            $params[] = $employee_id;
        }

        if (!empty($status)) {
            $whereConditions[] = "el.status = ?";
            $params[] = $status;
        }

        if (!empty($leave_type)) {
            $whereConditions[] = "el.leave_type_id = ?";
            $params[] = $leave_type;
        }

        if (!empty($year)) {
            $whereConditions[] = "YEAR(el.start_date) = ?";
            $params[] = $year;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM employee_leaves el
            INNER JOIN employees e ON el.employee_id = e.id
            INNER JOIN leave_types lt ON el.leave_type_id = lt.id
            {$whereClause}
        ");
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get leaves data
        $stmt = $pdo->prepare("
            SELECT
                el.id,
                el.employee_id,
                el.leave_type_id,
                el.start_date,
                el.end_date,
                el.total_days,
                el.reason,
                el.status,
                el.applied_date,
                el.approved_by,
                el.approved_date,
                el.notes,
                el.created_at,
                el.updated_at,
                e.employee_id as emp_id,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.email as employee_email,
                lt.leave_name,
                lt.leave_code,
                lt.is_paid,
                approver.first_name as approver_first_name,
                approver.last_name as approver_last_name
            FROM employee_leaves el
            INNER JOIN employees e ON el.employee_id = e.id
            INNER JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN employees approver ON el.approved_by = approver.id
            {$whereClause}
            ORDER BY el.applied_date DESC, el.start_date DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get leave types for filter
        $typeStmt = $pdo->prepare("SELECT id, leave_name, leave_code FROM leave_types WHERE is_active = 1 ORDER BY leave_name");
        $typeStmt->execute();
        $leaveTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'leaves' => $leaves,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$totalCount,
                    'totalPages' => ceil($totalCount / $limit)
                ],
                'filters' => [
                    'leave_types' => $leaveTypes,
                    'statuses' => ['Pending', 'Approved', 'Rejected', 'Cancelled']
                ]
            ],
            'message' => 'Leaves retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Leaves Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetLeave($pdo, $leaveId) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                el.*,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id as emp_id,
                e.email as employee_email,
                d.dept_name as department_name,
                p.position_title,
                lt.leave_name,
                lt.leave_code,
                lt.is_paid,
                lt.max_days_per_year,
                CONCAT(approver.first_name, ' ', approver.last_name) as approved_by_name,
                CONCAT(rejector.first_name, ' ', rejector.last_name) as rejected_by_name
            FROM employee_leaves el
            INNER JOIN employees e ON el.employee_id = e.id
            INNER JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees approver ON el.approved_by = approver.id AND el.status = 'Approved'
            LEFT JOIN employees rejector ON el.approved_by = rejector.id AND el.status = 'Rejected'
            WHERE el.id = ?
        ");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Leave request not found',
                'code' => 'LEAVE_NOT_FOUND'
            ]);
            return;
        }

        // Get associated documents
        $docStmt = $pdo->prepare("
            SELECT id, file_name, original_name, file_size, file_type, uploaded_at
            FROM leave_documents
            WHERE leave_id = ?
            ORDER BY uploaded_at ASC
        ");
        $docStmt->execute([$leaveId]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        $leave['documents'] = $documents;

        echo json_encode([
            'success' => true,
            'data' => $leave,
            'message' => 'Leave request retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetLeaveTypes($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, leave_code, leave_name, description, max_days_per_year, is_paid, requires_approval
            FROM leave_types
            WHERE is_active = 1
            ORDER BY leave_name
        ");
        $stmt->execute();
        $leaveTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $leaveTypes,
            'message' => 'Leave types retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Leave Types Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetLeaveBalance($pdo, $employeeId = null) {
    try {
        if (!$employeeId) {
            // Get current user's employee record
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
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
            $employeeId = $employee['id'];
        }

        $currentYear = date('Y');

        $stmt = $pdo->prepare("
            SELECT
                lt.id,
                lt.leave_code,
                lt.leave_name,
                lt.max_days_per_year,
                lt.is_paid,
                COALESCE(SUM(CASE
                    WHEN el.status = 'Approved' AND YEAR(el.start_date) = ?
                    THEN el.total_days
                    ELSE 0
                END), 0) as used_days,
                (lt.max_days_per_year - COALESCE(SUM(CASE
                    WHEN el.status = 'Approved' AND YEAR(el.start_date) = ?
                    THEN el.total_days
                    ELSE 0
                END), 0)) as remaining_days
            FROM leave_types lt
            LEFT JOIN employee_leaves el ON lt.id = el.leave_type_id AND el.employee_id = ?
            WHERE lt.is_active = 1
            GROUP BY lt.id, lt.leave_code, lt.leave_name, lt.max_days_per_year, lt.is_paid
            ORDER BY lt.leave_name
        ");
        $stmt->execute([$currentYear, $currentYear, $employeeId]);
        $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'balances' => $balances,
                'year' => $currentYear,
                'employee_id' => $employeeId
            ],
            'message' => 'Leave balances retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Leave Balance Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleCreateLeave($pdo) {
    try {
        // Ensure we start with a clean transaction state
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Handle both JSON and FormData inputs
        $input = [];
        if (isset($_POST['employee_id'])) {
            // FormData (with file uploads)
            $input = $_POST;
        } else {
            // JSON input (fallback)
            $input = json_decode(file_get_contents('php://input'), true);
        }

        // Validate required fields
        $required_fields = ['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                    'code' => 'VALIDATION_ERROR'
                ]);
                return;
            }
        }

        // Validate dates
        $start_date = $input['start_date'];
        $end_date = $input['end_date'];

        if (strtotime($start_date) > strtotime($end_date)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Start date cannot be after end date',
                'code' => 'VALIDATION_ERROR'
            ]);
            return;
        }

        // Calculate total days (excluding weekends for now - can be enhanced)
        $total_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

        // Check if employee exists
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$input['employee_id']]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Employee not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        // Check if leave type exists
        $stmt = $pdo->prepare("SELECT id FROM leave_types WHERE id = ? AND is_active = 1");
        $stmt->execute([$input['leave_type_id']]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid leave type',
                'code' => 'INVALID_LEAVE_TYPE'
            ]);
            return;
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert leave request
        $stmt = $pdo->prepare("
            INSERT INTO employee_leaves (
                employee_id, leave_type_id, start_date, end_date,
                total_days, reason, emergency_contact, status, applied_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', CURDATE())
        ");

        $stmt->execute([
            $input['employee_id'],
            $input['leave_type_id'],
            $start_date,
            $end_date,
            $total_days,
            $input['reason'],
            $input['emergency_contact'] ?? null
        ]);

        $leaveId = $pdo->lastInsertId();

        // Handle file uploads
        $uploadedFiles = [];
        if (isset($_FILES['documents'])) {
            // Handle both single file and multiple files
            $files = $_FILES['documents'];

            // If single file, convert to array format
            if (!is_array($files['name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']]
                ];
            }

            // Check if we have files to process
            if (!empty($files['name'][0])) {
                $uploadedFiles = handleFileUploads($pdo, $leaveId, $files);
            }
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'data' => [
                'leave_id' => $leaveId,
                'total_days' => $total_days,
                'status' => 'Pending',
                'uploaded_files' => count($uploadedFiles)
            ],
            'message' => 'Leave request created successfully'
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Create Leave PDO Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred: ' . $e->getMessage(),
            'code' => 'DATABASE_ERROR'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Create Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage(),
            'code' => 'UPLOAD_ERROR'
        ]);
    }
}

function handleFileUploads($pdo, $leaveId, $files) {
    $uploadedFiles = [];
    $uploadDir = __DIR__ . '/../uploads/leave_documents/';

    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate files array structure
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $uploadedFiles; // No files to process
    }

    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = $files['name'][$i];
        $fileSize = $files['size'][$i];
        $fileType = $files['type'][$i];
        $tmpName = $files['tmp_name'][$i];

        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type for {$originalName}");
        }

        // Validate file size
        if ($fileSize > $maxSize) {
            throw new Exception("File {$originalName} is too large");
        }

        // Generate unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid('leave_doc_') . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($tmpName, $filePath)) {
            throw new Exception("Failed to upload {$originalName}");
        }

        // Save file info to database
        $stmt = $pdo->prepare("
            INSERT INTO leave_documents (leave_id, file_name, original_name, file_size, file_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$leaveId, $fileName, $originalName, $fileSize, $fileType]);

        $uploadedFiles[] = [
            'id' => $pdo->lastInsertId(),
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_size' => $fileSize
        ];
    }

    return $uploadedFiles;
}

function handleApproveLeave($pdo, $leaveId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $notes = $input['notes'] ?? '';

        // Get current user's employee record to use as approver
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $approver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$approver) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Approver employee record not found',
                'code' => 'APPROVER_NOT_FOUND'
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE employee_leaves
            SET status = 'Approved',
                approved_by = ?,
                approved_date = CURDATE(),
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'Pending'
        ");

        $stmt->execute([$approver['id'], $notes, $leaveId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Leave request not found or already processed',
                'code' => 'LEAVE_NOT_FOUND'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'leave_id' => $leaveId,
                'status' => 'Approved',
                'approved_date' => date('Y-m-d')
            ],
            'message' => 'Leave request approved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Approve Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleRejectLeave($pdo, $leaveId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $notes = $input['notes'] ?? '';

        // Get current user's employee record to use as approver
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $approver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$approver) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Approver employee record not found',
                'code' => 'APPROVER_NOT_FOUND'
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE employee_leaves
            SET status = 'Rejected',
                approved_by = ?,
                approved_date = CURDATE(),
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'Pending'
        ");

        $stmt->execute([$approver['id'], $notes, $leaveId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Leave request not found or already processed',
                'code' => 'LEAVE_NOT_FOUND'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'leave_id' => $leaveId,
                'status' => 'Rejected',
                'approved_date' => date('Y-m-d')
            ],
            'message' => 'Leave request rejected successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Reject Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleUpdateLeave($pdo, $leaveId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        // Only allow updates to pending leaves
        $stmt = $pdo->prepare("SELECT status FROM employee_leaves WHERE id = ?");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Leave request not found',
                'code' => 'LEAVE_NOT_FOUND'
            ]);
            return;
        }

        if ($leave['status'] !== 'Pending') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Cannot update processed leave request',
                'code' => 'LEAVE_PROCESSED'
            ]);
            return;
        }

        // Build update query based on provided fields
        $updateFields = [];
        $params = [];

        if (isset($input['start_date'])) {
            $updateFields[] = "start_date = ?";
            $params[] = $input['start_date'];
        }

        if (isset($input['end_date'])) {
            $updateFields[] = "end_date = ?";
            $params[] = $input['end_date'];
        }

        if (isset($input['reason'])) {
            $updateFields[] = "reason = ?";
            $params[] = $input['reason'];
        }

        if (isset($input['start_date']) || isset($input['end_date'])) {
            // Recalculate total days if dates changed
            $start = $input['start_date'] ?? $leave['start_date'];
            $end = $input['end_date'] ?? $leave['end_date'];
            $total_days = (strtotime($end) - strtotime($start)) / (60 * 60 * 24) + 1;
            $updateFields[] = "total_days = ?";
            $params[] = $total_days;
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No fields to update',
                'code' => 'NO_UPDATE_FIELDS'
            ]);
            return;
        }

        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $leaveId;

        $stmt = $pdo->prepare("
            UPDATE employee_leaves
            SET " . implode(', ', $updateFields) . "
            WHERE id = ?
        ");

        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'data' => [
                'leave_id' => $leaveId,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'message' => 'Leave request updated successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Update Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleDeleteLeave($pdo, $leaveId) {
    try {
        // Only allow deletion of pending leaves
        $stmt = $pdo->prepare("SELECT status FROM employee_leaves WHERE id = ?");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Leave request not found',
                'code' => 'LEAVE_NOT_FOUND'
            ]);
            return;
        }

        if ($leave['status'] !== 'Pending') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Cannot delete processed leave request',
                'code' => 'LEAVE_PROCESSED'
            ]);
            return;
        }

        // Mark as cancelled instead of deleting
        $stmt = $pdo->prepare("
            UPDATE employee_leaves
            SET status = 'Cancelled', updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$leaveId]);

        echo json_encode([
            'success' => true,
            'data' => [
                'leave_id' => $leaveId,
                'status' => 'Cancelled'
            ],
            'message' => 'Leave request cancelled successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Delete Leave Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}
?>