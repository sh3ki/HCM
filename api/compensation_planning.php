<?php
require_once __DIR__ . '/../config/database.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

// DB Connection
$conn = $pdo ?? null;
if (!$conn) {
    echo json_encode(["error" => "Database connection not found"]);
    exit;
}

switch ($method) {
    case 'GET':
    if (isset($_GET['stats'])) {
        $stmt = $conn->query("SELECT COUNT(*) as total_plans, COALESCE(SUM(amount),0) as total_amount FROM compensations");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($data);
    } elseif (isset($_GET['id'])) {
        // single compensation
        $stmt = $conn->prepare("
            SELECT c.*, e.first_name, e.last_name
            FROM compensations c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($data ?: ["message" => "Compensation not found"]);
    } else {
        // all compensations
        $stmt = $conn->query("
            SELECT c.*, e.first_name, e.last_name
            FROM compensations c
            JOIN employees e ON c.employee_id = e.id
            ORDER BY c.created_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }
    break;


    case 'POST':
        if (!$input) {
            echo json_encode(["error" => "Invalid input"]);
            exit;
        }
        $stmt = $conn->prepare("
            INSERT INTO compensations (employee_id, position_title, plan_type, amount, effective_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $input['employee_id'],
            $input['position_title'], // string instead of FK
            $input['plan_type'],
            $input['amount'],
            $input['effective_date'],
            $input['remarks'] ?? null
        ]);
        echo json_encode([
            "success" => $success,
            "id" => $conn->lastInsertId()
        ]);
        break;

    case 'PUT':
        if (!isset($_GET['id']) || !$input) {
            echo json_encode(["error" => "ID and input required"]);
            exit;
        }
        $stmt = $conn->prepare("
            UPDATE compensations
            SET employee_id = ?, position_title = ?, plan_type = ?, amount = ?, effective_date = ?, remarks = ?
            WHERE id = ?
        ");
        $success = $stmt->execute([
            $input['employee_id'],
            $input['position_title'], // string
            $input['plan_type'],
            $input['amount'],
            $input['effective_date'],
            $input['remarks'] ?? null,
            $_GET['id']
        ]);
        echo json_encode(["success" => $success]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID required"]);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM compensations WHERE id = ?");
        $success = $stmt->execute([$_GET['id']]);
        echo json_encode(["success" => $success]);
        break;

    default:
        echo json_encode(["error" => "Method not allowed"]);
}
