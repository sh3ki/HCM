<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Mock current settings data
$companySettings = [
    'company_name' => 'TechCorp Solutions',
    'company_email' => 'info@techcorp.com',
    'company_phone' => '+1 (555) 123-4567',
    'company_address' => '123 Business Street, Tech City, TC 12345',
    'company_website' => 'https://techcorp.com',
    'company_logo' => 'assets/logo.png',
    'timezone' => 'Asia/Manila',
    'currency' => 'PHP',
    'date_format' => 'Y-m-d',
    'time_format' => '24'
];

$systemSettings = [
    'maintenance_mode' => false,
    'user_registration' => true,
    'email_notifications' => true,
    'sms_notifications' => false,
    'backup_frequency' => 'daily',
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'password_expiry_days' => 90,
    'two_factor_auth' => false
];

$payrollSettings = [
    'pay_frequency' => 'monthly',
    'pay_day' => 'last_day',
    'overtime_rate' => 1.5,
    'holiday_rate' => 2.0,
    'late_deduction_rate' => 0.1,
    'tax_rate' => 12.0,
    'sss_rate' => 3.63,
    'philhealth_rate' => 1.25,
    'pagibig_rate' => 1.0
];

$leaveSettings = [
    'annual_leave_days' => 21,
    'sick_leave_days' => 10,
    'personal_leave_days' => 5,
    'maternity_leave_days' => 90,
    'paternity_leave_days' => 7,
    'emergency_leave_days' => 3,
    'auto_approve_threshold' => 1,
    'require_medical_cert' => true,
    'advance_leave_days' => 30
];

// Mock user roles
$userRoles = [
    ['id' => 1, 'name' => 'Super Admin', 'permissions' => ['all']],
    ['id' => 2, 'name' => 'HR Manager', 'permissions' => ['employees', 'payroll', 'leaves', 'reports']],
    ['id' => 3, 'name' => 'Department Head', 'permissions' => ['employees', 'attendance', 'leaves']],
    ['id' => 4, 'name' => 'Employee', 'permissions' => ['profile', 'attendance', 'leave_apply']]
];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_company':
                $success = "Company settings updated successfully!";
                break;
            case 'update_system':
                $success = "System settings updated successfully!";
                break;
            case 'update_payroll':
                $success = "Payroll settings updated successfully!";
                break;
            case 'update_leave':
                $success = "Leave settings updated successfully!";
                break;
            case 'backup_database':
                $success = "Database backup completed successfully!";
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
    <title>System Settings - HCM System</title>
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
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
                <p class="text-gray-600">Configure your HCM system preferences and settings</p>
            </div>

            <!-- Settings Navigation Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button class="settings-tab active border-primary text-primary py-2 px-1 border-b-2 font-medium text-sm" data-tab="company">
                            <i class="fas fa-building mr-2"></i>Company
                        </button>
                        <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="system">
                            <i class="fas fa-cog mr-2"></i>System
                        </button>
                        <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="payroll">
                            <i class="fas fa-money-bill-wave mr-2"></i>Payroll
                        </button>
                        <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="leave">
                            <i class="fas fa-calendar-times mr-2"></i>Leave
                        </button>
                        <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="users">
                            <i class="fas fa-users mr-2"></i>Users
                        </button>
                        <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="backup">
                            <i class="fas fa-database mr-2"></i>Backup
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Company Settings Tab -->
            <div id="company-tab" class="settings-content">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Company Information</h3>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_company">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                    <input type="text" name="company_name" value="<?php echo htmlspecialchars($companySettings['company_name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" name="company_email" value="<?php echo htmlspecialchars($companySettings['company_email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="company_phone" value="<?php echo htmlspecialchars($companySettings['company_phone']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                    <input type="url" name="company_website" value="<?php echo htmlspecialchars($companySettings['company_website']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Address</label>
                                    <textarea name="company_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($companySettings['company_address']); ?></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Logo</label>
                                    <input type="file" name="company_logo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <p class="text-xs text-gray-500 mt-1">Upload a new logo (JPG, PNG, max 2MB)</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                                        <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="Asia/Manila" <?php echo $companySettings['timezone'] == 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila</option>
                                            <option value="UTC" <?php echo $companySettings['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo $companySettings['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                        <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="PHP" <?php echo $companySettings['currency'] == 'PHP' ? 'selected' : ''; ?>>PHP - Philippine Peso</option>
                                            <option value="USD" <?php echo $companySettings['currency'] == 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                            <option value="EUR" <?php echo $companySettings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200 mt-6">
                            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Settings Tab -->
            <div id="system-tab" class="settings-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">System Configuration</h3>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_system">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Maintenance Mode</h4>
                                        <p class="text-sm text-gray-500">Put the system in maintenance mode</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="maintenance_mode" value="1" class="sr-only peer" <?php echo $systemSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">User Registration</h4>
                                        <p class="text-sm text-gray-500">Allow new user registration</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="user_registration" value="1" class="sr-only peer" <?php echo $systemSettings['user_registration'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Email Notifications</h4>
                                        <p class="text-sm text-gray-500">Send email notifications to users</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="email_notifications" value="1" class="sr-only peer" <?php echo $systemSettings['email_notifications'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Two-Factor Authentication</h4>
                                        <p class="text-sm text-gray-500">Enable 2FA for enhanced security</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="two_factor_auth" value="1" class="sr-only peer" <?php echo $systemSettings['two_factor_auth'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Session Timeout (minutes)</label>
                                    <input type="number" name="session_timeout" value="<?php echo $systemSettings['session_timeout']; ?>" min="5" max="120" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" value="<?php echo $systemSettings['max_login_attempts']; ?>" min="3" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Expiry (days)</label>
                                    <input type="number" name="password_expiry_days" value="<?php echo $systemSettings['password_expiry_days']; ?>" min="30" max="365" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Backup Frequency</label>
                                    <select name="backup_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="hourly" <?php echo $systemSettings['backup_frequency'] == 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                                        <option value="daily" <?php echo $systemSettings['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $systemSettings['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $systemSettings['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200 mt-6">
                            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payroll Settings Tab -->
            <div id="payroll-tab" class="settings-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Payroll Configuration</h3>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_payroll">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pay Frequency</label>
                                    <select name="pay_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="weekly" <?php echo $payrollSettings['pay_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="bi-weekly" <?php echo $payrollSettings['pay_frequency'] == 'bi-weekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                        <option value="monthly" <?php echo $payrollSettings['pay_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Overtime Rate (multiplier)</label>
                                    <input type="number" name="overtime_rate" value="<?php echo $payrollSettings['overtime_rate']; ?>" step="0.1" min="1" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Holiday Rate (multiplier)</label>
                                    <input type="number" name="holiday_rate" value="<?php echo $payrollSettings['holiday_rate']; ?>" step="0.1" min="1" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Late Deduction Rate (%)</label>
                                    <input type="number" name="late_deduction_rate" value="<?php echo $payrollSettings['late_deduction_rate']; ?>" step="0.01" min="0" max="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                                    <input type="number" name="tax_rate" value="<?php echo $payrollSettings['tax_rate']; ?>" step="0.01" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SSS Rate (%)</label>
                                    <input type="number" name="sss_rate" value="<?php echo $payrollSettings['sss_rate']; ?>" step="0.01" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PhilHealth Rate (%)</label>
                                    <input type="number" name="philhealth_rate" value="<?php echo $payrollSettings['philhealth_rate']; ?>" step="0.01" min="0" max="5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pag-IBIG Rate (%)</label>
                                    <input type="number" name="pagibig_rate" value="<?php echo $payrollSettings['pagibig_rate']; ?>" step="0.01" min="0" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200 mt-6">
                            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leave Settings Tab -->
            <div id="leave-tab" class="settings-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Leave Configuration</h3>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_leave">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Annual Leave Days</label>
                                    <input type="number" name="annual_leave_days" value="<?php echo $leaveSettings['annual_leave_days']; ?>" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sick Leave Days</label>
                                    <input type="number" name="sick_leave_days" value="<?php echo $leaveSettings['sick_leave_days']; ?>" min="0" max="30" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Personal Leave Days</label>
                                    <input type="number" name="personal_leave_days" value="<?php echo $leaveSettings['personal_leave_days']; ?>" min="0" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Leave Days</label>
                                    <input type="number" name="emergency_leave_days" value="<?php echo $leaveSettings['emergency_leave_days']; ?>" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Maternity Leave Days</label>
                                    <input type="number" name="maternity_leave_days" value="<?php echo $leaveSettings['maternity_leave_days']; ?>" min="60" max="120" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Paternity Leave Days</label>
                                    <input type="number" name="paternity_leave_days" value="<?php echo $leaveSettings['paternity_leave_days']; ?>" min="5" max="14" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Auto-approve Threshold (days)</label>
                                    <input type="number" name="auto_approve_threshold" value="<?php echo $leaveSettings['auto_approve_threshold']; ?>" min="0" max="5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <p class="text-xs text-gray-500 mt-1">Leave requests up to this many days will be auto-approved</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Advance Leave Request (days)</label>
                                    <input type="number" name="advance_leave_days" value="<?php echo $leaveSettings['advance_leave_days']; ?>" min="1" max="90" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <p class="text-xs text-gray-500 mt-1">Minimum days in advance for leave requests</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200 mt-6">
                            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Roles Tab -->
            <div id="users-tab" class="settings-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">User Roles & Permissions</h3>
                        <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors" onclick="openModal('add-role-modal')">
                            <i class="fas fa-plus mr-2"></i>Add Role
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Role Name</th>
                                    <th class="px-6 py-3">Permissions</th>
                                    <th class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRoles as $role): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($role['name']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $permissions = is_array($role['permissions']) ? $role['permissions'] : [$role['permissions']];
                                        foreach ($permissions as $permission): ?>
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($permission); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($role['name'] !== 'Super Admin'): ?>
                                            <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Backup Tab -->
            <div id="backup-tab" class="settings-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Database Backup & Restore</h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Create Backup</h4>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="backup_database">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Backup Type</label>
                                        <select name="backup_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="full">Full Database</option>
                                            <option value="structure">Structure Only</option>
                                            <option value="data">Data Only</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="w-full bg-success text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-database mr-2"></i>Create Backup
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Restore Database</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Backup File</label>
                                    <input type="file" name="backup_file" accept=".sql" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <button type="button" class="w-full bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" onclick="confirmRestore()">
                                    <i class="fas fa-upload mr-2"></i>Restore Database
                                </button>
                                <p class="text-xs text-red-600">⚠️ Warning: This will overwrite your current database</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Backups -->
                    <div class="mt-8">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Recent Backups</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">backup_2024-03-14_10-30.sql</p>
                                    <p class="text-sm text-gray-500">March 14, 2024 - 10:30 AM (Full Database)</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');

                // Remove active class from all tabs
                document.querySelectorAll('.settings-tab').forEach(t => {
                    t.classList.remove('active', 'border-primary', 'text-primary');
                    t.classList.add('border-transparent', 'text-gray-500');
                });

                // Add active class to clicked tab
                this.classList.add('active', 'border-primary', 'text-primary');
                this.classList.remove('border-transparent', 'text-gray-500');

                // Hide all content
                document.querySelectorAll('.settings-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Show selected content
                document.getElementById(tabName + '-tab').classList.remove('hidden');
            });
        });

        // Backup restore confirmation
        function confirmRestore() {
            if (confirm('Are you sure you want to restore the database? This will overwrite all current data and cannot be undone.')) {
                alert('Database restore initiated...');
                // Implement restore logic here
            }
        }
    </script>
</body>
</html>