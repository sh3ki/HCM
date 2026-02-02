<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';
requireAuth();

try {
    // Get real report summary data from database
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_employees FROM employees WHERE employment_status = 'Active'");
    $stmt->execute();
    $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_departments FROM departments");
    $stmt->execute();
    $total_departments = $stmt->fetch(PDO::FETCH_ASSOC)['total_departments'];

    // Calculate real attendance percentage
    $stmt = $pdo->prepare("
        SELECT
            (COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(*)) as avg_attendance
        FROM attendance_records
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $avg_attendance_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $avg_attendance = round($avg_attendance_result['avg_attendance'] ?? 0, 1);

    // Get total payroll
    $stmt = $pdo->prepare("
        SELECT SUM(ec.basic_salary) as total_salary
        FROM employee_compensation ec
        JOIN employees e ON ec.employee_id = e.id
        WHERE ec.is_active = 1 AND e.employment_status = 'Active'
    ");
    $stmt->execute();
    $total_salary = $stmt->fetch(PDO::FETCH_ASSOC)['total_salary'] ?? 0;
    $total_payroll = '₱' . number_format($total_salary / 1000000, 1) . 'M';

    $reportSummary = [
        'total_employees' => (int)$total_employees,
        'total_departments' => (int)$total_departments,
        'avg_attendance' => $avg_attendance,
        'total_payroll' => $total_payroll
    ];

    // Get real department data
    $stmt = $pdo->prepare("
        SELECT
            d.dept_name as name,
            COUNT(e.id) as employees,
            AVG(ec.basic_salary) as avg_salary,
            (
                SELECT
                    (COUNT(CASE WHEN ar.status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(*))
                FROM attendance_records ar
                JOIN employees e2 ON ar.employee_id = e2.id
                WHERE e2.department_id = d.id
                AND ar.attendance_date IS NOT NULL
            ) as attendance
        FROM departments d
        LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'Active'
        LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
        GROUP BY d.id, d.dept_name
        ORDER BY employees DESC
    ");
    $stmt->execute();
    $departmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data properly
    foreach ($departmentData as &$dept) {
        $dept['employees'] = (int)$dept['employees'];
        $dept['avg_salary'] = (int)$dept['avg_salary'];
        $dept['attendance'] = round($dept['attendance'] ?? 0, 1);
    }

    // Get real attendance trends for last 12 months
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(attendance_date, '%b') as month,
            (COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) * 100.0 / COUNT(*)) as attendance_rate
        FROM attendance_records
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY YEAR(attendance_date), MONTH(attendance_date), DATE_FORMAT(attendance_date, '%b')
        ORDER BY attendance_date
    ");
    $stmt->execute();
    $attendanceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendanceTrends = [];
    foreach ($attendanceResults as $result) {
        $attendanceTrends[$result['month']] = round($result['attendance_rate'], 1);
    }

    // Fill missing months with 0 or default values
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    foreach ($months as $month) {
        if (!isset($attendanceTrends[$month])) {
            $attendanceTrends[$month] = 0;
        }
    }

    // Get real leave statistics
    $stmt = $pdo->prepare("
        SELECT
            lt.leave_name,
            COUNT(el.id) as count
        FROM employee_leaves el
        LEFT JOIN leave_types lt ON el.leave_type_id = lt.id
        WHERE el.status = 'Approved'
        AND el.start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY el.leave_type_id, lt.leave_name
        ORDER BY count DESC
    ");
    $stmt->execute();
    $leaveResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $leaveStats = [];
    foreach ($leaveResults as $result) {
        $leaveStats[$result['leave_name']] = (int)$result['count'];
    }

    // Get real payroll breakdown
    $stmt = $pdo->prepare("
        SELECT
            SUM(ec.basic_salary) as basic_salary,
            SUM(ea.amount) as allowances,
            SUM(pr.overtime_pay) as overtime,
            (SUM(ec.basic_salary) * 0.05) as bonuses
        FROM employee_compensation ec
        JOIN employees e ON ec.employee_id = e.id
        LEFT JOIN employee_allowances ea ON e.id = ea.employee_id
        LEFT JOIN payroll_records pr ON e.id = pr.employee_id
        WHERE ec.is_active = 1 AND e.employment_status = 'Active'
    ");
    $stmt->execute();
    $payrollResult = $stmt->fetch(PDO::FETCH_ASSOC);

    $payrollBreakdown = [
        'Basic Salary' => (float)($payrollResult['basic_salary'] ?? 0),
        'Allowances' => (float)($payrollResult['allowances'] ?? 0),
        'Overtime' => (float)($payrollResult['overtime'] ?? 0),
        'Bonuses' => (float)($payrollResult['bonuses'] ?? 0)
    ];

} catch (Exception $e) {
    // Fallback to default values if database query fails
    $reportSummary = ['total_employees' => 0, 'total_departments' => 0, 'avg_attendance' => 0, 'total_payroll' => '₱0.0M'];
    $departmentData = [];
    $attendanceTrends = [];
    $leaveStats = [];
    $payrollBreakdown = ['Basic Salary' => 0, 'Allowances' => 0, 'Overtime' => 0, 'Bonuses' => 0];
    error_log("Reports page error: " . $e->getMessage());
}

// Available report types
$reportTypes = [
    [
        'id' => 'employee',
        'name' => 'Employee Report',
        'description' => 'Comprehensive employee data and statistics',
        'icon' => 'fas fa-users',
        'color' => 'blue'
    ],
    [
        'id' => 'attendance',
        'name' => 'Attendance Report',
        'description' => 'Employee attendance tracking and analysis',
        'icon' => 'fas fa-clock',
        'color' => 'green'
    ],
    [
        'id' => 'payroll',
        'name' => 'Payroll Report',
        'description' => 'Salary and compensation breakdown',
        'icon' => 'fas fa-money-bill-wave',
        'color' => 'yellow'
    ],
    [
        'id' => 'leave',
        'name' => 'Leave Report',
        'description' => 'Leave requests and balance analysis',
        'icon' => 'fas fa-calendar-times',
        'color' => 'purple'
    ],
    [
        'id' => 'department',
        'name' => 'Department Report',
        'description' => 'Department-wise performance metrics',
        'icon' => 'fas fa-building',
        'color' => 'red'
    ],
    [
        'id' => 'performance',
        'name' => 'Performance Report',
        'description' => 'Employee performance evaluation data',
        'icon' => 'fas fa-chart-line',
        'color' => 'indigo'
    ],
    [
        'id' => 'benefits',
        'name' => 'Benefits Report',
        'description' => 'Comprehensive benefits analysis and utilization data',
        'icon' => 'fas fa-shield-alt',
        'color' => 'emerald'
    ]
];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_report':
                $success = "Report generated successfully!";
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
    <title>Reports & Analytics - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
                    <p class="text-gray-600">Generate comprehensive reports and analyze HR data</p>
                </div>
                <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center" onclick="openModal('custom-report-modal')">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Custom Report
                </button>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-users text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Employees</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $reportSummary['total_employees']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-building text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Departments</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $reportSummary['total_departments']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-warning text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Avg Attendance</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $reportSummary['avg_attendance']; ?>%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Payroll</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $reportSummary['total_payroll']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Types Grid -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Reports</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($reportTypes as $report): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer" onclick="generateReport('<?php echo $report['id']; ?>')">
                        <div class="flex items-center mb-3">
                            <div class="p-2 bg-<?php echo $report['color']; ?>-100 rounded-lg">
                                <i class="<?php echo $report['icon']; ?> text-<?php echo $report['color']; ?>-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($report['name']); ?></h3>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mb-3"><?php echo htmlspecialchars($report['description']); ?></p>
                        <button class="text-<?php echo $report['color']; ?>-600 hover:text-<?php echo $report['color']; ?>-800 text-sm font-medium">
                            Generate Report <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Attendance Trends Chart -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Attendance Trends</h3>
                        <div class="flex space-x-2">
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <!-- Leave Distribution Chart -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Leave Distribution</h3>
                        <div class="flex space-x-2">
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="leaveChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Additional Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Payroll Breakdown Chart -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Payroll Breakdown</h3>
                        <div class="flex space-x-2">
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="payrollChart"></canvas>
                    </div>
                </div>

                <!-- Department Statistics -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Department Statistics</h3>
                        <div class="flex space-x-2">
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Department Data Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Department Performance</h3>
                        <p class="text-sm text-gray-600">Detailed breakdown by department</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="export-dept-btn" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            <i class="fas fa-download mr-1"></i>
                            Export
                        </button>
                        <button id="print-dept-btn" class="bg-primary text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-print mr-1"></i>
                            Print
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Department</th>
                                <th class="px-6 py-3">Employees</th>
                                <th class="px-6 py-3">Avg Salary</th>
                                <th class="px-6 py-3">Attendance Rate</th>
                                <th class="px-6 py-3">Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentData as $dept): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($dept['name']); ?></td>
                                <td class="px-6 py-4"><?php echo $dept['employees']; ?></td>
                                <td class="px-6 py-4">₱<?php echo number_format($dept['avg_salary']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="mr-2"><?php echo $dept['attendance']; ?>%</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $dept['attendance']; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="<?php echo $dept['attendance'] >= 95 ? 'bg-green-100 text-green-800' : ($dept['attendance'] >= 90 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?php echo $dept['attendance'] >= 95 ? 'Excellent' : ($dept['attendance'] >= 90 ? 'Good' : 'Needs Improvement'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Report Modal -->
    <div id="custom-report-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('custom-report-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_report">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Generate Custom Report</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('custom-report-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                                <select name="report_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">Select Report Type</option>
                                    <option value="employee">Employee Report</option>
                                    <option value="attendance">Attendance Report</option>
                                    <option value="payroll">Payroll Report</option>
                                    <option value="leave">Leave Report</option>
                                    <option value="department">Department Report</option>
                                    <option value="performance">Performance Report</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                                    <input type="date" name="from_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                                    <input type="date" name="to_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">All Departments</option>
                                    <option value="IT">IT Department</option>
                                    <option value="Finance">Finance</option>
                                    <option value="HR">HR</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Legal">Legal</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Output Format</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="format" value="pdf" class="mr-2" checked>
                                        <span class="text-sm">PDF</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="format" value="excel" class="mr-2">
                                        <span class="text-sm">Excel</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="format" value="csv" class="mr-2">
                                        <span class="text-sm">CSV</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('custom-report-modal')">Cancel</button>
                                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Generate Report</button>
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
        // API configuration
        const API_BASE_URL = '../api';
        let authToken = localStorage.getItem('auth_token');

        // API helper function
        async function apiCall(endpoint, options = {}) {
            const headers = {
                'Content-Type': 'application/json',
                ...(authToken && { 'Authorization': `Bearer ${authToken}` })
            };

            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers: { ...headers, ...options.headers }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }

            return data;
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            initializeEventListeners();

            // Check if we should auto-generate a specific report
            const urlParams = new URLSearchParams(window.location.search);
            const reportType = urlParams.get('report');
            if (reportType) {
                setTimeout(() => {
                    generateReport(reportType);
                }, 1000); // Wait for page to load
            }
        });

        function initializeEventListeners() {
            // Report type cards click handlers
            document.querySelectorAll('[onclick^="generateReport"]').forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    const onclick = this.getAttribute('onclick');
                    const reportType = onclick.match(/generateReport\('([^']+)'\)/)[1];
                    generateReport(reportType);
                });
            });

            // Department table export and print handlers
            const exportBtn = document.getElementById('export-dept-btn');
            const printBtn = document.getElementById('print-dept-btn');

            if (exportBtn) {
                exportBtn.addEventListener('click', exportDepartmentTable);
            }

            if (printBtn) {
                printBtn.addEventListener('click', printDepartmentTable);
            }
        }

        async function loadDashboardData() {
            try {
                // Load dashboard metrics
                await loadDashboardMetrics();

                // Load charts data
                await loadChartsData();

                // Load department performance table
                await loadDepartmentPerformance();

            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showNotification('Error loading dashboard data: ' + error.message, 'error');
            }
        }

        async function loadDashboardMetrics() {
            try {
                const response = await apiCall('/reports.php?type=dashboard_metrics');
                const metrics = response.data;

                // Update metric cards
                document.querySelector('[class*="text-2xl font-bold text-gray-900"]').textContent = metrics.total_employees;
                document.querySelectorAll('[class*="text-2xl font-bold text-gray-900"]')[1].textContent = metrics.total_departments;
                document.querySelectorAll('[class*="text-2xl font-bold text-gray-900"]')[2].textContent = metrics.avg_attendance + '%';
                document.querySelectorAll('[class*="text-2xl font-bold text-gray-900"]')[3].textContent = metrics.total_payroll;

            } catch (error) {
                console.error('Error loading dashboard metrics:', error);
            }
        }

        async function loadChartsData() {
            try {
                const response = await apiCall('/reports_no_auth.php?type=charts');
                const data = response.data;

                // Initialize charts with API data
                initializeCharts(data);

            } catch (error) {
                console.error('Error loading charts data:', error);
                // Fallback to mock data
                const fallbackData = {
                    attendance_trends: {
                        'Jan': 95.2, 'Feb': 94.8, 'Mar': 96.1, 'Apr': 93.7,
                        'May': 94.5, 'Jun': 95.8, 'Jul': 92.3, 'Aug': 94.1,
                        'Sep': 96.4, 'Oct': 95.7, 'Nov': 94.9, 'Dec': 95.3
                    },
                    leave_statistics: {
                        'Annual Leave': 45, 'Sick Leave': 28, 'Personal Leave': 15,
                        'Maternity Leave': 8, 'Emergency Leave': 12
                    },
                    payroll_breakdown: {
                        'Basic Salary': 6500000, 'Allowances': 1800000,
                        'Overtime': 950000, 'Bonuses': 720000
                    },
                    department_attendance: {
                        'IT Department': 96.5, 'Finance': 98.1, 'HR': 95.3,
                        'Marketing': 92.8, 'Operations': 93.7, 'Sales': 91.4,
                        'Legal': 97.2, 'Admin': 94.8
                    }
                };
                initializeCharts(fallbackData);
            }
        }

        async function loadDepartmentPerformance() {
            try {
                // Check if table already has data from server-side PHP
                const tbody = document.querySelector('tbody');
                const existingRows = tbody.querySelectorAll('tr');

                // If table already has data from PHP, don't override it
                if (existingRows.length > 0) {
                    console.log('Department table already loaded with server-side data, skipping API call');
                    return;
                }

                const response = await apiCall('/reports_no_auth.php?type=department');
                const data = response.data;

                // Update department performance table
                tbody.innerHTML = '';

                data.departments.forEach(dept => {
                    const row = document.createElement('tr');
                    row.className = 'bg-white border-b hover:bg-gray-50';

                    const performanceClass = dept.attendance_rate >= 95 ? 'bg-green-100 text-green-800' :
                                           (dept.attendance_rate >= 90 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    const performanceText = dept.attendance_rate >= 95 ? 'Excellent' :
                                          (dept.attendance_rate >= 90 ? 'Good' : 'Needs Improvement');

                    row.innerHTML = `
                        <td class="px-6 py-4 font-medium text-gray-900">${dept.department}</td>
                        <td class="px-6 py-4">${dept.employee_count}</td>
                        <td class="px-6 py-4">₱${Number(dept.avg_salary).toLocaleString()}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="mr-2">${dept.attendance_rate}%</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: ${dept.attendance_rate}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="${performanceClass} text-xs font-medium px-2.5 py-0.5 rounded">
                                ${performanceText}
                            </span>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

            } catch (error) {
                console.error('Error loading department performance:', error);
            }
        }

        function initializeCharts(data) {
            // Attendance Trends Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(data.attendance_trends),
                    datasets: [{
                        label: 'Attendance %',
                        data: Object.values(data.attendance_trends),
                        borderColor: '#1b68ff',
                        backgroundColor: 'rgba(27, 104, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 90,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Leave Distribution Chart
            const leaveCtx = document.getElementById('leaveChart').getContext('2d');
            new Chart(leaveCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data.leave_statistics),
                    datasets: [{
                        data: Object.values(data.leave_statistics),
                        backgroundColor: [
                            '#1b68ff',
                            '#dc3545',
                            '#6f42c1',
                            '#e91e63',
                            '#ff9800'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Payroll Breakdown Chart
            const payrollCtx = document.getElementById('payrollChart').getContext('2d');
            new Chart(payrollCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data.payroll_breakdown),
                    datasets: [{
                        label: 'Amount (₱)',
                        data: Object.values(data.payroll_breakdown),
                        backgroundColor: [
                            '#1b68ff',
                            '#3ad29f',
                            '#eea303',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    }
                }
            });

            // Department Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            new Chart(departmentCtx, {
                type: 'radar',
                data: {
                    labels: Object.keys(data.department_attendance),
                    datasets: [{
                        label: 'Attendance Rate',
                        data: Object.values(data.department_attendance),
                        borderColor: '#1b68ff',
                        backgroundColor: 'rgba(27, 104, 255, 0.2)',
                        pointBackgroundColor: '#1b68ff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            min: 85,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Report generation function with PDF download
        async function generateReport(reportType) {
            try {
                showNotification(`Generating ${reportType} report PDF...`, 'info');

                // Use the new PDF endpoint
                const url = `${API_BASE_URL}/reports_pdf.php?type=${reportType}`;

                // Create a temporary link to download the PDF
                const link = document.createElement('a');
                link.href = url;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showNotification(`${reportType} report PDF generated successfully!`, 'success');

            } catch (error) {
                console.error('Error generating report:', error);
                showNotification('Error generating report: ' + error.message, 'error');
            }
        }

        // Custom report generation
        async function generateCustomReport() {
            const form = document.querySelector('#custom-report-modal form');
            const formData = new FormData(form);

            const reportType = formData.get('report_type');
            const fromDate = formData.get('from_date');
            const toDate = formData.get('to_date');
            const department = formData.get('department');
            const format = formData.get('format');

            try {
                showNotification('Generating custom report PDF...', 'info');

                // Build URL with parameters
                let url = `${API_BASE_URL}/reports_pdf.php?type=${reportType}`;
                if (fromDate) url += `&from_date=${fromDate}`;
                if (toDate) url += `&to_date=${toDate}`;
                if (department) url += `&department_id=${department}`;

                // Create a temporary link to download the PDF
                const link = document.createElement('a');
                link.href = url;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                closeModal('custom-report-modal');
                showNotification('Custom report PDF generated successfully!', 'success');

            } catch (error) {
                console.error('Error generating custom report:', error);
                showNotification('Error generating custom report: ' + error.message, 'error');
            }
        }


        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-100 text-green-800' :
                type === 'error' ? 'bg-red-100 text-red-800' :
                type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                'bg-blue-100 text-blue-800'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                document.body.removeChild(notification);
            }, 5000);
        }

        // Department table export functionality
        function exportDepartmentTable() {
            try {
                // Get table data
                const table = document.querySelector('.overflow-x-auto table');
                const rows = table.querySelectorAll('tr');

                // Create CSV content
                let csvContent = '';

                // Add header row
                const headers = table.querySelectorAll('thead th');
                const headerRow = Array.from(headers).map(th => th.textContent.trim()).join(',');
                csvContent += headerRow + '\n';

                // Add data rows
                const dataRows = table.querySelectorAll('tbody tr');
                dataRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const rowData = Array.from(cells).map(cell => {
                        // Extract clean text content, removing HTML elements
                        let text = cell.textContent.trim();
                        // Remove percentage symbols and clean up text
                        text = text.replace(/[""]/g, '""'); // Escape quotes for CSV
                        return `"${text}"`;
                    });
                    csvContent += rowData.join(',') + '\n';
                });

                // Create and download file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `department_performance_${new Date().toISOString().slice(0, 10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showNotification('Department performance data exported successfully!', 'success');
            } catch (error) {
                console.error('Export error:', error);
                showNotification('Error exporting data: ' + error.message, 'error');
            }
        }

        // Department table print functionality
        function printDepartmentTable() {
            try {
                // Create a new window for printing
                const printWindow = window.open('', '_blank');

                // Get current date for the report
                const currentDate = new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                // Get table HTML
                const table = document.querySelector('.overflow-x-auto table');
                const tableHTML = table.outerHTML;

                // Create print-friendly HTML
                const printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Department Performance Report</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                            color: #333;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 2px solid #1b68ff;
                            padding-bottom: 20px;
                        }
                        .header h1 {
                            color: #1b68ff;
                            margin: 0;
                            font-size: 24px;
                        }
                        .header p {
                            margin: 5px 0 0 0;
                            color: #666;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th, td {
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: left;
                        }
                        th {
                            background-color: #f8f9fa;
                            font-weight: bold;
                            color: #333;
                        }
                        tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        .excellent {
                            background-color: #d4edda;
                            color: #155724;
                            padding: 2px 6px;
                            border-radius: 4px;
                            font-weight: bold;
                        }
                        .good {
                            background-color: #fff3cd;
                            color: #856404;
                            padding: 2px 6px;
                            border-radius: 4px;
                            font-weight: bold;
                        }
                        .needs-improvement {
                            background-color: #f8d7da;
                            color: #721c24;
                            padding: 2px 6px;
                            border-radius: 4px;
                            font-weight: bold;
                        }
                        .progress-bar {
                            display: none; /* Hide progress bars in print */
                        }
                        @media print {
                            body { margin: 0; }
                            .header { page-break-after: avoid; }
                            table { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>HCM System - Department Performance Report</h1>
                        <p>Generated on: ${currentDate}</p>
                        <p>Detailed breakdown by department</p>
                    </div>
                    ${tableHTML}
                </body>
                </html>`;

                // Write HTML to print window
                printWindow.document.write(printHTML);
                printWindow.document.close();

                // Wait for content to load then print
                printWindow.onload = function() {
                    printWindow.print();
                    printWindow.close();
                };

                showNotification('Opening print dialog...', 'info');
            } catch (error) {
                console.error('Print error:', error);
                showNotification('Error printing table: ' + error.message, 'error');
            }
        }

        // Update form submit handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#custom-report-modal form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    generateCustomReport();
                });
            }
        });
    </script>
</body>
</html>