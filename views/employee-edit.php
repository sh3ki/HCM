<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get employee ID from URL parameter
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: employees.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_employee') {
    try {
        $pdo->beginTransaction();

        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'department_id', 'position_id'];
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                throw new Exception("Field '$field' is required.");
            }
        }

        // Prepare government IDs JSON
        $government_ids = json_encode([
            'sss' => trim($_POST['sss'] ?? ''),
            'philhealth' => trim($_POST['philhealth'] ?? ''),
            'pagibig' => trim($_POST['pagibig'] ?? ''),
            'tin' => trim($_POST['tin'] ?? '')
        ]);

        // Update employee basic information
        $update_stmt = $pdo->prepare("
            UPDATE employees SET
                first_name = ?,
                middle_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                date_of_birth = ?,
                gender = ?,
                marital_status = ?,
                address = ?,
                city = ?,
                state = ?,
                zip_code = ?,
                country = ?,
                emergency_contact_name = ?,
                emergency_contact_phone = ?,
                emergency_contact_relationship = ?,
                employment_status = ?,
                employee_type = ?,
                department_id = ?,
                position_id = ?,
                supervisor_id = ?,
                government_ids = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $result = $update_stmt->execute([
            trim($_POST['first_name']),
            trim($_POST['middle_name'] ?? ''),
            trim($_POST['last_name']),
            trim($_POST['email']),
            trim($_POST['phone'] ?? ''),
            !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            !empty($_POST['gender']) ? $_POST['gender'] : null,
            !empty($_POST['marital_status']) ? $_POST['marital_status'] : null,
            trim($_POST['address'] ?? ''),
            trim($_POST['city'] ?? ''),
            trim($_POST['state'] ?? ''),
            trim($_POST['zip_code'] ?? ''),
            trim($_POST['country'] ?? 'Philippines'),
            trim($_POST['emergency_contact_name'] ?? ''),
            trim($_POST['emergency_contact_phone'] ?? ''),
            trim($_POST['emergency_contact_relationship'] ?? ''),
            $_POST['employment_status'] ?? 'Active',
            $_POST['employee_type'] ?? 'Regular',
            intval($_POST['department_id']),
            intval($_POST['position_id']),
            !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null,
            $government_ids,
            $employee_id
        ]);

        if (!$result) {
            throw new Exception("Failed to update employee information.");
        }

        // Update compensation if provided
        if (!empty($_POST['basic_salary']) && !empty($_POST['salary_grade_id'])) {
            // First, deactivate any current active compensation records
            $deactivate_stmt = $pdo->prepare("
                UPDATE employee_compensation
                SET is_active = 0, end_date = CURDATE()
                WHERE employee_id = ? AND is_active = 1
            ");
            $deactivate_stmt->execute([$employee_id]);

            // Add new compensation record
            $compensation_stmt = $pdo->prepare("
                INSERT INTO employee_compensation
                (employee_id, salary_grade_id, current_step, basic_salary, effective_date, is_active)
                VALUES (?, ?, ?, ?, CURDATE(), 1)
            ");
            $compensation_stmt->execute([
                $employee_id,
                intval($_POST['salary_grade_id']),
                intval($_POST['current_step'] ?? 1),
                floatval($_POST['basic_salary'])
            ]);
        }

        $pdo->commit();
        $success = "Employee information updated successfully!";

        // Add specific success messages for different updates
        if (!empty($_POST['basic_salary']) && !empty($_POST['salary_grade_id'])) {
            $success .= " Salary information has also been updated.";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Error updating employee: " . $e->getMessage();
    }
}

try {
    // Fetch employee data for editing
    $stmt = $pdo->prepare("
        SELECT
            e.*,
            d.dept_name,
            p.position_title,
            ec.basic_salary,
            ec.current_step,
            ec.salary_grade_id
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id
            AND ec.id = (SELECT MAX(id) FROM employee_compensation WHERE employee_id = e.id)
        WHERE e.id = ?
    ");

    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        header('Location: employees.php');
        exit();
    }

    // Get departments for dropdown
    $dept_stmt = $pdo->prepare("SELECT id, dept_name FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get positions for dropdown
    $pos_stmt = $pdo->prepare("SELECT id, position_title FROM positions WHERE is_active = 1 ORDER BY position_title");
    $pos_stmt->execute();
    $positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get potential supervisors (employees with manager/supervisor roles)
    $supervisor_stmt = $pdo->prepare("
        SELECT e.id, e.first_name, e.last_name, e.employee_id, p.position_title
        FROM employees e
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE e.employment_status = 'Active'
        AND e.id != ?
        AND (p.position_title LIKE '%Manager%' OR p.position_title LIKE '%Supervisor%' OR p.position_title LIKE '%Director%' OR p.position_title LIKE '%Chief%')
        ORDER BY e.first_name, e.last_name
    ");
    $supervisor_stmt->execute([$employee_id]);
    $supervisors = $supervisor_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get salary grades for dropdown
    $grade_stmt = $pdo->prepare("SELECT id, grade_name, min_salary, max_salary FROM salary_grades WHERE is_active = 1 ORDER BY grade_name");
    $grade_stmt->execute();
    $salary_grades = $grade_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $employee = null;
}

// Parse government IDs if they exist
$government_ids = [];
if ($employee && !empty($employee['government_ids'])) {
    $government_ids = json_decode($employee['government_ids'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - <?php echo $employee ? htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) : 'Not Found'; ?> - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1b68ff',
                        secondary: '#6c757d',
                        success: '#3ad29f',
                        danger: '#dc3545',
                        warning: '#eea303',
                        info: '#17a2b8',
                        light: '#f8f9fa',
                        dark: '#343a40'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-4 sm:ml-64">
        <div class="p-4 rounded-lg mt-14">
            <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($employee): ?>
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="employees.php" class="text-sm font-medium text-gray-700 hover:text-primary">Employees</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="employee-profile.php?id=<?php echo $employee['id']; ?>" class="text-sm font-medium text-gray-700 hover:text-primary">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
                    <p class="text-gray-600">Update employee information and details</p>
                </div>
                <div class="flex gap-3">
                    <a href="employee-profile.php?id=<?php echo $employee['id']; ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                        <i class="fas fa-eye mr-2"></i>View Profile
                    </a>
                    <a href="employees.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="action" value="update_employee">

                <!-- Personal Information Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                        <p class="text-sm text-gray-600">Basic personal details and contact information</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                <input type="text" name="first_name" required
                                       value="<?php echo htmlspecialchars($employee['first_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                <input type="text" name="middle_name"
                                       value="<?php echo htmlspecialchars($employee['middle_name'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                <input type="text" name="last_name" required
                                       value="<?php echo htmlspecialchars($employee['last_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                       value="<?php echo htmlspecialchars($employee['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="text" name="phone"
                                       value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <input type="date" name="date_of_birth"
                                       value="<?php echo $employee['date_of_birth']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Select Gender</option>
                                    <option value="M" <?php echo $employee['gender'] == 'M' ? 'selected' : ''; ?>>Male</option>
                                    <option value="F" <?php echo $employee['gender'] == 'F' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $employee['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Marital Status</label>
                                <select name="marital_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Select Status</option>
                                    <option value="Single" <?php echo $employee['marital_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo $employee['marital_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo $employee['marital_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo $employee['marital_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
                        <p class="text-sm text-gray-600">Current residential address</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                                <textarea name="address" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                          placeholder="Complete street address"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" name="city"
                                       value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                                <input type="text" name="state"
                                       value="<?php echo htmlspecialchars($employee['state'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                                <input type="text" name="zip_code"
                                       value="<?php echo htmlspecialchars($employee['zip_code'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <input type="text" name="country"
                                       value="<?php echo htmlspecialchars($employee['country'] ?? 'Philippines'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Employment Information</h3>
                        <p class="text-sm text-gray-600">Job details and organizational structure</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                <select name="department_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $employee['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                                <select name="position_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Select Position</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo $pos['id']; ?>" <?php echo $employee['position_id'] == $pos['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos['position_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Status</label>
                                <select name="employment_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="Active" <?php echo $employee['employment_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo $employee['employment_status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="On Leave" <?php echo $employee['employment_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="Terminated" <?php echo $employee['employment_status'] == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type</label>
                                <select name="employee_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="Regular" <?php echo $employee['employee_type'] == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="Contractual" <?php echo $employee['employee_type'] == 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                    <option value="Probationary" <?php echo $employee['employee_type'] == 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                                    <option value="Part-time" <?php echo $employee['employee_type'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Reports To</label>
                                <select name="supervisor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">No Supervisor</option>
                                    <?php foreach ($supervisors as $supervisor): ?>
                                        <option value="<?php echo $supervisor['id']; ?>" <?php echo $employee['supervisor_id'] == $supervisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name'] . ' (' . $supervisor['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compensation Information Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Compensation Information</h3>
                        <p class="text-sm text-gray-600">Salary and compensation details</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Grade</label>
                                <select name="salary_grade_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" onchange="updateSalaryRange()">
                                    <option value="">Select Salary Grade</option>
                                    <?php foreach ($salary_grades as $grade): ?>
                                        <option value="<?php echo $grade['id']; ?>"
                                                data-min="<?php echo $grade['min_salary']; ?>"
                                                data-max="<?php echo $grade['max_salary']; ?>"
                                                <?php echo $employee['salary_grade_id'] == $grade['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grade['grade_name']); ?>
                                            (₱<?php echo number_format($grade['min_salary']); ?> - ₱<?php echo number_format($grade['max_salary']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Step</label>
                                <input type="number" name="current_step" min="1" max="10"
                                       value="<?php echo htmlspecialchars($employee['current_step'] ?? '1'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Basic Salary</label>
                                <input type="number" name="basic_salary" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($employee['basic_salary'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <div id="salary-range" class="text-xs text-gray-500 mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Government IDs Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Government IDs</h3>
                        <p class="text-sm text-gray-600">Social security and tax identification numbers</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SSS Number</label>
                                <input type="text" name="sss"
                                       value="<?php echo htmlspecialchars($government_ids['sss'] ?? ''); ?>"
                                       placeholder="123-45-6789012"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Number</label>
                                <input type="text" name="philhealth"
                                       value="<?php echo htmlspecialchars($government_ids['philhealth'] ?? ''); ?>"
                                       placeholder="12-345678901-2"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pag-IBIG Number</label>
                                <input type="text" name="pagibig"
                                       value="<?php echo htmlspecialchars($government_ids['pagibig'] ?? ''); ?>"
                                       placeholder="1234-5678-9012"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">TIN Number</label>
                                <input type="text" name="tin"
                                       value="<?php echo htmlspecialchars($government_ids['tin'] ?? ''); ?>"
                                       placeholder="123-456-789-000"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Emergency Contact</h3>
                        <p class="text-sm text-gray-600">Person to contact in case of emergency</p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                                <input type="text" name="emergency_contact_name"
                                       value="<?php echo htmlspecialchars($employee['emergency_contact_name'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                <input type="text" name="emergency_contact_phone"
                                       value="<?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Relationship</label>
                                <select name="emergency_contact_relationship" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Select Relationship</option>
                                    <option value="Spouse" <?php echo $employee['emergency_contact_relationship'] == 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                    <option value="Parent" <?php echo $employee['emergency_contact_relationship'] == 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                    <option value="Child" <?php echo $employee['emergency_contact_relationship'] == 'Child' ? 'selected' : ''; ?>>Child</option>
                                    <option value="Sibling" <?php echo $employee['emergency_contact_relationship'] == 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                                    <option value="Friend" <?php echo $employee['emergency_contact_relationship'] == 'Friend' ? 'selected' : ''; ?>>Friend</option>
                                    <option value="Other" <?php echo $employee['emergency_contact_relationship'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                All changes will be saved to the employee record.
                            </div>
                            <div class="flex gap-3">
                                <a href="employee-profile.php?id=<?php echo $employee['id']; ?>"
                                   class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                                <button type="submit"
                                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php else: ?>
            <!-- Employee not found -->
            <div class="text-center py-12">
                <i class="fas fa-user-times text-6xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Employee Not Found</h2>
                <p class="text-gray-600 mb-6">The employee you're trying to edit doesn't exist or has been removed.</p>
                <a href="employees.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Employees
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for Enhanced Functionality -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Update salary range display when salary grade changes
        function updateSalaryRange() {
            const select = document.querySelector('select[name="salary_grade_id"]');
            const rangeDiv = document.getElementById('salary-range');

            if (select.value) {
                const option = select.options[select.selectedIndex];
                const min = parseFloat(option.dataset.min);
                const max = parseFloat(option.dataset.max);

                if (min && max) {
                    rangeDiv.textContent = `Salary range: ₱${min.toLocaleString()} - ₱${max.toLocaleString()}`;
                }
            } else {
                rangeDiv.textContent = '';
            }
        }

        // Initialize salary range display on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSalaryRange();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let hasErrors = false;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    hasErrors = true;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>