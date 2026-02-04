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
$user_role = $_SESSION['role'] ?? 'employee';
$method = $_SERVER['REQUEST_METHOD'];

// Parse request URI to determine endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Extract endpoint and ID if present
$endpoint = '';
$id = null;

// Find the endpoint after 'benefits.php' or 'benefits_clean.php'
for ($i = 0; $i < count($path_parts); $i++) {
    if (strpos($path_parts[$i], 'benefits') !== false) {
        if (isset($path_parts[$i + 1])) {
            $endpoint = $path_parts[$i + 1];
            if (isset($path_parts[$i + 2]) && is_numeric($path_parts[$i + 2])) {
                $id = intval($path_parts[$i + 2]);
            }
        }
        break;
    }
}

// Debug logging
error_log("Benefits API - Request URI: " . $request_uri);
error_log("Benefits API - Path parts: " . json_encode($path_parts));
error_log("Benefits API - Endpoint: " . $endpoint);
error_log("Benefits API - ID: " . $id);

try {
    switch ($method) {
        case 'GET':
            if ($endpoint === 'plans') {
                if ($id) {
                    getBenefitPlan($pdo, $id);
                } else {
                    getBenefitPlans($pdo);
                }
            } elseif ($endpoint === 'providers') {
                getInsuranceProviders($pdo);
            } elseif ($endpoint === 'enrollments') {
                if ($id) {
                    getEmployeeEnrollments($pdo, $id);
                } else {
                    getAllEnrollments($pdo);
                }
            } elseif ($endpoint === 'stats') {
                getBenefitsStats($pdo);
            } else {
                // Default - benefits overview
                getBenefitsOverview($pdo);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if ($endpoint === 'plans') {
                createBenefitPlan($pdo, $input, $user_role);
            } elseif ($endpoint === 'enrollments') {
                enrollEmployee($pdo, $input, $user_role);
            } elseif ($endpoint === 'providers') {
                createInsuranceProvider($pdo, $input, $user_role);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invalid endpoint']);
            }
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            if ($endpoint === 'plans' && $id) {
                updateBenefitPlan($pdo, $id, $input, $user_role);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invalid endpoint or missing ID']);
            }
            break;

        case 'DELETE':
            if ($endpoint === 'plans' && $id) {
                deleteBenefitPlan($pdo, $id, $user_role);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invalid endpoint or missing ID']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Benefits API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

// Function implementations
function getBenefitsOverview($pdo) {
    try {
        $data = [
            'stats' => getBenefitsStatsData($pdo),
            'recent_enrollments' => getRecentEnrollmentsData($pdo),
            'active_plans' => getActivePlansData($pdo)
        ];

        echo json_encode(['success' => true, 'message' => 'Benefits overview retrieved successfully', 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve benefits overview: ' . $e->getMessage()]);
    }
}

function getBenefitPlans($pdo) {
    try {
        $sql = "SELECT ip.*, ipr.provider_name, ipr.provider_type,
                       COUNT(ei.id) as enrolled_count
                FROM insurance_plans ip
                LEFT JOIN insurance_providers ipr ON ip.provider_id = ipr.id
                LEFT JOIN employee_insurance ei ON ip.id = ei.insurance_plan_id AND ei.status = 'Active'
                WHERE ip.is_active = 1
                GROUP BY ip.id
                ORDER BY ip.created_at DESC, ip.plan_name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $plans = $stmt->fetchAll();

        // Format the data
        $formatted_plans = array_map(function($plan) {
            return [
                'id' => $plan['id'],
                'plan_name' => $plan['plan_name'],
                'plan_type' => $plan['plan_type'],
                'plan_code' => $plan['plan_code'],
                'provider_name' => $plan['provider_name'],
                'provider_type' => $plan['provider_type'],
                'coverage_amount' => number_format($plan['coverage_amount'], 2),
                'monthly_premium' => number_format($plan['monthly_premium'], 2),
                'employer_contribution' => number_format($plan['employer_contribution'], 2),
                'employee_contribution' => number_format($plan['employee_contribution'], 2),
                'description' => $plan['description'],
                'benefits_coverage' => $plan['benefits_coverage'],
                'enrolled_count' => intval($plan['enrolled_count']),
                'effective_date' => $plan['effective_date'],
                'expiry_date' => $plan['expiry_date'],
                'status' => $plan['is_active'] ? 'Active' : 'Inactive'
            ];
        }, $plans);

        echo json_encode(['success' => true, 'message' => 'Benefit plans retrieved successfully', 'data' => $formatted_plans]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve benefit plans: ' . $e->getMessage()]);
    }
}

function getBenefitPlan($pdo, $id) {
    try {
        $sql = "SELECT ip.*, ipr.provider_name, ipr.provider_type, ipr.contact_person,
                       ipr.contact_email, ipr.contact_phone, ipr.address,
                       COUNT(ei.id) as enrolled_count
                FROM insurance_plans ip
                LEFT JOIN insurance_providers ipr ON ip.provider_id = ipr.id
                LEFT JOIN employee_insurance ei ON ip.id = ei.insurance_plan_id AND ei.status = 'Active'
                WHERE ip.id = ? AND ip.is_active = 1
                GROUP BY ip.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Benefit plan not found']);
            return;
        }

        // Get enrolled employees for this plan
        $sql_employees = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email,
                                 ei.enrollment_date, ei.effective_date, ei.employee_premium,
                                 ei.dependents_count, ei.status, d.dept_name
                          FROM employee_insurance ei
                          JOIN employees e ON ei.employee_id = e.id
                          LEFT JOIN departments d ON e.department_id = d.id
                          WHERE ei.insurance_plan_id = ? AND ei.status = 'Active'
                          ORDER BY ei.enrollment_date DESC";

        $stmt_employees = $pdo->prepare($sql_employees);
        $stmt_employees->execute([$id]);
        $enrolled_employees = $stmt_employees->fetchAll();

        $formatted_plan = [
            'id' => $plan['id'],
            'plan_name' => $plan['plan_name'],
            'plan_type' => $plan['plan_type'],
            'plan_code' => $plan['plan_code'],
            'provider' => [
                'name' => $plan['provider_name'],
                'type' => $plan['provider_type'],
                'contact_person' => $plan['contact_person'],
                'contact_email' => $plan['contact_email'],
                'contact_phone' => $plan['contact_phone'],
                'address' => $plan['address']
            ],
            'coverage_amount' => number_format($plan['coverage_amount'], 2),
            'monthly_premium' => number_format($plan['monthly_premium'], 2),
            'employer_contribution' => number_format($plan['employer_contribution'], 2),
            'employee_contribution' => number_format($plan['employee_contribution'], 2),
            'description' => $plan['description'],
            'benefits_coverage' => $plan['benefits_coverage'],
            'enrolled_count' => intval($plan['enrolled_count']),
            'effective_date' => $plan['effective_date'],
            'expiry_date' => $plan['expiry_date'],
            'status' => $plan['is_active'] ? 'Active' : 'Inactive',
            'enrolled_employees' => $enrolled_employees
        ];

        echo json_encode(['success' => true, 'message' => 'Benefit plan retrieved successfully', 'data' => $formatted_plan]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve benefit plan: ' . $e->getMessage()]);
    }
}

function getInsuranceProviders($pdo) {
    try {
        $sql = "SELECT * FROM insurance_providers WHERE is_active = 1 ORDER BY provider_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $providers = $stmt->fetchAll();

        echo json_encode(['success' => true, 'message' => 'Insurance providers retrieved successfully', 'data' => $providers]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve insurance providers: ' . $e->getMessage()]);
    }
}

function getAllEnrollments($pdo) {
    try {
        $sql = "SELECT ei.*, e.employee_id, e.first_name, e.last_name, e.email,
                       ip.plan_name, ip.plan_type, ipr.provider_name, d.dept_name
                FROM employee_insurance ei
                JOIN employees e ON ei.employee_id = e.id
                JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
                LEFT JOIN insurance_providers ipr ON ip.provider_id = ipr.id
                LEFT JOIN departments d ON e.department_id = d.id
                ORDER BY ei.enrollment_date DESC
                LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $enrollments = $stmt->fetchAll();

        echo json_encode(['success' => true, 'message' => 'Enrollments retrieved successfully', 'data' => $enrollments]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve enrollments: ' . $e->getMessage()]);
    }
}

function getEmployeeEnrollments($pdo, $employee_id) {
    try {
        $sql = "SELECT ei.*, ip.plan_name, ip.plan_type, ipr.provider_name
                FROM employee_insurance ei
                JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
                LEFT JOIN insurance_providers ipr ON ip.provider_id = ipr.id
                WHERE ei.employee_id = ?
                ORDER BY ei.enrollment_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $enrollments = $stmt->fetchAll();

        echo json_encode(['success' => true, 'message' => 'Employee enrollments retrieved successfully', 'data' => $enrollments]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve employee enrollments: ' . $e->getMessage()]);
    }
}

function getBenefitsStats($pdo) {
    try {
        $stats = getBenefitsStatsData($pdo);
        echo json_encode(['success' => true, 'message' => 'Benefits statistics retrieved successfully', 'data' => $stats]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve benefits statistics: ' . $e->getMessage()]);
    }
}

function createBenefitPlan($pdo, $data, $user_role) {
    // Check permissions
    if (!in_array($user_role, ['admin', 'hr', 'Super Admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    try {
        // Validate required fields
        $required_fields = ['provider_id', 'plan_code', 'plan_name', 'monthly_premium', 'effective_date'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }

        // Check if plan code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM insurance_plans WHERE plan_code = ?");
        $stmt->execute([$data['plan_code']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan code already exists']);
            return;
        }

        $sql = "INSERT INTO insurance_plans (
                    provider_id, plan_code, plan_name, plan_type, coverage_amount,
                    monthly_premium, employer_contribution, employee_contribution,
                    description, benefits_coverage, effective_date, expiry_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['provider_id'],
            $data['plan_code'],
            $data['plan_name'],
            $data['plan_type'] ?? 'Individual',
            $data['coverage_amount'] ?? 0,
            $data['monthly_premium'],
            $data['employer_contribution'] ?? 0,
            $data['employee_contribution'] ?? 0,
            $data['description'] ?? '',
            $data['benefits_coverage'] ?? '{}',
            $data['effective_date'],
            $data['expiry_date'] ?? null
        ]);

        $plan_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Benefit plan created successfully', 'data' => ['plan_id' => $plan_id]]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create benefit plan: ' . $e->getMessage()]);
    }
}

function enrollEmployee($pdo, $data, $user_role) {
    // Check permissions
    if (!in_array($user_role, ['admin', 'hr', 'Super Admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    try {
        // Validate required fields
        $required_fields = ['employee_id', 'insurance_plan_id', 'enrollment_date', 'effective_date'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }

        // Check if employee is already enrolled in this plan
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_insurance WHERE employee_id = ? AND insurance_plan_id = ? AND status = 'Active'");
        $stmt->execute([$data['employee_id'], $data['insurance_plan_id']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Employee is already enrolled in this plan']);
            return;
        }

        // Get plan premium information
        $stmt = $pdo->prepare("SELECT monthly_premium, employer_contribution, employee_contribution FROM insurance_plans WHERE id = ?");
        $stmt->execute([$data['insurance_plan_id']]);
        $plan = $stmt->fetch();

        if (!$plan) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Insurance plan not found']);
            return;
        }

        $sql = "INSERT INTO employee_insurance (
                    employee_id, insurance_plan_id, enrollment_date, effective_date,
                    employee_premium, employer_premium, dependents_count, beneficiary_info
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        // Validate and prepare beneficiary_info
        $beneficiary_info = null;
        if (!empty($data['beneficiary_info'])) {
            if (is_string($data['beneficiary_info'])) {
                // Check if it's valid JSON
                $decoded = json_decode($data['beneficiary_info']);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $beneficiary_info = $data['beneficiary_info'];
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid beneficiary_info JSON format']);
                    return;
                }
            } else {
                // If it's an array/object, encode it as JSON
                $beneficiary_info = json_encode($data['beneficiary_info']);
            }
        }

        $stmt->execute([
            $data['employee_id'],
            $data['insurance_plan_id'],
            $data['enrollment_date'],
            $data['effective_date'],
            $plan['employee_contribution'],
            $plan['employer_contribution'],
            $data['dependents_count'] ?? 0,
            $beneficiary_info
        ]);

        $enrollment_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Employee enrolled successfully', 'data' => ['enrollment_id' => $enrollment_id]]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to enroll employee: ' . $e->getMessage()]);
    }
}

function createInsuranceProvider($pdo, $data, $user_role) {
    // Check permissions
    if (!in_array($user_role, ['admin', 'hr'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    try {
        // Validate required fields
        $required_fields = ['provider_code', 'provider_name', 'provider_type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }

        $sql = "INSERT INTO insurance_providers (
                    provider_code, provider_name, provider_type, contact_person,
                    contact_email, contact_phone, address
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['provider_code'],
            $data['provider_name'],
            $data['provider_type'],
            $data['contact_person'] ?? '',
            $data['contact_email'] ?? '',
            $data['contact_phone'] ?? '',
            $data['address'] ?? ''
        ]);

        $provider_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Insurance provider created successfully', 'data' => ['provider_id' => $provider_id]]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create insurance provider: ' . $e->getMessage()]);
    }
}

function updateBenefitPlan($pdo, $id, $data, $user_role) {
    // Check permissions
    if (!in_array($user_role, ['admin', 'hr', 'Super Admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    try {
        // Check if plan exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM insurance_plans WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Benefit plan not found']);
            return;
        }

        // Build update query dynamically
        $update_fields = [];
        $update_values = [];

        $allowed_fields = [
            'plan_name', 'plan_type', 'coverage_amount', 'monthly_premium',
            'employer_contribution', 'employee_contribution', 'description',
            'benefits_coverage', 'effective_date', 'expiry_date'
        ];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $update_values[] = $data[$field];
            }
        }

        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
            return;
        }

        $update_values[] = $id;
        $sql = "UPDATE insurance_plans SET " . implode(', ', $update_fields) . " WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_values);

        echo json_encode(['success' => true, 'message' => 'Benefit plan updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update benefit plan: ' . $e->getMessage()]);
    }
}

function deleteBenefitPlan($pdo, $id, $user_role) {
    // Check permissions
    if (!in_array($user_role, ['admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    try {
        // Check if plan has active enrollments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_insurance WHERE insurance_plan_id = ? AND status = 'Active'");
        $stmt->execute([$id]);
        $active_enrollments = $stmt->fetchColumn();

        if ($active_enrollments > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cannot delete plan with active enrollments']);
            return;
        }

        // Soft delete - set is_active to 0
        $stmt = $pdo->prepare("UPDATE insurance_plans SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Benefit plan not found']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Benefit plan deleted successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete benefit plan: ' . $e->getMessage()]);
    }
}

// Helper functions
function getBenefitsStatsData($pdo) {
    $stats = [];

    // Active plans count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM insurance_plans WHERE is_active = 1");
    $stmt->execute();
    $stats['active_plans'] = $stmt->fetchColumn();

    // Total enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_insurance WHERE status = 'Active'");
    $stmt->execute();
    $stats['total_enrollments'] = $stmt->fetchColumn();

    // Pending enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_insurance WHERE status = 'Pending'");
    $stmt->execute();
    $stats['pending_enrollments'] = $stmt->fetchColumn();

    // Active providers count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM insurance_providers WHERE is_active = 1");
    $stmt->execute();
    $stats['active_providers'] = $stmt->fetchColumn();

    return $stats;
}

function getRecentEnrollmentsData($pdo) {
    $sql = "SELECT ei.enrollment_date, ei.effective_date, ei.status,
                   e.first_name, e.last_name, ip.plan_name
            FROM employee_insurance ei
            JOIN employees e ON ei.employee_id = e.id
            JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
            ORDER BY ei.enrollment_date DESC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getActivePlansData($pdo) {
    $sql = "SELECT ip.id, ip.plan_name, ip.plan_type, ip.monthly_premium,
                   ip.coverage_amount, ip.description, ipr.provider_name,
                   COUNT(ei.id) as enrolled_count
            FROM insurance_plans ip
            LEFT JOIN insurance_providers ipr ON ip.provider_id = ipr.id
            LEFT JOIN employee_insurance ei ON ip.id = ei.insurance_plan_id AND ei.status = 'Active'
            WHERE ip.is_active = 1
            GROUP BY ip.id
            ORDER BY enrolled_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $plans = $stmt->fetchAll();

    // Format the data consistently with getBenefitPlans
    return array_map(function($plan) {
        return [
            'id' => $plan['id'],
            'plan_name' => $plan['plan_name'],
            'plan_type' => $plan['plan_type'],
            'provider_name' => $plan['provider_name'],
            'coverage_amount' => number_format($plan['coverage_amount'], 2),
            'monthly_premium' => number_format($plan['monthly_premium'], 2),
            'description' => $plan['description'],
            'enrolled_count' => intval($plan['enrolled_count'])
        ];
    }, $plans);
}
?>