<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Mock payroll data
$payrollSummary = [
    'total_employees' => 248,
    'total_gross' => '₱12.5M',
    'total_deductions' => '₱2.8M',
    'total_net' => '₱9.7M'
];

$payrollRecords = [
    [
        'employee_id' => 'EMP001',
        'name' => 'Sarah Johnson',
        'basic_salary' => 55000,
        'allowances' => 8000,
        'overtime' => 2500,
        'gross_pay' => 65500,
        'deductions' => 14800,
        'net_pay' => 50700,
        'status' => 'Approved',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b890?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ],
    [
        'employee_id' => 'EMP002',
        'name' => 'Michael Chen',
        'basic_salary' => 48000,
        'allowances' => 7000,
        'overtime' => 0,
        'gross_pay' => 55000,
        'deductions' => 12400,
        'net_pay' => 42600,
        'status' => 'Pending',
        'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ],
    [
        'employee_id' => 'EMP003',
        'name' => 'Emily Rodriguez',
        'basic_salary' => 42000,
        'allowances' => 6500,
        'overtime' => 1200,
        'gross_pay' => 49700,
        'deductions' => 11200,
        'net_pay' => 38500,
        'status' => 'Approved',
        'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ]
];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'process_payroll':
                $success = "Payroll processed successfully!";
                break;
            case 'approve_payroll':
                $success = "Payroll approved successfully!";
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
    <title>Payroll Management - HCM System</title>
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
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payroll Management</h1>
                    <p class="text-gray-600" id="page-subtitle">Process and manage employee payroll</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="refreshAllData()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Refresh
                    </button>
                    <form method="POST" action="" class="inline">
                        <input type="hidden" name="action" value="process_payroll">
                        <button type="submit" class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                            <i class="fas fa-play mr-2"></i>
                            Process Payroll
                        </button>
                    </form>
                    <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center" onclick="openModal('new-period-modal')">
                        <i class="fas fa-plus mr-2"></i>
                        New Period
                    </button>
                </div>
            </div>

            <!-- Payroll Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-users text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Employees</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $payrollSummary['total_employees']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-money-bill-wave text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Gross Pay</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $payrollSummary['total_gross']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-minus-circle text-danger text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Deductions</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $payrollSummary['total_deductions']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-calculator text-warning text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Net Pay</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $payrollSummary['total_net']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Period Selection -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Payroll Period</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="period-selector" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5">
                            <option value="">Loading periods...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="flex items-center h-10">
                            <span id="period-status" class="bg-yellow-100 text-yellow-800 text-sm font-medium mr-2 px-3 py-1 rounded">Loading...</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pay Date</label>
                        <div class="flex items-center h-10">
                            <span id="period-pay-date" class="text-gray-900 font-medium">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Records Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 id="payroll-records-title" class="text-lg font-semibold text-gray-900">Payroll Records - Loading...</h3>
                        <p id="payroll-records-count" class="text-sm text-gray-600">Loading employees...</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="refreshAllData()" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            <i class="fas fa-sync-alt mr-1"></i>
                            Refresh
                        </button>
                        <button onclick="exportPayrollData()" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            <i class="fas fa-download mr-1"></i>
                            Export
                        </button>
                        <button onclick="printPayrollReport()" class="bg-primary text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-print mr-1"></i>
                            Print
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Employee</th>
                                <th class="px-6 py-3">Basic Salary</th>
                                <th class="px-6 py-3">Allowances</th>
                                <th class="px-6 py-3">Overtime</th>
                                <th class="px-6 py-3">Gross Pay</th>
                                <th class="px-6 py-3">Deductions</th>
                                <th class="px-6 py-3">Net Pay</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payrollRecords as $record): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <img class="w-8 h-8 rounded-full mr-3" src="<?php echo htmlspecialchars($record['avatar']); ?>" alt="employee">
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($record['name']); ?></div>
                                            <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($record['employee_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">₱<?php echo number_format($record['basic_salary']); ?></td>
                                <td class="px-6 py-4">₱<?php echo number_format($record['allowances']); ?></td>
                                <td class="px-6 py-4">₱<?php echo number_format($record['overtime']); ?></td>
                                <td class="px-6 py-4 font-bold text-gray-900">₱<?php echo number_format($record['gross_pay']); ?></td>
                                <td class="px-6 py-4 text-red-600">₱<?php echo number_format($record['deductions']); ?></td>
                                <td class="px-6 py-4 font-bold text-green-600">₱<?php echo number_format($record['net_pay']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="<?php echo $record['status'] == 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?php echo htmlspecialchars($record['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button class="text-blue-600 hover:text-blue-800" title="View Payslip">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-800" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-purple-600 hover:text-purple-800" title="Email Payslip">
                                            <i class="fas fa-envelope"></i>
                                        </button>
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
                            Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($payrollRecords); ?></span> of <span class="font-medium"><?php echo count($payrollRecords); ?></span> results
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

    <!-- Payslip Modal -->
    <div id="payslip-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('payslip-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Employee Payslip</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('payslip-modal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="payslip-content" class="space-y-4">
                        <!-- Payslip content will be inserted here -->
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                        <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('payslip-modal')">Close</button>
                        <button type="button" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700" onclick="printPayslip()">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Salary Modal -->
    <div id="edit-salary-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('edit-salary-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="edit-salary-form">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Employee Salary</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('edit-salary-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                                <div id="edit-employee-info" class="text-sm text-gray-600 bg-gray-50 p-2 rounded"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Basic Salary</label>
                                <div id="current-salary" class="text-sm text-gray-600 bg-gray-50 p-2 rounded"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Basic Salary</label>
                                <input type="number" id="new-salary" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter new salary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Change</label>
                                <textarea id="salary-reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter reason for salary change (optional)"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                            <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('edit-salary-modal')">Cancel</button>
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Update Salary</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 id="confirmation-title" class="text-lg leading-6 font-medium text-gray-900">Confirm Action</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('confirmation-modal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="mb-6">
                        <p id="confirmation-message" class="text-sm text-gray-600">Are you sure you want to proceed?</p>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('confirmation-modal')">Cancel</button>
                        <button type="button" id="confirmation-confirm-btn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div id="rejection-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('rejection-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="rejection-form">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Reject Payroll</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('rejection-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                                <div id="rejection-employee-info" class="text-sm text-gray-600 bg-gray-50 p-2 rounded"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection *</label>
                                <textarea id="rejection-reason" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Please provide a detailed reason for rejecting this payroll"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                            <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('rejection-modal')">Cancel</button>
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Reject Payroll</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Period Modal -->
    <div id="new-period-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('new-period-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="new-period-form">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Create New Payroll Period</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('new-period-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Period Name</label>
                                <input type="text" id="period-name" name="period_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="April 2024">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input type="date" id="period-start-date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input type="date" id="period-end-date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pay Date</label>
                                <input type="date" id="period-pay-date" name="pay_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('new-period-modal')">Cancel</button>
                                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Create Period</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // PayrollAPI Class for handling all payroll-related API calls
        class PayrollAPI {
            constructor(baseUrl = '../api/payroll.php') {
                this.baseUrl = baseUrl;
            }

            // Helper method for making API requests
            async request(endpoint, method = 'GET', data = null) {
                const url = `${this.baseUrl}?path=${endpoint}`;
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };

                if (data && (method === 'POST' || method === 'PUT')) {
                    options.body = JSON.stringify(data);
                }

                try {
                    const response = await fetch(url, options);
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || `HTTP error! status: ${response.status}`);
                    }

                    return result;
                } catch (error) {
                    console.error('API Request failed:', error);
                    throw error;
                }
            }

            // GET methods
            async getPayrollSummary(periodId = null) {
                let url = 'summary';
                if (periodId) {
                    url += `&period_id=${periodId}`;
                }
                return this.request(url);
            }

            async getPayrollRecords(periodId = null, limit = 50, offset = 0) {
                let url = `records&limit=${limit}&offset=${offset}`;
                if (periodId) {
                    url += `&period_id=${periodId}`;
                }
                return this.request(url);
            }

            async getEmployeePayroll(employeeId) {
                return this.request(`employee&employee_id=${employeeId}`);
            }

            async getPayrollPeriods() {
                return this.request('periods');
            }

            async getCurrentMonthPeriod() {
                return this.request('current-period');
            }

            async getAllPayrollData() {
                return this.request('');
            }

            // POST methods
            async processPayroll(periodId, employeeIds = []) {
                return this.request('process', 'POST', {
                    period_id: periodId,
                    employee_ids: employeeIds
                });
            }

            async createPayrollPeriod(periodData) {
                return this.request('period', 'POST', periodData);
            }

            async approvePayroll(periodId, employeeId = null) {
                return this.request('approve', 'POST', {
                    period_id: periodId,
                    employee_id: employeeId
                });
            }

            // PUT methods
            async updatePayrollRecord(employeeId, updates) {
                return this.request('update', 'PUT', {
                    employee_id: employeeId,
                    updates: updates
                });
            }

            async updatePayrollStatus(employeeId, status) {
                return this.request('status', 'PUT', {
                    employee_id: employeeId,
                    status: status
                });
            }

            // DELETE methods
            async deletePayrollRecord(employeeId, periodId = null) {
                return this.request('record', 'DELETE', {
                    employee_id: employeeId,
                    period_id: periodId
                });
            }
        }

        // Initialize PayrollAPI instance
        const payrollAPI = new PayrollAPI();

        // Global state for current period
        let currentPeriod = null;
        let allPeriods = [];

        // DOM manipulation functions
        function showLoading(element) {
            if (element) {
                element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                element.disabled = true;
            }
        }

        function hideLoading(element, originalText) {
            if (element) {
                element.innerHTML = originalText;
                element.disabled = false;
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
                type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
                'bg-blue-100 border border-blue-400 text-blue-700'
            }`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-xl">&times;</button>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Payroll-specific functions
        async function refreshPayrollSummary() {
            try {
                const result = await payrollAPI.getPayrollSummary(currentPeriod?.id);
                if (result.success) {
                    updateSummaryCards(result.data);
                }
            } catch (error) {
                console.error('Failed to refresh payroll summary:', error);
                showNotification('Failed to refresh payroll summary', 'error');
            }
        }

        function updateSummaryCards(data) {
            // Update summary cards with new data
            const cards = document.querySelectorAll('.grid .bg-white');
            if (cards.length >= 4) {
                cards[0].querySelector('.text-2xl').textContent = data.total_employees;
                cards[1].querySelector('.text-2xl').textContent = '₱' + data.total_gross;
                cards[2].querySelector('.text-2xl').textContent = '₱' + data.total_deductions;
                cards[3].querySelector('.text-2xl').textContent = '₱' + data.total_net;
            }
        }

        async function processPayrollAPI() {
            // Find the process payroll button more reliably
            const button = document.querySelector('button[type="submit"]') ||
                          document.querySelector('button:contains("Process Payroll")') ||
                          event.target;

            const originalText = button ? button.innerHTML : '';

            if (button) showLoading(button);

            try {
                showNotification('Processing payroll...', 'info');
                const result = await payrollAPI.processPayroll(1); // Current period ID
                if (result.success) {
                    showNotification(result.message, 'success');
                    // Force refresh all data
                    await Promise.all([
                        refreshPayrollSummary(),
                        loadPayrollRecords(currentPeriod?.id)
                    ]);
                }
            } catch (error) {
                showNotification('Failed to process payroll: ' + error.message, 'error');
                console.error('Process payroll error:', error);
            } finally {
                if (button) hideLoading(button, originalText);
            }
        }

        async function loadPayrollRecords(periodId = null) {
            try {
                const result = await payrollAPI.getPayrollRecords(periodId);

                if (result.success) {
                    updatePayrollTable(result.data);
                    updateRecordsHeader(result.data.length);
                } else {
                    throw new Error(result.error || 'Failed to load records');
                }
            } catch (error) {
                console.error('Failed to load payroll records:', error);
                showNotification('Failed to load payroll records: ' + error.message, 'error');
            }
        }

        async function loadPayrollPeriods() {
            try {
                const result = await payrollAPI.getPayrollPeriods();

                if (result.success) {
                    allPeriods = result.data;
                    updatePeriodSelector();

                    // Set current period to the most recent one if not already set
                    if (allPeriods.length > 0 && !currentPeriod) {
                        currentPeriod = allPeriods[0];
                        updateCurrentPeriodDisplay();
                    }
                } else {
                    throw new Error(result.error || 'Failed to load periods');
                }
            } catch (error) {
                console.error('Failed to load payroll periods:', error);
                showNotification('Failed to load payroll periods: ' + error.message, 'error');
            }
        }

        async function loadCurrentMonthPeriod() {
            try {
                const result = await payrollAPI.getCurrentMonthPeriod();

                if (result.success && result.data) {
                    currentPeriod = result.data;
                    console.log('Loaded current month period:', currentPeriod);
                    return true;
                } else {
                    console.log('No current month period found, will use most recent');
                    return false;
                }
            } catch (error) {
                console.error('Failed to load current month period:', error);
                return false;
            }
        }

        function updatePeriodSelector() {
            const selector = document.getElementById('period-selector');
            if (!selector) return;

            selector.innerHTML = '';

            if (allPeriods.length === 0) {
                selector.innerHTML = '<option value="">No periods available</option>';
                return;
            }

            allPeriods.forEach(period => {
                const option = document.createElement('option');
                option.value = period.id;
                option.textContent = period.name;
                option.dataset.period = JSON.stringify(period);

                if (currentPeriod && period.id == currentPeriod.id) {
                    option.selected = true;
                }

                selector.appendChild(option);
            });
        }

        function updateCurrentPeriodDisplay() {
            if (!currentPeriod) return;

            // Update page subtitle with current period info
            const pageSubtitle = document.getElementById('page-subtitle');
            if (pageSubtitle && currentPeriod.name) {
                const startDate = new Date(currentPeriod.start_date);
                const endDate = new Date(currentPeriod.end_date);
                const monthYear = startDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                pageSubtitle.textContent = `Showing payroll for: ${currentPeriod.name} (${monthYear})`;
            }

            // Update status badge
            const statusElement = document.getElementById('period-status');
            if (statusElement) {
                statusElement.textContent = currentPeriod.status || 'Processing';

                // Update status badge color
                statusElement.className = 'text-sm font-medium mr-2 px-3 py-1 rounded ';
                switch(currentPeriod.status?.toLowerCase()) {
                    case 'completed':
                    case 'paid':
                        statusElement.className += 'bg-green-100 text-green-800';
                        break;
                    case 'approved':
                        statusElement.className += 'bg-blue-100 text-blue-800';
                        break;
                    case 'processing':
                    case 'draft':
                    default:
                        statusElement.className += 'bg-yellow-100 text-yellow-800';
                        break;
                }
            }

            // Update pay date
            const payDateElement = document.getElementById('period-pay-date');
            if (payDateElement && currentPeriod.pay_date) {
                const payDate = new Date(currentPeriod.pay_date);
                payDateElement.textContent = payDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            // Update records header
            updateRecordsHeader();
        }

        function updateRecordsHeader(recordCount = null) {
            const headerTitle = document.getElementById('payroll-records-title');
            if (headerTitle && currentPeriod) {
                headerTitle.textContent = `Payroll Records - ${currentPeriod.name}`;
            }

            const headerSubtitle = document.getElementById('payroll-records-count');
            if (headerSubtitle) {
                if (recordCount !== null) {
                    headerSubtitle.textContent = `${recordCount} employees processed`;
                } else {
                    headerSubtitle.textContent = 'Loading employees...';
                }
            }
        }

        async function handlePeriodChange(event) {
            const selectedOption = event.target.selectedOptions[0];
            if (!selectedOption || !selectedOption.dataset.period) return;

            try {
                currentPeriod = JSON.parse(selectedOption.dataset.period);

                showNotification(`Switched to ${currentPeriod.name}`, 'info');

                // Update period display
                updateCurrentPeriodDisplay();

                // Reload payroll records for the new period
                await loadPayrollRecords(currentPeriod.id);

                // Refresh summary for the new period
                await refreshPayrollSummary();

            } catch (error) {
                console.error('Error changing period:', error);
                showNotification('Failed to change period: ' + error.message, 'error');
            }
        }

        function getStatusBadgeClass(status) {
            switch(status.toLowerCase()) {
                case 'approved':
                    return 'bg-green-100 text-green-800';
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800';
                case 'rejected':
                    return 'bg-red-100 text-red-800';
                case 'emailed':
                    return 'bg-blue-100 text-blue-800';
                case 'paid':
                    return 'bg-purple-100 text-purple-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        function getActionButtons(status, employeeId) {
            if (status.toLowerCase() === 'pending') {
                return `
                    <button onclick="approvePayrollRecord('${employeeId}')" class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50" title="Approve">
                        <i class="fas fa-check"></i>
                    </button>
                    <button onclick="rejectPayrollRecord('${employeeId}')" class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50" title="Reject">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (status.toLowerCase() === 'approved') {
                return `
                    <button onclick="markAsPaid('${employeeId}')" class="text-purple-600 hover:text-purple-800 p-1 rounded hover:bg-purple-50" title="Mark as Paid">
                        <i class="fas fa-money-bill"></i>
                    </button>
                `;
            }
            return '';
        }

        function updatePayrollTable(records) {
            const tbody = document.querySelector('tbody');
            if (!tbody) return;

            tbody.innerHTML = records.map(record => `
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-300 mr-3 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">${record.name}</div>
                                <div class="text-gray-500 text-xs">${record.employee_id}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">₱${parseFloat(record.basic_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.allowances || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.overtime || 0).toLocaleString()}</td>
                    <td class="px-6 py-4 font-bold text-gray-900">₱${parseFloat(record.gross_pay || 0).toLocaleString()}</td>
                    <td class="px-6 py-4 text-red-600">₱${parseFloat(record.deductions || 0).toLocaleString()}</td>
                    <td class="px-6 py-4 font-bold text-green-600">₱${parseFloat(record.net_pay || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">
                        <span class="${getStatusBadgeClass(record.status || 'Pending')} text-xs font-medium px-2.5 py-0.5 rounded">
                            ${record.status || 'Pending'}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <button onclick="viewPayslip('${record.employee_id}')" class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50" title="View Payslip">
                                <i class="fas fa-file-alt"></i>
                            </button>
                            <button onclick="editPayroll('${record.employee_id}')" class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50" title="Edit Salary">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="emailPayslip('${record.employee_id}')" class="text-purple-600 hover:text-purple-800 p-1 rounded hover:bg-purple-50" title="Email Payslip">
                                <i class="fas fa-envelope"></i>
                            </button>
                            ${getActionButtons(record.status || 'Pending', record.employee_id)}
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function approvePayrollRecord(employeeId) {
            try {
                const result = await payrollAPI.approvePayroll(1, employeeId);
                if (result.success) {
                    showNotification(result.message, 'success');
                    await loadPayrollRecords(currentPeriod?.id);
                }
            } catch (error) {
                showNotification('Failed to approve payroll: ' + error.message, 'error');
            }
        }

        async function viewPayslip(employeeId) {
            try {
                showNotification('Loading payslip...', 'info');
                const result = await payrollAPI.getEmployeePayroll(employeeId);
                if (result.success) {
                    const employee = result.data;
                    showPayslipModal(employee);
                    showNotification('Payslip loaded successfully', 'success');
                }
            } catch (error) {
                showNotification('Failed to load payslip: ' + error.message, 'error');
            }
        }

        function showPayslipModal(employee) {
            const payslipContent = document.getElementById('payslip-content');
            payslipContent.innerHTML = `
                <div class="bg-white border rounded-lg p-6">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">PAYSLIP</h2>
                        <p class="text-gray-600">Pay Period: March 2024</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Employee Information</h3>
                            <div class="space-y-1 text-sm">
                                <p><span class="font-medium">Name:</span> ${employee.name}</p>
                                <p><span class="font-medium">Employee ID:</span> ${employee.employee_id}</p>
                                <p><span class="font-medium">Department:</span> ${employee.department || 'N/A'}</p>
                                <p><span class="font-medium">Position:</span> ${employee.position_title || 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Pay Details</h3>
                            <div class="space-y-1 text-sm">
                                <p><span class="font-medium">Pay Date:</span> ${new Date().toLocaleDateString()}</p>
                                <p><span class="font-medium">Pay Method:</span> Bank Transfer</p>
                                <p><span class="font-medium">Currency:</span> PHP</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3 text-green-700">EARNINGS</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Basic Salary:</span>
                                    <span class="font-medium">₱${parseFloat(employee.basic_salary || 0).toLocaleString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Allowances:</span>
                                    <span class="font-medium">₱${parseFloat(employee.allowances || 0).toLocaleString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Overtime:</span>
                                    <span class="font-medium">₱${parseFloat(employee.overtime || 0).toLocaleString()}</span>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <div class="flex justify-between font-semibold text-green-700">
                                        <span>Total Earnings:</span>
                                        <span>₱${parseFloat(employee.gross_pay || 0).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3 text-red-700">DEDUCTIONS</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Income Tax:</span>
                                    <span class="font-medium">₱${(parseFloat(employee.deductions || 0) * 0.6).toLocaleString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>SSS:</span>
                                    <span class="font-medium">₱${(parseFloat(employee.deductions || 0) * 0.2).toLocaleString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>PhilHealth:</span>
                                    <span class="font-medium">₱${(parseFloat(employee.deductions || 0) * 0.1).toLocaleString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pag-IBIG:</span>
                                    <span class="font-medium">₱${(parseFloat(employee.deductions || 0) * 0.1).toLocaleString()}</span>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <div class="flex justify-between font-semibold text-red-700">
                                        <span>Total Deductions:</span>
                                        <span>₱${parseFloat(employee.deductions || 0).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t mt-6 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900">NET PAY:</span>
                            <span class="text-2xl font-bold text-blue-600">₱${parseFloat(employee.net_pay || 0).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            `;

            // Store employee data for printing
            window.currentPayslipData = employee;
            openModal('payslip-modal');
        }

        function printPayslip() {
            const payslipContent = document.getElementById('payslip-content').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payslip - ${window.currentPayslipData?.name || 'Employee'}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .grid { display: grid; }
                        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
                        .gap-6 { gap: 1.5rem; }
                        .mb-6 { margin-bottom: 1.5rem; }
                        .mb-3 { margin-bottom: 0.75rem; }
                        .mb-2 { margin-bottom: 0.5rem; }
                        .mt-6 { margin-top: 1.5rem; }
                        .pt-4 { padding-top: 1rem; }
                        .pt-2 { padding-top: 0.5rem; }
                        .mt-2 { margin-top: 0.5rem; }
                        .p-6 { padding: 1.5rem; }
                        .text-center { text-align: center; }
                        .text-2xl { font-size: 1.5rem; }
                        .text-xl { font-size: 1.25rem; }
                        .font-bold { font-weight: bold; }
                        .font-semibold { font-weight: 600; }
                        .font-medium { font-weight: 500; }
                        .text-sm { font-size: 0.875rem; }
                        .space-y-1 > * + * { margin-top: 0.25rem; }
                        .space-y-2 > * + * { margin-top: 0.5rem; }
                        .border { border: 1px solid #e5e7eb; }
                        .border-t { border-top: 1px solid #e5e7eb; }
                        .rounded-lg { border-radius: 0.5rem; }
                        .flex { display: flex; }
                        .justify-between { justify-content: space-between; }
                        .items-center { align-items: center; }
                        .text-green-700 { color: #15803d; }
                        .text-red-700 { color: #b91c1c; }
                        .text-blue-600 { color: #2563eb; }
                        .text-gray-900 { color: #111827; }
                        .text-gray-600 { color: #4b5563; }
                    </style>
                </head>
                <body>
                    ${payslipContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        async function editPayroll(employeeId) {
            console.log('Edit payroll called for employee:', employeeId);

            try {
                // Get employee data first
                const result = await payrollAPI.getEmployeePayroll(employeeId);
                if (result.success) {
                    const employee = result.data;
                    showEditSalaryModal(employee);
                } else {
                    showNotification('Failed to load employee data', 'error');
                }
            } catch (error) {
                console.error('Error loading employee data:', error);
                showNotification('Failed to load employee data: ' + error.message, 'error');
            }
        }

        function showEditSalaryModal(employee) {
            // Populate the modal with employee data
            document.getElementById('edit-employee-info').textContent = `${employee.name} (${employee.employee_id})`;
            document.getElementById('current-salary').textContent = `₱${parseFloat(employee.basic_salary || 0).toLocaleString()}`;
            document.getElementById('new-salary').value = '';
            document.getElementById('salary-reason').value = '';

            // Store employee ID for form submission
            window.currentEditingEmployeeId = employee.employee_id;

            openModal('edit-salary-modal');
        }

        async function handleEditSalarySubmit(event) {
            event.preventDefault();

            const newSalary = document.getElementById('new-salary').value;
            const reason = document.getElementById('salary-reason').value;
            const employeeId = window.currentEditingEmployeeId;

            if (!newSalary || !employeeId) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }

            if (isNaN(newSalary) || parseFloat(newSalary) <= 0) {
                showNotification('Please enter a valid salary amount', 'error');
                return;
            }

            try {
                showNotification('Updating payroll...', 'info');
                console.log('Updating salary for employee:', employeeId, 'New salary:', newSalary);

                const result = await payrollAPI.updatePayrollRecord(employeeId, {
                    basic_salary: parseFloat(newSalary),
                    reason: reason
                });

                console.log('Update result:', result);

                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal('edit-salary-modal');

                    // Force refresh with delay to ensure backend updates
                    setTimeout(async () => {
                        await Promise.all([
                            refreshPayrollSummary(),
                            loadPayrollRecords(currentPeriod?.id)
                        ]);
                    }, 500);
                } else {
                    showNotification('Update failed: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Edit payroll error:', error);
                showNotification('Failed to update payroll: ' + error.message, 'error');
            }
        }

        async function emailPayslip(employeeId) {
            try {
                showNotification('Sending payslip email...', 'info');

                // Get employee data first
                const result = await payrollAPI.getEmployeePayroll(employeeId);
                if (result.success) {
                    const employee = result.data;

                    // Simulate email sending with delay
                    await new Promise(resolve => setTimeout(resolve, 1000));

                    showNotification(`Payslip emailed successfully to ${employee.name} (${employee.email || 'company email'})`, 'success');

                    // Update the record status to show it was emailed
                    await payrollAPI.updatePayrollStatus(employeeId, 'Emailed');
                    await loadPayrollRecords(currentPeriod?.id);
                } else {
                    throw new Error('Employee not found');
                }
            } catch (error) {
                showNotification('Failed to email payslip: ' + error.message, 'error');
            }
        }

        async function approvePayrollRecord(employeeId) {
            showConfirmationModal(
                'Approve Payroll',
                `Are you sure you want to approve payroll for employee ${employeeId}?`,
                'Approve',
                async () => {
                    try {
                        showNotification('Approving payroll...', 'info');
                        const result = await payrollAPI.approvePayroll(1, employeeId);
                        if (result.success) {
                            showNotification(result.message, 'success');
                            // Refresh both summary and records
                            await Promise.all([
                                refreshPayrollSummary(),
                                loadPayrollRecords(currentPeriod?.id)
                            ]);
                        }
                    } catch (error) {
                        showNotification('Failed to approve payroll: ' + error.message, 'error');
                    }
                },
                'success'
            );
        }

        async function rejectPayrollRecord(employeeId) {
            try {
                // Get employee data first
                const result = await payrollAPI.getEmployeePayroll(employeeId);
                if (result.success) {
                    const employee = result.data;
                    showRejectionModal(employee);
                } else {
                    showNotification('Failed to load employee data', 'error');
                }
            } catch (error) {
                console.error('Error loading employee data:', error);
                showNotification('Failed to load employee data: ' + error.message, 'error');
            }
        }

        function showRejectionModal(employee) {
            // Populate the modal with employee data
            document.getElementById('rejection-employee-info').textContent = `${employee.name} (${employee.employee_id})`;
            document.getElementById('rejection-reason').value = '';

            // Store employee ID for form submission
            window.currentRejectingEmployeeId = employee.employee_id;

            openModal('rejection-modal');
        }

        async function handleRejectionSubmit(event) {
            event.preventDefault();

            const reason = document.getElementById('rejection-reason').value.trim();
            const employeeId = window.currentRejectingEmployeeId;

            if (!reason || !employeeId) {
                showNotification('Please provide a reason for rejection', 'error');
                return;
            }

            try {
                showNotification('Rejecting payroll...', 'info');

                // Use the PayrollAPI request method directly to include reason
                const result = await payrollAPI.request('status', 'PUT', {
                    employee_id: employeeId,
                    status: 'Rejected',
                    reason: reason
                });

                if (result.success) {
                    showNotification(`Payroll rejected for employee ${employeeId}. Reason: ${reason}`, 'success');
                    closeModal('rejection-modal');
                    await loadPayrollRecords(currentPeriod?.id);
                } else {
                    throw new Error(result.error || 'Failed to reject payroll');
                }
            } catch (error) {
                showNotification('Failed to reject payroll: ' + error.message, 'error');
            }
        }

        async function markAsPaid(employeeId) {
            showConfirmationModal(
                'Mark as Paid',
                'Mark this payroll as paid? This action cannot be undone.',
                'Mark as Paid',
                async () => {
                    try {
                        showNotification('Marking as paid...', 'info');
                        await payrollAPI.updatePayrollStatus(employeeId, 'Paid');
                        showNotification(`Payroll marked as paid for employee ${employeeId}`, 'success');
                        // Refresh both summary and records
                        await Promise.all([
                            refreshPayrollSummary(),
                            loadPayrollRecords(currentPeriod?.id)
                        ]);
                    } catch (error) {
                        showNotification('Failed to mark as paid: ' + error.message, 'error');
                    }
                }
            );
        }

        function showConfirmationModal(title, message, confirmText, onConfirm, buttonType = 'danger') {
            document.getElementById('confirmation-title').textContent = title;
            document.getElementById('confirmation-message').textContent = message;

            const confirmBtn = document.getElementById('confirmation-confirm-btn');
            confirmBtn.textContent = confirmText;

            // Update button styling based on action type
            confirmBtn.className = 'px-4 py-2 rounded-lg text-white ';
            if (buttonType === 'success') {
                confirmBtn.className += 'bg-green-600 hover:bg-green-700';
            } else if (buttonType === 'primary') {
                confirmBtn.className += 'bg-blue-600 hover:bg-blue-700';
            } else {
                confirmBtn.className += 'bg-red-600 hover:bg-red-700';
            }

            // Store the callback
            window.currentConfirmCallback = onConfirm;

            openModal('confirmation-modal');
        }

        function handleConfirmation() {
            if (window.currentConfirmCallback) {
                window.currentConfirmCallback();
                window.currentConfirmCallback = null;
            }
            closeModal('confirmation-modal');
        }

        // Form submission handler for new payroll period
        async function handleNewPeriodForm(event) {
            event.preventDefault();
            console.log('New period form submitted');

            const formData = new FormData(event.target);
            const periodData = {
                period_name: formData.get('period_name'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                pay_date: formData.get('pay_date')
            };

            console.log('Period data:', periodData);

            // Validate form data
            if (!periodData.period_name || !periodData.start_date || !periodData.end_date || !periodData.pay_date) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }

            // Validate date logic
            const startDate = new Date(periodData.start_date);
            const endDate = new Date(periodData.end_date);
            const payDate = new Date(periodData.pay_date);

            if (endDate <= startDate) {
                showNotification('End date must be after start date', 'error');
                return;
            }

            if (payDate <= endDate) {
                showNotification('Pay date should be after the end date', 'error');
                return;
            }

            // Disable submit button during processing
            const submitButton = event.target.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            showLoading(submitButton);

            try {
                showNotification('Creating payroll period...', 'info');
                const result = await payrollAPI.createPayrollPeriod(periodData);

                console.log('Create period result:', result);

                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal('new-period-modal');
                    event.target.reset();

                    // Refresh period selector if it exists
                    addNewPeriodToSelector(result.data);
                } else {
                    throw new Error(result.error || 'Failed to create period');
                }
            } catch (error) {
                console.error('Create period error:', error);
                showNotification('Failed to create period: ' + error.message, 'error');
            } finally {
                hideLoading(submitButton, originalText);
            }
        }

        function addNewPeriodToSelector(newPeriod) {
            // Add the new period to the allPeriods array
            if (newPeriod) {
                allPeriods.unshift(newPeriod); // Add to beginning of array
                currentPeriod = newPeriod; // Set as current period
            }

            // Update the main period selector
            const periodSelector = document.getElementById('period-selector');
            if (periodSelector && newPeriod) {
                const option = document.createElement('option');
                option.value = newPeriod.id;
                option.textContent = newPeriod.name;
                option.dataset.period = JSON.stringify(newPeriod);
                option.selected = true;

                periodSelector.insertBefore(option, periodSelector.firstChild);

                // Update current period display
                updateCurrentPeriodDisplay();

                // Reload data for the new period
                setTimeout(() => {
                    loadPayrollRecords(currentPeriod?.id);
                    refreshPayrollSummary();
                }, 100);
            }
        }

        // Test functions for Postman demonstration
        window.testPayrollAPI = {
            async testGetSummary() {
                console.log('Testing GET Summary...');
                const result = await payrollAPI.getPayrollSummary();
                console.log('Summary:', result);
                return result;
            },

            async testGetRecords() {
                console.log('Testing GET Records...');
                const result = await payrollAPI.getPayrollRecords();
                console.log('Records:', result);
                return result;
            },

            async testProcessPayroll() {
                console.log('Testing POST Process Payroll...');
                const result = await payrollAPI.processPayroll(1);
                console.log('Process Result:', result);
                return result;
            },

            async testCreatePeriod() {
                console.log('Testing POST Create Period...');
                const result = await payrollAPI.createPayrollPeriod({
                    period_name: 'Test Period',
                    start_date: '2024-04-01',
                    end_date: '2024-04-30',
                    pay_date: '2024-05-05'
                });
                console.log('Create Period Result:', result);
                return result;
            },

            async testUpdateRecord() {
                console.log('Testing PUT Update Record...');
                const result = await payrollAPI.updatePayrollRecord('EMP001', {
                    basic_salary: 60000
                });
                console.log('Update Result:', result);
                return result;
            }
        };

        // Additional utility functions
        async function refreshAllData(buttonElement = null) {
            let button = buttonElement;
            if (!button && event && event.target) {
                button = event.target;
            }

            const originalText = button ? button.innerHTML : '';
            if (button) showLoading(button);

            try {
                showNotification('Refreshing all data...', 'info');
                await Promise.all([
                    refreshPayrollSummary(),
                    loadPayrollRecords(currentPeriod?.id)
                ]);
                showNotification('Data refreshed successfully', 'success');
            } catch (error) {
                showNotification('Failed to refresh data: ' + error.message, 'error');
            } finally {
                if (button) hideLoading(button, originalText);
            }
        }

        async function exportPayrollData() {
            try {
                showNotification('Preparing export...', 'info');
                const result = await payrollAPI.getPayrollRecords(1000, 0); // Get all records
                if (result.success) {
                    const csvContent = generateCSV(result.data);
                    downloadCSV(csvContent, 'payroll_records.csv');
                    showNotification('Payroll data exported successfully', 'success');
                }
            } catch (error) {
                showNotification('Failed to export data: ' + error.message, 'error');
            }
        }

        function generateCSV(data) {
            const headers = ['Employee ID', 'Name', 'Department', 'Position', 'Basic Salary', 'Allowances', 'Overtime', 'Gross Pay', 'Deductions', 'Net Pay', 'Status'];
            const rows = data.map(record => [
                record.employee_id,
                record.name,
                record.department || 'N/A',
                record.position_title || 'N/A',
                record.basic_salary || 0,
                record.allowances || 0,
                record.overtime || 0,
                record.gross_pay || 0,
                record.deductions || 0,
                record.net_pay || 0,
                record.status || 'Pending'
            ]);

            return [headers, ...rows].map(row => row.join(',')).join('\n');
        }

        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function printPayrollReport() {
            showNotification('Opening print dialog...', 'info');
            window.print();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listener for new period form
            const newPeriodForm = document.getElementById('new-period-form');
            if (newPeriodForm) {
                newPeriodForm.addEventListener('submit', handleNewPeriodForm);
            }

            // Add event listener for edit salary form
            const editSalaryForm = document.getElementById('edit-salary-form');
            if (editSalaryForm) {
                editSalaryForm.addEventListener('submit', handleEditSalarySubmit);
            }

            // Add event listener for rejection form
            const rejectionForm = document.getElementById('rejection-form');
            if (rejectionForm) {
                rejectionForm.addEventListener('submit', handleRejectionSubmit);
            }

            // Add event listener for confirmation button
            const confirmationBtn = document.getElementById('confirmation-confirm-btn');
            if (confirmationBtn) {
                confirmationBtn.addEventListener('click', handleConfirmation);
            }

            // Add event listener for period selector
            const periodSelector = document.getElementById('period-selector');
            if (periodSelector) {
                periodSelector.addEventListener('change', handlePeriodChange);
            }

            // Override process payroll button
            const processButton = document.querySelector('button[type="submit"][name="action"]');
            if (processButton) {
                processButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    processPayrollAPI();
                });
            }

            // Load initial data
            initializePayrollPage();

            // Add auto-refresh every 30 seconds
            setInterval(() => {
                refreshPayrollSummary();
            }, 30000);
        });

        async function initializePayrollPage() {
            try {
                // First, try to load the current month's period
                const hasCurrentPeriod = await loadCurrentMonthPeriod();
                
                // Then load all periods for the dropdown
                await loadPayrollPeriods();

                // If we successfully loaded current month period, display it
                if (hasCurrentPeriod && currentPeriod) {
                    updateCurrentPeriodDisplay();
                    
                    // Show notification about loaded period
                    const periodName = currentPeriod.name || 'Current Period';
                    showNotification(`Showing payroll for: ${periodName}`, 'info');
                }

                // Load data for the current period
                await Promise.all([
                    refreshPayrollSummary(),
                    loadPayrollRecords(currentPeriod?.id)
                ]);
            } catch (error) {
                console.error('Failed to initialize payroll page:', error);
                showNotification('Failed to initialize payroll page', 'error');
            }
        }
    </script>
</body>
</html>