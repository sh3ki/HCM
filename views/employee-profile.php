<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Get employee ID from URL parameter
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: employees.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

try {
    // Fetch detailed employee information
    $stmt = $pdo->prepare("
        SELECT
            e.*,
            d.dept_name,
            d.dept_code,
            p.position_title,
            p.position_code,
            p.job_description,
            ec.basic_salary,
            ec.current_step,
            sg.grade_name,
            sg.min_salary,
            sg.max_salary,
            supervisor.first_name as supervisor_first_name,
            supervisor.last_name as supervisor_last_name,
            supervisor.employee_id as supervisor_employee_id
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
        LEFT JOIN salary_grades sg ON ec.salary_grade_id = sg.id
        LEFT JOIN employees supervisor ON e.supervisor_id = supervisor.id
        WHERE e.id = ?
    ");

    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        header('Location: employees.php');
        exit();
    }

    // Get employee allowances
    $allowances_stmt = $pdo->prepare("
        SELECT
            at.allowance_name,
            ea.amount,
            ea.effective_date,
            ea.is_active
        FROM employee_allowances ea
        JOIN allowance_types at ON ea.allowance_type_id = at.id
        WHERE ea.employee_id = ? AND ea.is_active = 1
        ORDER BY at.allowance_name
    ");
    $allowances_stmt->execute([$employee_id]);
    $allowances = $allowances_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get employee dependents
    $dependents_stmt = $pdo->prepare("
        SELECT *
        FROM employee_dependents
        WHERE employee_id = ?
        ORDER BY relationship, dependent_name
    ");
    $dependents_stmt->execute([$employee_id]);
    $dependents = $dependents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get employment history
    $history_stmt = $pdo->prepare("
        SELECT
            eh.*,
            d.dept_name,
            p.position_title
        FROM employment_history eh
        LEFT JOIN departments d ON eh.department_id = d.id
        LEFT JOIN positions p ON eh.position_id = p.id
        WHERE eh.employee_id = ?
        ORDER BY eh.start_date DESC
        LIMIT 10
    ");
    $history_stmt->execute([$employee_id]);
    $employment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent leave records
    $leave_stmt = $pdo->prepare("
        SELECT
            el.*,
            lt.leave_name,
            approver.first_name as approver_first_name,
            approver.last_name as approver_last_name
        FROM employee_leaves el
        LEFT JOIN leave_types lt ON el.leave_type_id = lt.id
        LEFT JOIN employees approver ON el.approved_by = approver.id
        WHERE el.employee_id = ?
        ORDER BY el.start_date DESC
        LIMIT 10
    ");
    $leave_stmt->execute([$employee_id]);
    $leave_records = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent payroll records
    $payroll_stmt = $pdo->prepare("
        SELECT
            pr.*,
            pp.period_name,
            pp.start_date as period_start,
            pp.end_date as period_end
        FROM payroll_records pr
        JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
        WHERE pr.employee_id = ?
        ORDER BY pp.start_date DESC
        LIMIT 6
    ");
    $payroll_stmt->execute([$employee_id]);
    $payroll_records = $payroll_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Employee Profile - <?php echo $employee ? htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) : 'Not Found'; ?> - HCM System</title>
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
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
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
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Employee Header Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="relative bg-gradient-to-r from-primary to-blue-600 h-32 rounded-t-lg"></div>
                <div class="px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between -mt-16 relative">
                        <div class="flex items-end">
                            <div class="relative">
                                <img class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white"
                                     src="<?php echo !empty($employee['profile_picture']) ? htmlspecialchars($employee['profile_picture']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80'; ?>"
                                     alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                <div class="absolute bottom-2 right-2 w-6 h-6 rounded-full border-2 border-white
                                    <?php echo $employee['employment_status'] == 'Active' ? 'bg-green-500' : ($employee['employment_status'] == 'On Leave' ? 'bg-yellow-500' : 'bg-gray-500'); ?>">
                                </div>
                            </div>
                            <div class="ml-6 mb-4">
                                <h1 class="text-2xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </h1>
                                <p class="text-lg text-gray-600"><?php echo htmlspecialchars($employee['position_title']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['dept_name']); ?></p>
                                <div class="flex items-center mt-2">
                                    <span class="<?php echo $employee['employment_status'] == 'Active' ? 'bg-green-100 text-green-800' : ($employee['employment_status'] == 'On Leave' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?> text-xs font-medium px-2.5 py-0.5 rounded mr-2">
                                        <?php echo htmlspecialchars($employee['employment_status']); ?>
                                    </span>
                                    <span class="text-sm text-gray-600">ID: <?php echo htmlspecialchars($employee['employee_id']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 sm:mt-0">
                            <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                <i class="fas fa-edit mr-2"></i>Edit Profile
                            </button>
                            <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button class="tab-button active-tab border-primary text-primary py-2 px-1 border-b-2 font-medium text-sm" data-tab="overview">
                        <i class="fas fa-user mr-2"></i>Overview
                    </button>
                    <button class="tab-button text-gray-500 hover:text-gray-700 py-2 px-1 border-b-2 border-transparent hover:border-gray-300 font-medium text-sm" data-tab="compensation">
                        <i class="fas fa-dollar-sign mr-2"></i>Compensation
                    </button>
                    <button class="tab-button text-gray-500 hover:text-gray-700 py-2 px-1 border-b-2 border-transparent hover:border-gray-300 font-medium text-sm" data-tab="history">
                        <i class="fas fa-history mr-2"></i>Employment History
                    </button>
                    <button class="tab-button text-gray-500 hover:text-gray-700 py-2 px-1 border-b-2 border-transparent hover:border-gray-300 font-medium text-sm" data-tab="leaves">
                        <i class="fas fa-calendar-alt mr-2"></i>Leave Records
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Personal Information -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Full Name</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars(trim($employee['first_name'] . ' ' . $employee['middle_name'] . ' ' . $employee['last_name'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Email Address</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="text-primary hover:underline">
                                                <?php echo htmlspecialchars($employee['email']); ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Phone Number</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['phone'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Date of Birth</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo $employee['date_of_birth'] ? date('M j, Y', strtotime($employee['date_of_birth'])) : 'N/A'; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Gender</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php
                                            $gender_map = ['M' => 'Male', 'F' => 'Female', 'Other' => 'Other'];
                                            echo htmlspecialchars($gender_map[$employee['gender']] ?? $employee['gender'] ?: 'N/A');
                                            ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Marital Status</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['marital_status'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-gray-500">Address</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['address'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">City</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['city'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">State/Province</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['state'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Zip Code</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['zip_code'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Country</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($employee['country'] ?: 'N/A'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Government IDs -->
                        <?php if (!empty($government_ids)): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Government IDs</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($government_ids as $key => $value): ?>
                                        <?php if (!empty($value)): ?>
                                        <div>
                                            <label class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars(strtoupper($key)); ?></label>
                                            <p class="mt-1 text-sm text-gray-900 font-mono">
                                                <?php echo htmlspecialchars($value); ?>
                                            </p>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-6">
                        <!-- Employment Details -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Employment Details</h3>
                            </div>
                            <div class="px-6 py-4 space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Employee ID</label>
                                    <p class="mt-1 text-sm text-gray-900 font-mono">
                                        <?php echo htmlspecialchars($employee['employee_id']); ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['dept_name']); ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Position</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['position_title']); ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Employment Type</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['employee_type']); ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Hire Date</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($employee['hire_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Tenure</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php
                                        $hire_date = new DateTime($employee['hire_date']);
                                        $current_date = new DateTime();
                                        $interval = $hire_date->diff($current_date);
                                        echo $interval->y . ' years, ' . $interval->m . ' months';
                                        ?>
                                    </p>
                                </div>
                                <?php if ($employee['supervisor_first_name']): ?>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Reports To</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <a href="employee-profile.php?id=<?php echo $employee['supervisor_id']; ?>" class="text-primary hover:underline">
                                            <?php echo htmlspecialchars($employee['supervisor_first_name'] . ' ' . $employee['supervisor_last_name']); ?>
                                        </a>
                                        <span class="text-xs text-gray-500 block">
                                            <?php echo htmlspecialchars($employee['supervisor_employee_id']); ?>
                                        </span>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <?php if ($employee['emergency_contact_name']): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Emergency Contact</h3>
                            </div>
                            <div class="px-6 py-4 space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Name</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['emergency_contact_name']); ?>
                                    </p>
                                </div>
                                <?php if ($employee['emergency_contact_phone']): ?>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <a href="tel:<?php echo htmlspecialchars($employee['emergency_contact_phone']); ?>" class="text-primary hover:underline">
                                            <?php echo htmlspecialchars($employee['emergency_contact_phone']); ?>
                                        </a>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <?php if ($employee['emergency_contact_relationship']): ?>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Relationship</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['emergency_contact_relationship']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Dependents -->
                        <?php if (!empty($dependents)): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Dependents</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-3">
                                    <?php foreach ($dependents as $dependent): ?>
                                    <div class="border-l-4 border-primary pl-4">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($dependent['dependent_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($dependent['relationship']); ?>
                                            <?php if ($dependent['date_of_birth']): ?>
                                                • Born <?php echo date('M j, Y', strtotime($dependent['date_of_birth'])); ?>
                                            <?php endif; ?>
                                            <?php if ($dependent['is_hmo_covered']): ?>
                                                • <span class="text-green-600">HMO Covered</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Compensation Tab -->
            <div id="compensation-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Current Compensation -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Current Compensation</h3>
                        </div>
                        <div class="px-6 py-4">
                            <?php if ($employee['basic_salary']): ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Basic Salary</label>
                                    <p class="mt-1 text-2xl font-bold text-primary">
                                        ₱<?php echo number_format($employee['basic_salary'], 2); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">Monthly</p>
                                </div>
                                <?php if ($employee['grade_name']): ?>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Salary Grade</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['grade_name']); ?>
                                        <?php if ($employee['current_step']): ?>
                                            (Step <?php echo $employee['current_step']; ?>)
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($employee['min_salary'] && $employee['max_salary']): ?>
                                    <p class="text-xs text-gray-500">
                                        Range: ₱<?php echo number_format($employee['min_salary'], 2); ?> - ₱<?php echo number_format($employee['max_salary'], 2); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No compensation data available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Allowances -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Current Allowances</h3>
                        </div>
                        <div class="px-6 py-4">
                            <?php if (!empty($allowances)): ?>
                            <div class="space-y-3">
                                <?php
                                $total_allowances = 0;
                                foreach ($allowances as $allowance):
                                    $total_allowances += $allowance['amount'];
                                ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($allowance['allowance_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Effective: <?php echo date('M j, Y', strtotime($allowance['effective_date'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">
                                            ₱<?php echo number_format($allowance['amount'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="pt-3 border-t-2 border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <p class="text-sm font-bold text-gray-900">Total Allowances</p>
                                        <p class="text-lg font-bold text-primary">
                                            ₱<?php echo number_format($total_allowances, 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No allowances assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Payroll -->
                    <?php if (!empty($payroll_records)): ?>
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Payroll Records</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Period</th>
                                        <th class="px-6 py-3">Gross Pay</th>
                                        <th class="px-6 py-3">Deductions</th>
                                        <th class="px-6 py-3">Net Pay</th>
                                        <th class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payroll_records as $payroll): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($payroll['period_name']); ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo date('M j', strtotime($payroll['period_start'])); ?> - <?php echo date('M j, Y', strtotime($payroll['period_end'])); ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            ₱<?php echo number_format($payroll['gross_pay'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 text-red-600">
                                            ₱<?php echo number_format($payroll['total_deductions'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-green-600">
                                            ₱<?php echo number_format($payroll['net_pay'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="<?php echo $payroll['status'] == 'Paid' ? 'bg-green-100 text-green-800' : ($payroll['status'] == 'Approved' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                                <?php echo htmlspecialchars($payroll['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Employment History Tab -->
            <div id="history-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Employment History</h3>
                    </div>
                    <div class="px-6 py-4">
                        <?php if (!empty($employment_history)): ?>
                        <div class="space-y-4">
                            <?php foreach ($employment_history as $history): ?>
                            <div class="border-l-4 border-primary pl-6 pb-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900">
                                            <?php echo htmlspecialchars($history['position_title']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($history['dept_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php echo date('M j, Y', strtotime($history['start_date'])); ?>
                                            <?php if ($history['end_date']): ?>
                                                - <?php echo date('M j, Y', strtotime($history['end_date'])); ?>
                                            <?php else: ?>
                                                - Present
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($history['change_reason']): ?>
                                        <p class="text-sm text-gray-600 mt-2">
                                            <span class="font-medium">Reason:</span> <?php echo htmlspecialchars($history['change_reason']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if ($history['notes']): ?>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium">Notes:</span> <?php echo htmlspecialchars($history['notes']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No employment history records found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Leave Records Tab -->
            <div id="leaves-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Leave Records</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if (!empty($leave_records)): ?>
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Leave Type</th>
                                    <th class="px-6 py-3">Period</th>
                                    <th class="px-6 py-3">Days</th>
                                    <th class="px-6 py-3">Reason</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Approved By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_records as $leave): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <?php echo htmlspecialchars($leave['leave_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo date('M j', strtotime($leave['start_date'])); ?>
                                        <?php if ($leave['start_date'] !== $leave['end_date']): ?>
                                            - <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                                        <?php else: ?>
                                            <?php echo date(', Y', strtotime($leave['start_date'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo $leave['total_days']; ?> day<?php echo $leave['total_days'] != 1 ? 's' : ''; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo htmlspecialchars($leave['reason'] ?: 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="<?php
                                            echo $leave['status'] == 'Approved' ? 'bg-green-100 text-green-800' :
                                                ($leave['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                                                ($leave['status'] == 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                        ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                            <?php echo htmlspecialchars($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($leave['approver_first_name']): ?>
                                            <?php echo htmlspecialchars($leave['approver_first_name'] . ' ' . $leave['approver_last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="px-6 py-8">
                            <p class="text-gray-500 text-center">No leave records found</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Employee not found -->
            <div class="text-center py-12">
                <i class="fas fa-user-times text-6xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Employee Not Found</h2>
                <p class="text-gray-600 mb-6">The employee you're looking for doesn't exist or has been removed.</p>
                <a href="employees.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Employees
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for Tabs -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;

                    // Remove active classes from all tabs
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active-tab', 'border-primary', 'text-primary');
                        btn.classList.add('text-gray-500', 'hover:text-gray-700', 'border-transparent', 'hover:border-gray-300');
                    });

                    // Add active class to clicked tab
                    this.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-transparent', 'hover:border-gray-300');
                    this.classList.add('active-tab', 'border-primary', 'text-primary');

                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });

                    // Show target tab content
                    const targetContent = document.getElementById(targetTab + '-tab');
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                        targetContent.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>