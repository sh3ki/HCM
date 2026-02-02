<?php
// dependents.php - API endpoint for managing employee dependents

// Suppress all PHP errors and warnings for API responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to prevent any accidental output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ApiResponse.php';

// Clean any buffered output before sending headers
ob_clean();

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set content type
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    ApiResponse::error('Unauthorized - Please login first', 401);
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($path, '/'));

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    switch ($method) {
        case 'GET':
            handleGet($conn, $user_id);
            break;
        case 'POST':
            handlePost($conn, $user_id);
            break;
        case 'PUT':
            handlePut($conn, $user_id);
            break;
        case 'DELETE':
            handleDelete($conn, $user_id);
            break;
        default:
            ApiResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Dependents API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGet($conn, $user_id) {
    // Get query parameters
    $employee_id = $_GET['employee_id'] ?? '';
    $dependent_id = $_GET['dependent_id'] ?? '';
    $relationship = $_GET['relationship'] ?? '';
    $is_beneficiary = $_GET['is_beneficiary'] ?? '';
    $is_hmo_covered = $_GET['is_hmo_covered'] ?? '';

    if ($dependent_id) {
        getDependentById($conn, $user_id, $dependent_id);
    } elseif ($employee_id) {
        getDependentsByEmployee($conn, $user_id, $employee_id, $relationship, $is_beneficiary, $is_hmo_covered);
    } else {
        getAllDependents($conn, $user_id, $relationship, $is_beneficiary, $is_hmo_covered);
    }
}

function handlePost($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        ApiResponse::error('Invalid JSON data', 400);
        return;
    }

    createDependent($conn, $user_id, $input);
}

function handlePut($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['dependent_id'])) {
        ApiResponse::error('Invalid JSON data or missing dependent_id', 400);
        return;
    }

    updateDependent($conn, $user_id, $input);
}

function handleDelete($conn, $user_id) {
    $dependent_id = $_GET['dependent_id'] ?? '';

    if (!$dependent_id) {
        ApiResponse::error('Dependent ID is required', 400);
        return;
    }

    deleteDependent($conn, $user_id, $dependent_id);
}

function getAllDependents($conn, $user_id, $relationship = '', $is_beneficiary = '', $is_hmo_covered = '') {
    try {
        $where_clauses = [];
        $params = [];

        if ($relationship) {
            $where_clauses[] = "ed.relationship = :relationship";
            $params[':relationship'] = $relationship;
        }

        if ($is_beneficiary !== '') {
            $where_clauses[] = "ed.is_beneficiary = :is_beneficiary";
            $params[':is_beneficiary'] = $is_beneficiary;
        }

        if ($is_hmo_covered !== '') {
            $where_clauses[] = "ed.is_hmo_covered = :is_hmo_covered";
            $params[':is_hmo_covered'] = $is_hmo_covered;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        $stmt = $conn->prepare("
            SELECT
                ed.id,
                ed.employee_id,
                CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                e.employee_id as employee_number,
                ed.dependent_name,
                ed.relationship,
                ed.date_of_birth,
                ed.gender,
                ed.is_beneficiary,
                ed.beneficiary_percentage,
                ed.is_hmo_covered,
                ed.created_at,
                TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) as age
            FROM employee_dependents ed
            LEFT JOIN employees e ON ed.employee_id = e.id
            $where_sql
            ORDER BY e.first_name, e.last_name, ed.dependent_name
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $dependents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add calculated fields
        foreach ($dependents as &$dependent) {
            $dependent['is_minor'] = $dependent['age'] < 18;
            $dependent['age'] = intval($dependent['age']);
        }

        ApiResponse::success([
            'total_count' => count($dependents),
            'dependents' => $dependents
        ], 'Dependents retrieved successfully');
    } catch (Exception $e) {
        ApiResponse::error('Error retrieving dependents: ' . $e->getMessage(), 500);
    }
}

function getDependentsByEmployee($conn, $user_id, $employee_id, $relationship = '', $is_beneficiary = '', $is_hmo_covered = '') {
    try {
        // Check if employee exists
        $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE id = :employee_id");
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            ApiResponse::error('Employee not found', 404);
            return;
        }

        $where_clauses = ["ed.employee_id = :employee_id"];
        $params = [':employee_id' => $employee_id];

        if ($relationship) {
            $where_clauses[] = "ed.relationship = :relationship";
            $params[':relationship'] = $relationship;
        }

        if ($is_beneficiary !== '') {
            $where_clauses[] = "ed.is_beneficiary = :is_beneficiary";
            $params[':is_beneficiary'] = $is_beneficiary;
        }

        if ($is_hmo_covered !== '') {
            $where_clauses[] = "ed.is_hmo_covered = :is_hmo_covered";
            $params[':is_hmo_covered'] = $is_hmo_covered;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $stmt = $conn->prepare("
            SELECT
                ed.id,
                ed.employee_id,
                ed.dependent_name,
                ed.relationship,
                ed.date_of_birth,
                ed.gender,
                ed.is_beneficiary,
                ed.beneficiary_percentage,
                ed.is_hmo_covered,
                ed.created_at,
                TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) as age
            FROM employee_dependents ed
            $where_sql
            ORDER BY ed.dependent_name
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $dependents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add calculated fields
        foreach ($dependents as &$dependent) {
            $dependent['is_minor'] = $dependent['age'] < 18;
            $dependent['age'] = intval($dependent['age']);
        }

        ApiResponse::success([
            'employee' => $employee,
            'total_count' => count($dependents),
            'dependents' => $dependents
        ], 'Dependents retrieved successfully');
    } catch (Exception $e) {
        ApiResponse::error('Error retrieving employee dependents: ' . $e->getMessage(), 500);
    }
}

function getDependentById($conn, $user_id, $dependent_id) {
    try {
        $stmt = $conn->prepare("
            SELECT
                ed.id,
                ed.employee_id,
                CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                e.employee_id as employee_number,
                ed.dependent_name,
                ed.relationship,
                ed.date_of_birth,
                ed.gender,
                ed.is_beneficiary,
                ed.beneficiary_percentage,
                ed.is_hmo_covered,
                ed.created_at,
                TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) as age
            FROM employee_dependents ed
            LEFT JOIN employees e ON ed.employee_id = e.id
            WHERE ed.id = :dependent_id
        ");

        $stmt->bindParam(':dependent_id', $dependent_id);
        $stmt->execute();
        $dependent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dependent) {
            ApiResponse::error('Dependent not found', 404);
            return;
        }

        // Add calculated fields
        $dependent['is_minor'] = $dependent['age'] < 18;
        $dependent['age'] = intval($dependent['age']);

        ApiResponse::success($dependent, 'Dependent retrieved successfully');
    } catch (Exception $e) {
        ApiResponse::error('Error retrieving dependent: ' . $e->getMessage(), 500);
    }
}

function createDependent($conn, $user_id, $input) {
    try {
        // Validate required fields
        $required_fields = ['employee_id', 'dependent_name', 'relationship'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                ApiResponse::error("$field is required", 400);
                return;
            }
        }

        // Check if employee exists
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = :employee_id");
        $stmt->bindParam(':employee_id', $input['employee_id']);
        $stmt->execute();

        if (!$stmt->fetch()) {
            ApiResponse::error('Employee not found', 404);
            return;
        }

        // Validate relationship
        $valid_relationships = ['Spouse', 'Child', 'Parent', 'Sibling', 'Other'];
        if (!in_array($input['relationship'], $valid_relationships)) {
            ApiResponse::error('Invalid relationship type', 400);
            return;
        }

        // Validate beneficiary percentage if is_beneficiary is true
        if (!empty($input['is_beneficiary']) && $input['is_beneficiary'] == 1) {
            if (empty($input['beneficiary_percentage']) || $input['beneficiary_percentage'] <= 0 || $input['beneficiary_percentage'] > 100) {
                ApiResponse::error('Valid beneficiary percentage (1-100) is required for beneficiaries', 400);
                return;
            }

            // Check if total beneficiary percentage exceeds 100%
            $stmt = $conn->prepare("
                SELECT SUM(beneficiary_percentage) as total_percentage
                FROM employee_dependents
                WHERE employee_id = :employee_id AND is_beneficiary = 1
            ");
            $stmt->bindParam(':employee_id', $input['employee_id']);
            $stmt->execute();
            $total_percentage = $stmt->fetch(PDO::FETCH_ASSOC)['total_percentage'] ?? 0;

            if (($total_percentage + $input['beneficiary_percentage']) > 100) {
                ApiResponse::error('Total beneficiary percentage cannot exceed 100%', 400);
                return;
            }
        }

        // Insert dependent
        $stmt = $conn->prepare("
            INSERT INTO employee_dependents (
                employee_id,
                dependent_name,
                relationship,
                date_of_birth,
                gender,
                is_beneficiary,
                beneficiary_percentage,
                is_hmo_covered
            ) VALUES (
                :employee_id,
                :dependent_name,
                :relationship,
                :date_of_birth,
                :gender,
                :is_beneficiary,
                :beneficiary_percentage,
                :is_hmo_covered
            )
        ");

        // Prepare values for binding (bindParam requires variables, not expressions)
        $employee_id = $input['employee_id'];
        $dependent_name = $input['dependent_name'];
        $relationship = $input['relationship'];
        $date_of_birth = $input['date_of_birth'] ?? null;
        $gender = $input['gender'] ?? null;
        $is_beneficiary = $input['is_beneficiary'] ?? 0;
        $beneficiary_percentage = $input['beneficiary_percentage'] ?? 0;
        $is_hmo_covered = $input['is_hmo_covered'] ?? 0;

        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':dependent_name', $dependent_name);
        $stmt->bindParam(':relationship', $relationship);
        $stmt->bindParam(':date_of_birth', $date_of_birth);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':is_beneficiary', $is_beneficiary);
        $stmt->bindParam(':beneficiary_percentage', $beneficiary_percentage);
        $stmt->bindParam(':is_hmo_covered', $is_hmo_covered);

        $stmt->execute();
        $dependent_id = $conn->lastInsertId();

        // Get the created dependent
        getDependentById($conn, $user_id, $dependent_id);
    } catch (Exception $e) {
        ApiResponse::error('Error creating dependent: ' . $e->getMessage(), 500);
    }
}

function updateDependent($conn, $user_id, $input) {
    try {
        $dependent_id = $input['dependent_id'];

        // Check if dependent exists
        $stmt = $conn->prepare("SELECT employee_id FROM employee_dependents WHERE id = :dependent_id");
        $stmt->bindParam(':dependent_id', $dependent_id);
        $stmt->execute();
        $current_dependent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_dependent) {
            ApiResponse::error('Dependent not found', 404);
            return;
        }

        // Validate relationship if provided
        if (isset($input['relationship'])) {
            $valid_relationships = ['Spouse', 'Child', 'Parent', 'Sibling', 'Other'];
            if (!in_array($input['relationship'], $valid_relationships)) {
                ApiResponse::error('Invalid relationship type', 400);
                return;
            }
        }

        // Validate beneficiary percentage if updating beneficiary info
        if (isset($input['is_beneficiary']) && $input['is_beneficiary'] == 1 && isset($input['beneficiary_percentage'])) {
            if ($input['beneficiary_percentage'] <= 0 || $input['beneficiary_percentage'] > 100) {
                ApiResponse::error('Valid beneficiary percentage (1-100) is required for beneficiaries', 400);
                return;
            }

            // Check if total beneficiary percentage exceeds 100% (excluding current dependent)
            $stmt = $conn->prepare("
                SELECT SUM(beneficiary_percentage) as total_percentage
                FROM employee_dependents
                WHERE employee_id = :employee_id AND is_beneficiary = 1 AND id != :dependent_id
            ");
            $stmt->bindParam(':employee_id', $current_dependent['employee_id']);
            $stmt->bindParam(':dependent_id', $dependent_id);
            $stmt->execute();
            $total_percentage = $stmt->fetch(PDO::FETCH_ASSOC)['total_percentage'] ?? 0;

            if (($total_percentage + $input['beneficiary_percentage']) > 100) {
                ApiResponse::error('Total beneficiary percentage cannot exceed 100%', 400);
                return;
            }
        }

        // Build update query dynamically
        $update_fields = [];
        $params = [':dependent_id' => $dependent_id];

        $allowed_fields = [
            'dependent_name', 'relationship', 'date_of_birth', 'gender',
            'is_beneficiary', 'beneficiary_percentage', 'is_hmo_covered'
        ];

        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $update_fields[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }

        if (empty($update_fields)) {
            ApiResponse::error('No valid fields to update', 400);
            return;
        }

        $sql = "UPDATE employee_dependents SET " . implode(', ', $update_fields) . " WHERE id = :dependent_id";
        $stmt = $conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            ApiResponse::error('No changes made or dependent not found', 404);
            return;
        }

        // Get the updated dependent
        getDependentById($conn, $user_id, $dependent_id);
    } catch (Exception $e) {
        ApiResponse::error('Error updating dependent: ' . $e->getMessage(), 500);
    }
}

function deleteDependent($conn, $user_id, $dependent_id) {
    try {
        // Check if dependent exists
        $stmt = $conn->prepare("SELECT id, dependent_name FROM employee_dependents WHERE id = :dependent_id");
        $stmt->bindParam(':dependent_id', $dependent_id);
        $stmt->execute();
        $dependent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dependent) {
            ApiResponse::error('Dependent not found', 404);
            return;
        }

        // Delete the dependent
        $stmt = $conn->prepare("DELETE FROM employee_dependents WHERE id = :dependent_id");
        $stmt->bindParam(':dependent_id', $dependent_id);
        $stmt->execute();

        ApiResponse::success([
            'deleted_dependent' => $dependent
        ], 'Dependent deleted successfully');
    } catch (Exception $e) {
        ApiResponse::error('Error deleting dependent: ' . $e->getMessage(), 500);
    }
}
?>