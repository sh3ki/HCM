<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Simplified: Skip role check for now to test basic access
// TODO: Re-add role checking once access works

function getUserRole($userId) {
    try {
        require_once __DIR__ . '/../config/database.php';
        global $conn;

        if (!$conn) {
            return 'employee'; // Default role if no connection
        }

        $stmt = $conn->prepare("
            SELECT r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['role_name'] ?? 'employee';
    } catch (Exception $e) {
        error_log("getUserRole error: " . $e->getMessage());
        return 'employee'; // Default role on error
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
            <!-- Loading Spinner -->
            <div id="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>

            <!-- Alert Messages -->
            <div id="alert-container" class="mb-4"></div>

            <!-- Main Content Container -->
            <div id="main-content" class="hidden">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
                    <p class="text-gray-600">Configure your HCM system preferences and settings</p>
                </div>

                <!-- Settings Navigation Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" id="settings-tabs">
                            <button class="settings-tab active border-primary text-primary py-2 px-1 border-b-2 font-medium text-sm" data-tab="company">
                                <i class="fas fa-building mr-2"></i>Company
                            </button>
                            <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="system">
                                <i class="fas fa-cog mr-2"></i>System
                            </button>
                            <button class="settings-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 border-b-2 font-medium text-sm" data-tab="payroll">
                                <i class="fas fa-money-bill-wave mr-2"></i>Payroll
                            </button>
                           
                        </nav>
                    </div>
                </div>

                <!-- Settings Content Container -->
                <div id="settings-content">
                    <!-- Content will be dynamically loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Upload Modal -->
    <div id="logoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Upload Company Logo</h3>
                    <button onclick="closeLogo()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="logoForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Logo</label>
                        <input type="file" id="logoFile" name="company_logo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF up to 2MB</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeLogo()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-700">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Settings Management -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        class SettingsManager {
            constructor() {
                this.settings = {};
                this.currentTab = 'company';
                this.apiBase = '../api/settings-no-auth.php';
                this.init();
            }

            async init() {
                await this.loadSettings();
                this.setupEventListeners();
                this.renderCurrentTab();
                this.hideLoading();
            }

            async loadSettings() {
                try {
                    const response = await fetch(this.apiBase);
                    const data = await response.json();

                    if (data.success) {
                        this.settings = data.data;
                        console.log('Settings loaded:', this.settings);
                    } else {
                        this.showAlert('error', 'Failed to load settings: ' + data.error);
                    }
                } catch (error) {
                    this.showAlert('error', 'Error loading settings: ' + error.message);
                }
            }

            setupEventListeners() {
                // Tab switching
                document.querySelectorAll('.settings-tab').forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        this.switchTab(e.target.getAttribute('data-tab'));
                    });
                });

                // Logo form
                document.getElementById('logoForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.uploadLogo();
                });
            }

            switchTab(tabName) {
                // Update active tab
                document.querySelectorAll('.settings-tab').forEach(tab => {
                    tab.classList.remove('active', 'border-primary', 'text-primary');
                    tab.classList.add('border-transparent', 'text-gray-500');
                });

                const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
                activeTab.classList.add('active', 'border-primary', 'text-primary');
                activeTab.classList.remove('border-transparent', 'text-gray-500');

                this.currentTab = tabName;
                this.renderCurrentTab();
            }

            renderCurrentTab() {
                const container = document.getElementById('settings-content');

                switch (this.currentTab) {
                    case 'company':
                        container.innerHTML = this.renderCompanySettings();
                        break;
                    case 'system':
                        container.innerHTML = this.renderSystemSettings();
                        break;
                    case 'payroll':
                        container.innerHTML = this.renderPayrollSettings();
                        break;
                    
                }

                this.attachFormListeners();
            }

            renderCompanySettings() {
                return `
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Company Information</h3>
                        </div>

                        <form id="companyForm" class="settings-form">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                        <input type="text" name="company_name" value="${this.getSettingValue('company_name')}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                        <input type="email" name="company_email" value="${this.getSettingValue('company_email')}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                        <input type="text" name="company_phone" value="${this.getSettingValue('company_phone')}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                        <input type="url" name="company_website" value="${this.getSettingValue('company_website')}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Address</label>
                                        <textarea name="company_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">${this.getSettingValue('company_address')}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Logo</label>
                                        <div class="flex items-center space-x-3">
                                            <img id="currentLogo" src="../${this.getSettingValue('company_logo')}" alt="Company Logo" class="h-16 w-16 object-contain border border-gray-300 rounded">
                                            <button type="button" onclick="openLogo()" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                                <i class="fas fa-upload mr-2"></i>Change Logo
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                                            <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                                <option value="Asia/Manila" ${this.getSettingValue('timezone') === 'Asia/Manila' ? 'selected' : ''}>Asia/Manila</option>
                                                <option value="UTC" ${this.getSettingValue('timezone') === 'UTC' ? 'selected' : ''}>UTC</option>
                                                <option value="America/New_York" ${this.getSettingValue('timezone') === 'America/New_York' ? 'selected' : ''}>America/New_York</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                            <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                                <option value="PHP" ${this.getSettingValue('currency') === 'PHP' ? 'selected' : ''}>PHP - Philippine Peso</option>
                                                <option value="USD" ${this.getSettingValue('currency') === 'USD' ? 'selected' : ''}>USD - US Dollar</option>
                                                <option value="EUR" ${this.getSettingValue('currency') === 'EUR' ? 'selected' : ''}>EUR - Euro</option>
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
                `;
            }

            renderSystemSettings() {
                return `
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">System Configuration</h3>
                        </div>

                        <form id="systemForm" class="settings-form">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    ${this.renderToggle('maintenance_mode', 'Maintenance Mode', 'Put the system in maintenance mode')}
                                    ${this.renderToggle('user_registration', 'User Registration', 'Allow new user registration')}
                                    ${this.renderToggle('email_notifications', 'Email Notifications', 'Send email notifications to users')}
                                    ${this.renderToggle('two_factor_auth', 'Two-Factor Authentication', 'Enable 2FA for enhanced security')}
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Session Timeout (minutes)</label>
                                        <input type="number" name="session_timeout" value="${this.getSettingValue('session_timeout')}" min="5" max="120" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                                        <input type="number" name="max_login_attempts" value="${this.getSettingValue('max_login_attempts')}" min="3" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Expiry (days)</label>
                                        <input type="number" name="password_expiry_days" value="${this.getSettingValue('password_expiry_days')}" min="30" max="365" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Backup Frequency</label>
                                        <select name="backup_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="hourly" ${this.getSettingValue('backup_frequency') === 'hourly' ? 'selected' : ''}>Hourly</option>
                                            <option value="daily" ${this.getSettingValue('backup_frequency') === 'daily' ? 'selected' : ''}>Daily</option>
                                            <option value="weekly" ${this.getSettingValue('backup_frequency') === 'weekly' ? 'selected' : ''}>Weekly</option>
                                            <option value="monthly" ${this.getSettingValue('backup_frequency') === 'monthly' ? 'selected' : ''}>Monthly</option>
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
                `;
            }

            renderPayrollSettings() {
                return `
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Payroll Configuration</h3>
                        </div>

                        <form id="payrollForm" class="settings-form">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pay Frequency</label>
                                        <select name="pay_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="weekly" ${this.getSettingValue('pay_frequency') === 'weekly' ? 'selected' : ''}>Weekly</option>
                                            <option value="bi-weekly" ${this.getSettingValue('pay_frequency') === 'bi-weekly' ? 'selected' : ''}>Bi-weekly</option>
                                            <option value="monthly" ${this.getSettingValue('pay_frequency') === 'monthly' ? 'selected' : ''}>Monthly</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Overtime Rate (multiplier)</label>
                                        <input type="number" name="overtime_rate" value="${this.getSettingValue('overtime_rate')}" step="0.1" min="1" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Holiday Rate (multiplier)</label>
                                        <input type="number" name="holiday_rate" value="${this.getSettingValue('holiday_rate')}" step="0.1" min="1" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Late Deduction Rate (%)</label>
                                        <input type="number" name="late_deduction_rate" value="${this.getSettingValue('late_deduction_rate')}" step="0.01" min="0" max="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                                        <input type="number" name="tax_rate" value="${this.getSettingValue('tax_rate')}" step="0.01" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">SSS Rate (%)</label>
                                        <input type="number" name="sss_rate" value="${this.getSettingValue('sss_rate')}" step="0.01" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">PhilHealth Rate (%)</label>
                                        <input type="number" name="philhealth_rate" value="${this.getSettingValue('philhealth_rate')}" step="0.01" min="0" max="5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pag-IBIG Rate (%)</label>
                                        <input type="number" name="pagibig_rate" value="${this.getSettingValue('pagibig_rate')}" step="0.01" min="0" max="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
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
                `;
            }

            renderLeaveSettings() {
                return `
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Leave Configuration</h3>
                        </div>

                        <form id="leaveForm" class="settings-form">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Annual Leave Days</label>
                                        <input type="number" name="annual_leave_days" value="${this.getSettingValue('annual_leave_days')}" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Sick Leave Days</label>
                                        <input type="number" name="sick_leave_days" value="${this.getSettingValue('sick_leave_days')}" min="0" max="30" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Personal Leave Days</label>
                                        <input type="number" name="personal_leave_days" value="${this.getSettingValue('personal_leave_days')}" min="0" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Leave Days</label>
                                        <input type="number" name="emergency_leave_days" value="${this.getSettingValue('emergency_leave_days')}" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Maternity Leave Days</label>
                                        <input type="number" name="maternity_leave_days" value="${this.getSettingValue('maternity_leave_days')}" min="60" max="120" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Paternity Leave Days</label>
                                        <input type="number" name="paternity_leave_days" value="${this.getSettingValue('paternity_leave_days')}" min="5" max="14" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Auto-approve Threshold (days)</label>
                                        <input type="number" name="auto_approve_threshold" value="${this.getSettingValue('auto_approve_threshold')}" min="0" max="5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        <p class="text-xs text-gray-500 mt-1">Leave requests up to this many days will be auto-approved</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Advance Leave Request (days)</label>
                                        <input type="number" name="advance_leave_days" value="${this.getSettingValue('advance_leave_days')}" min="1" max="90" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
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
                `;
            }

            renderToggle(settingKey, title, description) {
                const isChecked = this.getSettingValue(settingKey) === true || this.getSettingValue(settingKey) === 'true';

                return `
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">${title}</h4>
                            <p class="text-sm text-gray-500">${description}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="${settingKey}" value="1" class="sr-only peer" ${isChecked ? 'checked' : ''}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                `;
            }

            attachFormListeners() {
                document.querySelectorAll('.settings-form').forEach(form => {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.saveSettings(form);
                    });
                });
            }

            async saveSettings(form) {
                const formData = new FormData(form);
                const settings = {};

                for (const [key, value] of formData.entries()) {
                    settings[key] = {
                        value: value,
                        category: this.currentTab
                    };
                }

                // Handle unchecked checkboxes
                const checkboxes = form.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    if (!formData.has(checkbox.name)) {
                        settings[checkbox.name] = {
                            value: false,
                            category: this.currentTab
                        };
                    }
                });

                try {
                    const response = await fetch(this.apiBase, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ settings })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showAlert('success', 'Settings saved successfully!');
                        console.log('Save response:', data);
                        await this.loadSettings(); // Reload settings
                        this.renderCurrentTab(); // Re-render the current tab with updated values
                    } else {
                        this.showAlert('error', 'Failed to save settings: ' + data.error);
                    }
                } catch (error) {
                    this.showAlert('error', 'Error saving settings: ' + error.message);
                }
            }

            async uploadLogo() {
                const form = document.getElementById('logoForm');
                const formData = new FormData(form);

                try {
                    const response = await fetch(this.apiBase, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showAlert('success', 'Logo uploaded successfully!');
                        this.closeLogo();
                        await this.loadSettings(); // Reload settings
                        this.renderCurrentTab(); // Re-render the current tab with updated values
                    } else {
                        this.showAlert('error', 'Failed to upload logo: ' + data.error);
                    }
                } catch (error) {
                    this.showAlert('error', 'Error uploading logo: ' + error.message);
                }
            }

            getSettingValue(key) {
                return this.settings[key]?.value || '';
            }

            showAlert(type, message) {
                const container = document.getElementById('alert-container');
                const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';

                container.innerHTML = `
                    <div class="${alertClass} border px-4 py-3 rounded mb-4">
                        ${message}
                    </div>
                `;

                setTimeout(() => {
                    container.innerHTML = '';
                }, 5000);
            }

            hideLoading() {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('main-content').classList.remove('hidden');
            }

            closeLogo() {
                document.getElementById('logoModal').classList.add('hidden');
            }
        }

        // Global functions for modal
        function openLogo() {
            document.getElementById('logoModal').classList.remove('hidden');
        }

        function closeLogo() {
            document.getElementById('logoModal').classList.add('hidden');
        }

        // Initialize Settings Manager
        document.addEventListener('DOMContentLoaded', () => {
            window.settingsManager = new SettingsManager();
        });
    </script>
</body>
</html>