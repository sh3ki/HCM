<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate('D, d M Y H:i:s') . " GMT");

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Fetch employee data from database with department and position information
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
            e.termination_date,
            e.profile_picture,
            d.dept_name as department,
            p.position_title as position,
            ec.basic_salary
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
        WHERE e.employment_status IN ('Active', 'On Leave', 'Inactive', 'Terminated')
        ORDER BY
            CASE e.employment_status
                WHEN 'Active' THEN 1
                WHEN 'On Leave' THEN 2
                WHEN 'Inactive' THEN 3
                WHEN 'Terminated' THEN 4
            END,
            e.employee_id
    ");

    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    foreach ($employees as $key => $employee) {
        // Format hire date
        if ($employee['hire_date']) {
            $employees[$key]['hire_date'] = date('M j, Y', strtotime($employee['hire_date']));
        }

        // Format termination date if exists
        if ($employee['termination_date']) {
            $employees[$key]['termination_date_formatted'] = date('M j, Y', strtotime($employee['termination_date']));
        }

        // Set default avatar if profile_picture is empty
        if (empty($employee['profile_picture'])) {
            // Use a placeholder avatar based on gender or just a generic one
            $employees[$key]['avatar'] = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80';
        } else {
            $employees[$key]['avatar'] = $employee['profile_picture'];
        }

        // Set status for display
        $employees[$key]['status'] = $employee['employment_status'];
    }

    // Get department list for filter
    $dept_stmt = $pdo->prepare("SELECT DISTINCT dept_name FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get departments with ID for form dropdown
    $dept_form_stmt = $pdo->prepare("SELECT id, dept_name FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $dept_form_stmt->execute();
    $departments_form = $dept_form_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get positions for form dropdown
    $pos_stmt = $pdo->prepare("SELECT id, position_title FROM positions WHERE is_active = 1 ORDER BY position_title");
    $pos_stmt->execute();
    $positions_form = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fallback to empty array if database error
    $employees = [];
    $departments = [];
    $departments_form = [];
    $positions_form = [];
    $error = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_employee':
                // Add new employee logic here
                $success = "Employee added successfully!";
                break;
            case 'edit_employee':
                // Edit employee logic here
                $success = "Employee updated successfully!";
                break;
            case 'delete_employee':
                if (isset($_POST['employee_id'])) {
                    try {
                        $delete_id = intval($_POST['employee_id']);

                        // Get employee info and check current status
                        $emp_stmt = $pdo->prepare("SELECT first_name, last_name, employee_id, employment_status FROM employees WHERE id = ?");
                        $emp_stmt->execute([$delete_id]);
                        $emp_info = $emp_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($emp_info) {
                            // Only proceed if employee is not already terminated
                            if ($emp_info['employment_status'] != 'Terminated') {
                                $pdo->beginTransaction();

                                // Soft delete - update employment status to 'Terminated'
                                // and set termination date (only if not already terminated)
                                $delete_stmt = $pdo->prepare("
                                    UPDATE employees
                                    SET employment_status = 'Terminated',
                                        termination_date = CASE
                                            WHEN termination_date IS NULL THEN CURDATE()
                                            ELSE termination_date
                                        END,
                                        updated_at = CURRENT_TIMESTAMP
                                    WHERE id = ?
                                    AND employment_status != 'Terminated'
                                    AND termination_date IS NULL
                                ");
                                $result = $delete_stmt->execute([$delete_id]);

                                if ($result && $delete_stmt->rowCount() > 0) {
                                    // Deactivate current compensation
                                    $comp_stmt = $pdo->prepare("
                                        UPDATE employee_compensation
                                        SET is_active = 0, end_date = CURDATE()
                                        WHERE employee_id = ? AND is_active = 1
                                    ");
                                    $comp_stmt->execute([$delete_id]);

                                    // Deactivate current allowances
                                    $allow_stmt = $pdo->prepare("
                                        UPDATE employee_allowances
                                        SET is_active = 0, end_date = CURDATE()
                                        WHERE employee_id = ? AND is_active = 1
                                    ");
                                    $allow_stmt->execute([$delete_id]);

                                    $pdo->commit();
                                    $success = "Employee {$emp_info['first_name']} {$emp_info['last_name']} ({$emp_info['employee_id']}) has been terminated successfully!";
                                } else {
                                    $pdo->rollback();
                                    $error = "Failed to terminate employee.";
                                }
                            }
                            // If employee is already terminated, silently skip - no error message
                        } else {
                            $error = "Employee not found.";
                        }

                    } catch (PDOException $e) {
                        if (isset($pdo)) {
                            $pdo->rollback();
                        }
                        $error = "Error terminating employee: " . $e->getMessage();
                    }
                }
                break;
            case 'restore_employee':
                if (isset($_POST['employee_id'])) {
                    try {
                        $restore_id = intval($_POST['employee_id']);

                        // Get employee info and check current status
                        $emp_stmt = $pdo->prepare("SELECT first_name, last_name, employee_id, employment_status FROM employees WHERE id = ?");
                        $emp_stmt->execute([$restore_id]);
                        $emp_info = $emp_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($emp_info) {
                            // Only proceed if employee is actually terminated
                            if ($emp_info['employment_status'] == 'Terminated') {
                                $pdo->beginTransaction();

                                // Restore employee - update employment status to 'Active'
                                // and clear termination date (only if currently terminated)
                                $restore_stmt = $pdo->prepare("
                                    UPDATE employees
                                    SET employment_status = 'Active',
                                        termination_date = NULL,
                                        updated_at = CURRENT_TIMESTAMP
                                    WHERE id = ?
                                    AND employment_status = 'Terminated'
                                    AND termination_date IS NOT NULL
                                ");
                                $result = $restore_stmt->execute([$restore_id]);

                                if ($result && $restore_stmt->rowCount() > 0) {
                                    $pdo->commit();
                                    $success = "Employee {$emp_info['first_name']} {$emp_info['last_name']} ({$emp_info['employee_id']}) has been restored successfully!";
                                } else {
                                    $pdo->rollback();
                                    $error = "Failed to restore employee.";
                                }
                            }
                            // If employee is not terminated, silently skip - no error message
                        } else {
                            $error = "Employee not found.";
                        }

                    } catch (PDOException $e) {
                        if (isset($pdo)) {
                            $pdo->rollback();
                        }
                        $error = "Error restoring employee: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - HCM System</title>
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
    <style>
        .processing-row {
            opacity: 0.6;
            pointer-events: none;
        }
        .processing-row::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-animation {
            animation: pulse 1.5s infinite;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-4 sm:ml-64">
        <div class="p-4 rounded-lg mt-14">
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Employee Management</h1>
                    <p class="text-gray-600">Manage your workforce and employee information</p>
                </div>
                <div class="flex gap-2 items-center">
                    <small class="text-gray-500 mr-3">Updated: <?php echo date('H:i:s'); ?></small>
                    <button onclick="window.location.reload(true)" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                        <i class="fas fa-sync mr-1"></i>Hard Refresh
                    </button>
                    <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center" onclick="openModal('add-employee-modal')">
                        <i class="fas fa-plus mr-2"></i>
                        Add Employee
                    </button>
                </div>
            </div>

            <!-- Filter and Search Bar -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="search" id="employee-search" class="w-full bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary focus:border-primary block pl-10 p-2.5" placeholder="Search by name, email, or department...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-500"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <select id="department-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="status-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Employee Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Employees List</h3>
                    <p class="text-sm text-gray-600">Total: <?php echo count($employees); ?> employees</p>
                </div>

                <div class="overflow-x-auto">
                    <table id="employees-table" class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 cursor-pointer" data-sort="employee">Employee</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="employee_id">Employee ID</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="department">Department</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="position">Position</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="status">Status</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="hire_date">Hire Date</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="termination_date">Termination Date</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="salary">Basic Salary</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr class="<?php echo $employee['employment_status'] == 'Terminated' ? 'bg-red-50 border-b hover:bg-red-100 opacity-75' : 'bg-white border-b hover:bg-gray-50'; ?>">
                                <td class="px-6 py-4" data-sort="employee">
                                    <div class="flex items-center">
                                        <div class="relative">
                                            <img class="w-10 h-10 rounded-full mr-3 <?php echo $employee['employment_status'] == 'Terminated' ? 'opacity-60 grayscale' : ''; ?>" src="<?php echo htmlspecialchars($employee['avatar']); ?>" alt="employee">
                                            <?php if ($employee['employment_status'] == 'Terminated'): ?>
                                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-times text-white text-xs"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-medium <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600 line-through' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                <?php if ($employee['employment_status'] == 'Terminated'): ?>
                                                    <span class="text-red-600 text-xs font-normal ml-2">(Terminated)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="<?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-400' : 'text-gray-500'; ?>">
                                                <?php echo htmlspecialchars($employee['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600' : 'text-gray-900'; ?>" data-sort="employee_id">
                                    <?php echo htmlspecialchars($employee['employee_id']); ?>
                                </td>
                                <td class="px-6 py-4 <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600' : 'text-gray-900'; ?>" data-sort="department">
                                    <?php echo htmlspecialchars($employee['department']); ?>
                                </td>
                                <td class="px-6 py-4 <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600' : 'text-gray-900'; ?>" data-sort="position">
                                    <?php echo htmlspecialchars($employee['position']); ?>
                                </td>
                                <td class="px-6 py-4" data-sort="status">
                                    <?php
                                    $statusClasses = [
                                        'Active' => 'bg-green-100 text-green-800',
                                        'On Leave' => 'bg-yellow-100 text-yellow-800',
                                        'Inactive' => 'bg-gray-100 text-gray-800',
                                        'Terminated' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusClass = $statusClasses[$employee['employment_status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="<?php echo $statusClass; ?> text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                        <?php if ($employee['employment_status'] == 'Terminated'): ?>
                                            <i class="fas fa-user-times mr-1"></i>
                                        <?php elseif ($employee['employment_status'] == 'Active'): ?>
                                            <i class="fas fa-check-circle mr-1"></i>
                                        <?php elseif ($employee['employment_status'] == 'On Leave'): ?>
                                            <i class="fas fa-calendar-times mr-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($employee['employment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600' : 'text-gray-900'; ?>" data-sort="hire_date">
                                    <?php echo htmlspecialchars($employee['hire_date']); ?>
                                </td>
                                <td class="px-6 py-4" data-sort="termination_date">
                                    <?php if ($employee['termination_date']): ?>
                                        <span class="text-red-600 font-medium">
                                            <i class="fas fa-calendar-times mr-1"></i>
                                            <?php echo htmlspecialchars($employee['termination_date_formatted']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 <?php echo $employee['employment_status'] == 'Terminated' ? 'text-gray-600' : 'text-gray-900'; ?>" data-sort="salary">
                                    <?php if ($employee['basic_salary']): ?>
                                        ₱<?php echo number_format($employee['basic_salary'], 2); ?>
                                        <?php if ($employee['employment_status'] == 'Terminated'): ?>
                                            <span class="text-xs text-gray-400 block">(Inactive)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button class="text-blue-600 hover:text-blue-800" title="View Details" onclick="viewEmployee(<?php echo $employee['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($employee['employment_status'] == 'Terminated'): ?>
                                            <button class="text-orange-600 hover:text-orange-800" title="Edit (Terminated)" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800" title="Restore Employee" onclick="restoreEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="text-green-600 hover:text-green-800" title="Edit" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800" title="Terminate Employee" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($employees); ?></span> of <span class="font-medium"><?php echo count($employees); ?></span> results
                        </div>
                        <div class="flex space-x-1">
                            <button class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100">Previous</button>
                            <button class="px-3 py-2 text-sm leading-tight text-white bg-primary border border-primary hover:bg-blue-700">1</button>
                            <button class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="add-employee-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('add-employee-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form id="add-employee-form" onsubmit="handleAddEmployee(event)">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Employee</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('add-employee-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Will be used for login">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Select Gender</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                    <select name="department_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments_form as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['id']); ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                                    <select name="position_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Select Position</option>
                                        <?php foreach ($positions_form as $pos): ?>
                                            <option value="<?php echo htmlspecialchars($pos['id']); ?>"><?php echo htmlspecialchars($pos['position_title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date *</label>
                                    <input type="date" name="hire_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status *</label>
                                    <select name="employment_status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="Active" selected>Active</option>
                                        <option value="On Leave">On Leave</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div id="add-employee-message" class="hidden text-sm mt-2"></div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('add-employee-modal')">Cancel</button>
                                <button type="submit" id="add-employee-submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-user-plus mr-2"></i>Add Employee
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Reusable Confirmation Modal -->
    <?php include 'includes/confirmation-modal.php'; ?>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Handle page refresh after operations
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a success message and auto-hide it after 5 seconds
            const successAlert = document.querySelector('.bg-green-100');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.transition = 'opacity 0.5s ease-out';
                    successAlert.style.opacity = '0';
                    setTimeout(function() {
                        successAlert.remove();
                    }, 500);
                }, 5000);
            }

            // Auto-refresh after successful terminate/restore operations
            const hasSuccess = successAlert !== null;

            // Check if the success message is about termination or restoration
            if (hasSuccess) {
                const successText = successAlert.textContent;
                const isTerminateOrRestore = successText.includes('terminated successfully') ||
                                           successText.includes('restored successfully');

                if (isTerminateOrRestore) {
                    // Show a processing indicator
                    successAlert.innerHTML += '<div class="mt-2 text-sm"><i class="fas fa-sync fa-spin mr-2"></i>Updating display...</div>';

                    setTimeout(function() {
                        // Force refresh to show updated data
                        window.location.reload(true);
                    }, 1500);
                }
            }
        });

        // Initialize search functionality
        initializeSearch('employees-table', 'employee-search');

        // Employee management functions
        function viewEmployee(id) {
            // Implementation for viewing employee details
            window.location.href = `employee-profile.php?id=${id}`;
        }

        function editEmployee(id) {
            // Implementation for editing employee
            window.location.href = `employee-edit.php?id=${id}`;
        }

        function deleteEmployee(id) {
            // Prevent multiple clicks
            const button = document.querySelector(`button[onclick="deleteEmployee(${id})"]`);
            if (button.disabled) return;

            // Find employee data from the table
            const row = button.closest('tr');
            const nameElement = row.querySelector('[data-sort="employee"] .font-medium');
            // Get just the first text node (before any spans)
            let employeeName = '';
            for (let node of nameElement.childNodes) {
                if (node.nodeType === Node.TEXT_NODE) {
                    employeeName += node.textContent;
                }
            }
            employeeName = employeeName.trim();
            const employeeId = row.querySelector('[data-sort="employee_id"]').textContent.trim();

            // Disable the button temporarily
            button.disabled = true;
            setTimeout(() => { button.disabled = false; }, 2000);

            showConfirmationModal({
                title: 'Terminate Employee',
                message: `Are you sure you want to terminate <strong>${employeeName}</strong> (${employeeId})? This action will:
                          <ul class="list-disc list-inside mt-2 text-left">
                            <li>Set employment status to "Terminated"</li>
                            <li>End current compensation and allowances</li>
                            <li>Set termination date to today</li>
                          </ul>
                          <p class="mt-2 text-red-600 font-medium">This action can be reversed by editing the employee record.</p>`,
                confirmText: 'Terminate',
                confirmClass: 'bg-red-600 hover:bg-red-700',
                icon: 'fas fa-user-times',
                iconClass: 'text-red-600',
                onConfirm: function() {
                    // Show loading state
                    const confirmBtn = document.getElementById('modal-confirm-btn');
                    const originalText = confirmBtn.innerHTML;
                    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    confirmBtn.disabled = true;

                    // Mark the row as processing
                    const row = button.closest('tr');
                    row.classList.add('processing-row', 'pulse-animation');

                    // Create and submit the delete form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_employee';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'employee_id';
                    idInput.value = id;

                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Modal functions are now loaded from includes/confirmation-modal.php

        function restoreEmployee(id) {
            // Prevent multiple clicks
            const button = document.querySelector(`button[onclick="restoreEmployee(${id})"]`);
            if (button.disabled) return;

            // Find employee data from the table
            const row = button.closest('tr');
            const nameElement = row.querySelector('[data-sort="employee"] .font-medium');
            // Get just the first text node (before any spans)
            let employeeName = '';
            for (let node of nameElement.childNodes) {
                if (node.nodeType === Node.TEXT_NODE) {
                    employeeName += node.textContent;
                }
            }
            employeeName = employeeName.trim();
            const employeeId = row.querySelector('[data-sort="employee_id"]').textContent.trim();

            // Disable the button temporarily
            button.disabled = true;
            setTimeout(() => { button.disabled = false; }, 2000);

            showConfirmationModal({
                title: 'Restore Employee',
                message: `Are you sure you want to restore <strong>${employeeName}</strong> (${employeeId})? This action will:
                          <ul class="list-disc list-inside mt-2 text-left">
                            <li>Set employment status to "Active"</li>
                            <li>Clear termination date</li>
                            <li>Allow the employee to access the system again</li>
                          </ul>
                          <p class="mt-2 text-green-600 font-medium">The employee will be able to resume normal work activities.</p>`,
                confirmText: 'Restore',
                confirmClass: 'bg-green-600 hover:bg-green-700',
                icon: 'fas fa-undo',
                iconClass: 'text-green-600',
                type: 'success',
                onConfirm: function() {
                    // Show loading state
                    const confirmBtn = document.getElementById('modal-confirm-btn');
                    const originalText = confirmBtn.innerHTML;
                    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    confirmBtn.disabled = true;

                    // Mark the row as processing
                    const row = button.closest('tr');
                    row.classList.add('processing-row', 'pulse-animation');

                    // Create and submit the restore form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'restore_employee';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'employee_id';
                    idInput.value = id;

                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Demo function to show different modal types
        function demonstrateModal() {
            const demoOptions = [
                {
                    title: 'Save Changes',
                    message: 'Do you want to save your changes before continuing?',
                    confirmText: 'Save',
                    confirmClass: 'bg-blue-600 hover:bg-blue-700',
                    icon: 'fas fa-save',
                    iconClass: 'text-blue-600',
                    type: 'info'
                },
                {
                    title: 'Warning',
                    message: 'This action may affect other records. Are you sure you want to continue?',
                    confirmText: 'Continue',
                    confirmClass: 'bg-yellow-600 hover:bg-yellow-700',
                    icon: 'fas fa-exclamation-triangle',
                    iconClass: 'text-yellow-600',
                    type: 'warning'
                },
                {
                    title: 'Success Confirmation',
                    message: 'The operation completed successfully! Do you want to view the results?',
                    confirmText: 'View Results',
                    confirmClass: 'bg-green-600 hover:bg-green-700',
                    icon: 'fas fa-check-circle',
                    iconClass: 'text-green-600',
                    type: 'success'
                }
            ];

            const randomDemo = demoOptions[Math.floor(Math.random() * demoOptions.length)];
            randomDemo.onConfirm = function() {
                alert('Demo action confirmed!');
            };

            showConfirmationModal(randomDemo);
        }

        // Filter functionality
        document.getElementById('department-filter').addEventListener('change', filterTable);
        document.getElementById('status-filter').addEventListener('change', filterTable);

        function filterTable() {
            const departmentFilter = document.getElementById('department-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#employees-table tbody tr');

            rows.forEach(row => {
                const department = row.querySelector('[data-sort="department"]').textContent;
                const status = row.querySelector('[data-sort="status"]').textContent.trim();

                const departmentMatch = !departmentFilter || department.includes(departmentFilter);
                const statusMatch = !statusFilter || status === statusFilter;

                if (departmentMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add Employee Form Handler
        async function handleAddEmployee(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = document.getElementById('add-employee-submit');
            const messageEl = document.getElementById('add-employee-message');
            const formData = new FormData(form);
            
            // Convert FormData to JSON
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
            messageEl.className = 'hidden text-sm mt-2';
            
            try {
                const response = await fetch('/HCM/api/employees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    messageEl.className = 'text-green-600 text-sm mt-2';
                    messageEl.textContent = result.message || 'Employee created successfully!';
                    
                    // Show credentials info
                    if (result.data.username) {
                        messageEl.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded p-3">
                                <p class="font-semibold mb-2">✓ ${result.message}</p>
                                <p class="text-xs"><strong>Username:</strong> ${result.data.username}</p>
                                <p class="text-xs"><strong>Employee #:</strong> ${result.data.employee_number}</p>
                                <p class="text-xs mt-1 text-gray-600">Credentials have been emailed to the employee.</p>
                            </div>
                        `;
                    }
                    
                    // Reset form
                    form.reset();
                    
                    // Reload page after 3 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Show error message
                    messageEl.className = 'text-red-600 text-sm mt-2 bg-red-50 border border-red-200 rounded p-2';
                    messageEl.textContent = result.error || 'Failed to create employee';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Add Employee';
                }
            } catch (error) {
                console.error('Error creating employee:', error);
                messageEl.className = 'text-red-600 text-sm mt-2 bg-red-50 border border-red-200 rounded p-2';
                messageEl.textContent = 'Failed to create employee. Please try again.';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Add Employee';
            }
        }
    </script>
</body>
</html>