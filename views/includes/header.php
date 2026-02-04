<?php
// Detect current page for context-aware search
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Check if current page is an employee-only page
$employee_pages = ['employee_payslip', 'employee_tax_deduction', 'employee_performance'];
$is_employee_page = in_array($current_page, $employee_pages);

$search_contexts = [
    'employees' => ['placeholder' => 'Search employees...', 'context' => 'employees'],
    'payroll' => ['placeholder' => 'Search employees in payroll...', 'context' => 'payroll'],
    'reports' => ['placeholder' => 'Search employees, departments in reports...', 'context' => 'reports'],
    'leaves' => ['placeholder' => 'Search employees, leave types...', 'context' => 'leaves'],
    'benefits' => ['placeholder' => 'Search employees in benefits...', 'context' => 'benefits'],
    'attendance' => ['placeholder' => 'Search employees in attendance...', 'context' => 'attendance'],
    'compensation' => ['placeholder' => 'Search employees in compensation...', 'context' => 'employees'],
    'dependents' => ['placeholder' => 'Search employees with dependents...', 'context' => 'employees']
];

$search_config = $search_contexts[$current_page] ?? ['placeholder' => 'Search employees, departments...', 'context' => 'global'];
?>
<nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start">
                <!-- Sidebar Toggle -->
                <button id="toggleSidebar" class="p-2 text-gray-600 rounded cursor-pointer lg:hidden hover:text-gray-900 hover:bg-gray-100">
                    <i class="fas fa-bars w-6 h-6"></i>
                </button>
                <!-- Logo -->
                <a href="<?php echo $is_employee_page ? 'employee_payslip.php' : 'index.php'; ?>" class="flex ml-2 md:mr-24">
                    <div class="h-8 w-8 bg-primary rounded flex items-center justify-center mr-3">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                    <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap text-gray-900">HCM System</span>
                </a>
            </div>

            <?php if (!$is_employee_page): ?>
            <!-- Search Bar (Only for admin/HR pages) -->
            <div class="hidden lg:flex items-center lg:ml-6">
                <div class="relative">
                    <!-- <input type="search" 
                           id="globalSearch" 
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary focus:border-primary block w-80 pl-10 p-2.5" 
                           placeholder="<?php echo htmlspecialchars($search_config['placeholder']); ?>" 
                           data-context="<?php echo htmlspecialchars($search_config['context']); ?>"
                           autocomplete="off"> -->
                    <!-- <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-500"></i>
                    </div> -->
                    <!-- Search Results Dropdown -->
                    <div id="searchResults" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden max-h-96 overflow-y-auto">
                        <div id="searchContent" class="p-2"></div>
                    </div>
                    <!-- Loading indicator -->
                    <div id="searchLoading" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden p-4 text-center">
                        <i class="fas fa-spinner fa-spin text-gray-500"></i>
                        <span class="ml-2 text-gray-500">Searching...</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Right Navigation -->
            <div class="flex items-center">
                <!-- User Menu -->
                <div class="flex items-center ml-3 relative">
                    <button class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" id="user-menu-button">
                        <img class="w-8 h-8 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="user photo">
                    </button>
                    <div class="z-50 hidden absolute right-0 top-full mt-2 w-48 bg-white divide-y divide-gray-100 rounded-md shadow-lg border border-gray-200" id="dropdown">
                        <div class="px-4 py-3">
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . (isset($_SESSION['first_name'], $_SESSION['last_name']) ? ' ' : '') . ($_SESSION['last_name'] ?? $_SESSION['username'] ?? 'User')); ?></p>
                            <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['employee_email'] ?? $_SESSION['email'] ?? $_SESSION['username'] . '@company.com'); ?></p>
                        </div>
                        <ul class="py-1">
                            <?php if (!$is_employee_page): ?>
                                <li><a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-user mr-2"></i>Profile</a></li>
                                <li><a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-cog mr-2"></i>Settings</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<?php if (!empty($_SESSION['is_new'])): ?>
<div id="otp-modal" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-70"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Email Verification Required</h2>
        <p class="text-sm text-gray-600 mb-4">We've sent a one-time code to your email. Enter it below to continue.</p>
        <div class="space-y-3">
            <input id="otp-input" type="text" maxlength="6" class="w-full border border-gray-300 rounded-lg p-3 text-center text-lg tracking-widest focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter OTP" />
            <div class="flex gap-2">
                <button id="otp-confirm" class="flex-1 bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Confirm OTP</button>
                <button id="otp-resend" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Resend OTP</button>
            </div>
            <a href="logout.php" class="block text-center text-red-600 hover:text-red-700 text-sm">Logout</a>
            <p id="otp-message" class="text-sm text-red-600"></p>
        </div>
    </div>
</div>
<?php endif; ?>