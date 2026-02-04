<?php
// Get current page to highlight active menu
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user role to determine which menu items to show
$currentUser = getCurrentUser();
$userRole = 'employee'; // default

if ($currentUser && isset($currentUser['id'])) {
    try {
        require_once __DIR__ . '/../../config/database.php';
        global $conn;

        if ($conn) {
            $stmt = $conn->prepare("
                SELECT r.role_name, r.permissions
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$currentUser['id']]);
            $result = $stmt->fetch();
            $userRole = strtolower($result['role_name'] ?? 'employee');
            $permissions = json_decode($result['permissions'] ?? '[]', true);
        }
    } catch (Exception $e) {
        error_log("Sidebar role check error: " . $e->getMessage());
    }
}

// Determine if user is employee (only has limited permissions)
$isEmployee = ($userRole === 'employee');
?>
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
        <ul class="space-y-2 font-medium">
            <?php if ($isEmployee): ?>
                <!-- Employee Menu Items -->
                <li>
                    <a href="employee_payslip.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'employee_payslip') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 h-5 <?php echo ($currentPage == 'employee_payslip') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">My Payslips</span>
                    </a>
                </li>

                <li>
                    <a href="employee_tax_deduction.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'employee_tax_deduction') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-file-invoice w-5 h-5 <?php echo ($currentPage == 'employee_tax_deduction') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Tax Deductions</span>
                    </a>
                </li>

                <li>
                    <a href="employee_performance.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'employee_performance') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-line w-5 h-5 <?php echo ($currentPage == 'employee_performance') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Performance History</span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Admin/Manager Menu Items -->
                <li>
                    <a href="index.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'index') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-line w-5 h-5 <?php echo ($currentPage == 'index') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">HR Analytics</span>
                    </a>
                </li>

                <li>
                    <a href="employees.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'employees') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-users w-5 h-5 <?php echo ($currentPage == 'employees') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Core HCM</span>
                    </a>
                </li>

                <li>
                    <a href="payroll.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'payroll') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-money-bill-wave w-5 h-5 <?php echo ($currentPage == 'payroll') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Payroll Management</span>
                    </a>
                </li>

                <li>
                    <a href="compensation.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'compensation') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-hand-holding-usd w-5 h-5 <?php echo ($currentPage == 'compensation') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Compensation Planning</span>
                    </a>
                </li>

                <li>
                    <a href="benefits.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'benefits') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-shield-alt w-5 h-5 <?php echo ($currentPage == 'benefits') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">HMO & Benefits</span>
                    </a>
                </li>

                <li>
                    <a href="reports.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'reports') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-bar w-5 h-5 <?php echo ($currentPage == 'reports') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Reports</span>
                    </a>
                </li>

                <li>
                    <a href="dependents.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'dependents') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-users-cog w-5 h-5 <?php echo ($currentPage == 'dependents') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Manage Dependents</span>
                    </a>
                </li>

                <?php 
                // Check if user is admin to show Admin Management link
                if ($currentUser && isset($currentUser['role']) && strtolower($currentUser['role']) === 'admin'): ?>
                <li>
                    <a href="admin.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'admin') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                        <i class="fas fa-user-shield w-5 h-5 <?php echo ($currentPage == 'admin') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                        <span class="ml-3">Admin Management</span>
                    </a>
                </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Settings -->
            <?php
            $currentUser = getCurrentUser();
            $showSettings = false;

            if ($currentUser && isset($currentUser['id'])) {
                try {
                    require_once __DIR__ . '/../../config/database.php';
                    global $conn;

                    if ($conn) {
                        $stmt = $conn->prepare("
                            SELECT r.role_name
                            FROM users u
                            LEFT JOIN roles r ON u.role_id = r.id
                            WHERE u.id = ?
                        ");
                        $stmt->execute([$currentUser['id']]);
                        $result = $stmt->fetch();
                        $userRole = $result['role_name'] ?? 'employee';

                        $showSettings = in_array(strtolower($userRole), ['admin', 'hr', 'super admin', 'hr manager', 'hr staff']);
                    }
                } catch (Exception $e) {
                    error_log("Settings menu error: " . $e->getMessage());
                    $showSettings = false;
                }
            }

            if ($showSettings): ?>
            <li>
                <a href="settings.php" class="flex items-center p-2 rounded-lg group <?php echo ($currentPage == 'settings') ? 'text-white bg-primary' : 'text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-cog w-5 h-5 <?php echo ($currentPage == 'settings') ? 'text-white' : 'text-gray-500 group-hover:text-gray-900'; ?>"></i>
                    <span class="ml-3">Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</aside>
