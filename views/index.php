<?php
// Start output buffering to prevent header issues
ob_start();

// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// FORCE redirect for non-admin users
$roleId = intval($_SESSION['role_id'] ?? 1);
error_log("INDEX.PHP - Current role_id: " . $roleId);
error_log("INDEX.PHP - Session data: " . print_r($_SESSION, true));

if ($roleId !== 1) {
    error_log("INDEX.PHP - Redirecting role_id $roleId to employee_payslip.php");
    ob_end_clean();
    header('Location: employee_payslip.php');
    exit();
}
error_log("INDEX.PHP - Admin user (role_id 1) staying on dashboard");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HCM Dashboard - Human Capital Management</title>
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

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
            <div class="animate-spin h-12 w-12 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-gray-600" id="loadingMessage">Loading dashboard...</p>
            <p class="text-xs text-gray-500 mt-2" id="loadingTimeout" style="display:none;">This is taking longer than expected. Check your connection.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-4 sm:ml-64">
        <div class="p-4 rounded-lg mt-14">
            <!-- Notification Container -->
            <div id="notificationContainer" class="fixed top-20 right-4 z-40 space-y-2"></div>

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">HR Analytics Dashboard</h1>
                <p id="welcomeMessage" class="text-gray-600">Welcome back! Here's what's happening in your organization today.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Employees -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-users text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Employees</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalEmployees">0</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600 font-medium" id="employeeGrowth">0%</span>
                        <span class="text-gray-600 ml-1">vs last month</span>
                    </div>
                </div>

                <!-- Monthly Payroll -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-money-bill-wave text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Monthly Payroll</p>
                            <p class="text-2xl font-bold text-gray-900" id="monthlyPayroll">₱0</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600 font-medium" id="payrollGrowth">0%</span>
                        <span class="text-gray-600 ml-1">vs last month</span>
                    </div>
                </div>

               <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Compensation Amount</p>
                    <p class="text-2xl font-bold text-gray-900" id="totalCompensation">₱0</p>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                <span class="text-green-600 font-medium" id="compensationPlans">0</span>
                <span class="text-gray-600 ml-1">plans in progress</span>
            </div>
        </div>


                <!-- Benefits Enrolled -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Benefits Enrolled</p>
                            <p class="text-2xl font-bold text-gray-900" id="benefitsEnrolled">0%</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600 font-medium" id="benefitsGrowth">0/0</span>
                        <span class="text-gray-600 ml-1">enrollment rate</span>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Employee Growth Chart -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Employee Growth</h3>
                        <span id="chartDataIndicator" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">Loading...</span>
                    </div>
                    <div class="h-64 relative">
                        <div id="chartLoading" class="absolute inset-0 flex items-center justify-center">
                            <div class="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full"></div>
                        </div>
                        <canvas id="employeeGrowthChart" class="hidden"></canvas>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
                    <div id="recentActivities" class="space-y-4">
                        <!-- Loading skeleton -->
                        <div class="animate-pulse">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 bg-gray-200 rounded-full mr-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                                <div class="w-8 h-3 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                        <div class="animate-pulse">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 bg-gray-200 rounded-full mr-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                                </div>
                                <div class="w-8 h-3 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <a href="employees.php" class="p-4 text-center hover:bg-gray-50 rounded-lg transition-colors block">
                        <div class="p-3 bg-blue-100 rounded-lg inline-block mb-2">
                            <i class="fas fa-user-plus text-primary text-xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900">Manage Employees</p>
                    </a>

                    <a href="payroll.php" class="p-4 text-center hover:bg-gray-50 rounded-lg transition-colors block">
                        <div class="p-3 bg-green-100 rounded-lg inline-block mb-2">
                            <i class="fas fa-money-bill-wave text-success text-xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900">Process Payroll</p>
                    </a>

                  <a href="compensation.php" class="p-4 text-center hover:bg-gray-50 rounded-lg transition-colors block">
                        <div class="p-3 bg-green-100 rounded-lg inline-block mb-2">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900">Compensation Planning</p>
                    </a>

                    <a href="reports.php" class="p-4 text-center hover:bg-gray-50 rounded-lg transition-colors block">
                        <div class="p-3 bg-purple-100 rounded-lg inline-block mb-2">
                            <i class="fas fa-file-alt text-purple-600 text-xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900">Generate Report</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Client-Side Dashboard Manager
        class ClientDashboardManager {
            constructor() {
                this.apiBase = '../api/dashboard.php';
                this.profileApiBase = '../api/profile.php';
                this.dashboardData = null;
                this.chart = null;
                this.loadingTimeout = null;
                this.maxLoadTime = 10000; // 10 seconds max loading time
                this.init();
            }

            async init() {
                // Set a fallback timeout to hide loading screen
                this.loadingTimeout = setTimeout(() => {
                    console.warn('Loading timeout reached');
                    document.getElementById('loadingTimeout').style.display = 'block';
                    document.getElementById('loadingMessage').textContent = 'Failed to load dashboard';
                    // Force hide after additional 2 seconds
                    setTimeout(() => this.hideLoading(), 2000);
                }, this.maxLoadTime);

                try {
                    await this.loadUserInfo();
                    await this.loadDashboardStats();
                    await this.loadRecentActivities();
                    await this.loadChartData();
                } catch (error) {
                    console.error('Dashboard initialization error:', error);
                    this.showNotification('Failed to load dashboard. Please refresh the page.', 'error');
                } finally {
                    // Always hide loading, even if errors occurred
                    clearTimeout(this.loadingTimeout);
                    setTimeout(() => this.hideLoading(), 500);
                    this.setupAutoRefresh();
                }
            }

            async loadUserInfo() {
                try {
                    const response = await fetch(this.profileApiBase);
                    
                    if (!response.ok) {
                        console.error('Profile API error:', response.status);
                        return;
                    }
                    
                    const result = await response.json();

                    if (result.success) {
                        const userData = result.data;
                        const fullName = this.getFullName(userData.first_name, userData.middle_name, userData.last_name);
                        const displayName = fullName || userData.username || 'User';
                        const role = userData.role_name || 'User';

                        document.getElementById('welcomeMessage').textContent =
                            `Welcome back, ${displayName}! Here's what's happening in your organization today.`;
                    } else {
                        console.error('Profile load error:', result.error);
                        // Check if it's an authentication error
                        if (result.code === 'AUTH_REQUIRED' || result.code === 'INVALID_SESSION') {
                            // Redirect to login page
                            setTimeout(() => window.location.href = 'login.php', 1000);
                            return;
                        }
                    }
                } catch (error) {
                    console.error('Error loading user info:', error);
                    // Don't redirect immediately, let other calls try
                }
            }

            async loadDashboardStats() {
                try {
                    const response = await fetch(`${this.apiBase}?type=stats`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const result = await response.json();

                    if (result.success) {
                        this.dashboardData = result.data;
                        this.renderStats();
                    } else {
                        // Check if it's an authentication error
                        if (result.code === 'AUTH_REQUIRED' || result.code === 'INVALID_SESSION') {
                            // Redirect to login page
                            console.error('Authentication error:', result.error);
                            setTimeout(() => window.location.href = 'login.php', 1000);
                            return;
                        }
                        console.error('Dashboard stats error:', result.error);
                        this.showNotification(result.error || 'Failed to load dashboard statistics', 'error');
                        this.renderStatsError();
                    }
                } catch (error) {
                    console.error('Load dashboard stats error:', error);
                    this.showNotification('Failed to load dashboard statistics: ' + error.message, 'error');
                    this.renderStatsError();
                }
            }

            renderStats() {
                if (!this.dashboardData) return;

                const data = this.dashboardData;

                // Total Employees
                document.getElementById('totalEmployees').innerHTML = data.totalEmployees.toLocaleString();
                document.getElementById('employeeGrowth').innerHTML = `+${data.employeeGrowth}%`;

                // Monthly Payroll
                const payrollFormatted = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2
                }).format(data.monthlyPayroll);
                document.getElementById('monthlyPayroll').innerHTML = payrollFormatted;
                document.getElementById('payrollGrowth').innerHTML = `+${data.payrollGrowth}%`;


                // Benefits Enrolled
                document.getElementById('benefitsEnrolled').innerHTML = `${data.benefitsPercentage}%`;
                document.getElementById('benefitsGrowth').innerHTML = `${data.benefitsEnrolled}/${data.totalActiveEmployees}`;
            }

            renderStatsError() {
                // Show error state for stats cards
                const errorText = 'Error';
                document.getElementById('totalEmployees').innerHTML = errorText;
                document.getElementById('employeeGrowth').innerHTML = 'N/A';
                document.getElementById('monthlyPayroll').innerHTML = errorText;
                document.getElementById('payrollGrowth').innerHTML = 'N/A';
             
                document.getElementById('leavesChange').innerHTML = 'N/A';
                document.getElementById('benefitsEnrolled').innerHTML = errorText;
                document.getElementById('benefitsGrowth').innerHTML = 'N/A';
            }

            async loadRecentActivities() {
                try {
                    const response = await fetch(`${this.apiBase}?type=activities`);
                    const result = await response.json();

                    if (result.success && result.data && result.data.length > 0) {
                        this.renderActivities(result.data);
                    } else {
                        this.renderNoActivities();
                    }
                } catch (error) {
                    console.error('Load activities error:', error);
                    this.renderActivitiesError();
                }
            }

            renderActivities(activities) {
                const container = document.getElementById('recentActivities');

                const activitiesHTML = activities.map(activity => {
                    const colorClasses = {
                        'blue': 'bg-blue-100 text-primary',
                        'green': 'bg-green-100 text-success',
                        'yellow': 'bg-yellow-100 text-warning',
                        'gray': 'bg-gray-100 text-gray-600'
                    };

                    return `
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="p-2 ${colorClasses[activity.color] || colorClasses.gray} rounded-full mr-3">
                                <i class="fas fa-${activity.icon} text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">${activity.activity_description}</p>
                                <p class="text-xs text-gray-600">by ${activity.username || 'System'}</p>
                            </div>
                            <span class="text-xs text-gray-500">${activity.time_ago}</span>
                        </div>
                    `;
                }).join('');

                container.innerHTML = activitiesHTML;
            }

            renderNoActivities() {
                const container = document.getElementById('recentActivities');
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p>No recent activities</p>
                    </div>
                `;
            }

            renderActivitiesError() {
                const container = document.getElementById('recentActivities');
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Failed to load activities</p>
                    </div>
                `;
            }

            async loadChartData() {
                try {
                    const response = await fetch(`${this.apiBase}?type=chart`);
                    const result = await response.json();

                    let chartData = [];
                    let hasRealData = false;

                    if (result.success && result.data) {
                        chartData = result.data.monthly_data;
                        hasRealData = result.data.has_real_data;
                    }

                    // Fallback to sample data if no real data
                    if (!hasRealData || chartData.length === 0) {
                        chartData = this.generateSampleChartData();
                    }

                    this.createChart(chartData, hasRealData);
                } catch (error) {
                    console.error('Load chart data error:', error);
                    this.createChart(this.generateSampleChartData(), false);
                }
            }

            generateSampleChartData() {
                const data = [];
                const currentDate = new Date();

                for (let i = 5; i >= 0; i--) {
                    const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                    const monthName = date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });

                    data.push({
                        month_name: monthName,
                        total_employees: 200 + (i * 8) + Math.floor(Math.random() * 10)
                    });
                }

                return data;
            }

            createChart(chartData, hasRealData) {
                const ctx = document.getElementById('employeeGrowthChart').getContext('2d');

                // Extract labels and data
                const labels = chartData.map(item => item.month_name);
                const data = chartData.map(item => item.total_employees);

                // Update indicator
                const indicator = document.getElementById('chartDataIndicator');
                if (hasRealData) {
                    indicator.textContent = 'Real Data';
                    indicator.className = 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-600';
                } else {
                    indicator.textContent = 'Sample Data';
                    indicator.className = 'text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-600';
                }

                // Hide loading and show chart
                document.getElementById('chartLoading').classList.add('hidden');
                document.getElementById('employeeGrowthChart').classList.remove('hidden');

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Employees',
                            data: data,
                            borderColor: '#1b68ff',
                            backgroundColor: 'rgba(27, 104, 255, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#1b68ff',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#1b68ff',
                                borderWidth: 1
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
            }

            getFullName(firstName, middleName, lastName) {
                const parts = [firstName, middleName, lastName].filter(part => part && part.trim());
                return parts.join(' ');
            }

            setupAutoRefresh() {
                // Refresh dashboard every 5 minutes
                setInterval(() => {
                    this.loadDashboardStats();
                    this.loadRecentActivities();
                }, 300000);
            }

            showNotification(message, type = 'info') {
                const container = document.getElementById('notificationContainer');

                const notification = document.createElement('div');
                notification.className = `transform transition-all duration-300 translate-x-full`;

                const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' :
                                type === 'error' ? 'bg-red-100 border-red-400 text-red-700' :
                                'bg-blue-100 border-blue-400 text-blue-700';

                notification.innerHTML = `
                    <div class="p-4 rounded-lg border shadow-lg max-w-sm ${bgColor}">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                ${type === 'success' ?
                                    '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
                                    type === 'error' ?
                                    '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>' :
                                    '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
                                }
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">${message}</p>
                            </div>
                            <button class="ml-auto -mr-1 flex-shrink-0" onclick="this.parentElement.parentElement.parentElement.remove()">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;

                container.appendChild(notification);

                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 10);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }, 5000);
            }

            hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = 'none';
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new ClientDashboardManager();
        });

        async function fetchCompensationStats() {
    try {
        const res = await fetch('../api/compensation_planning.php?stats=1');
        const data = await res.json();

        document.getElementById('totalCompensation').textContent = 
            `₱${Number(data.total_amount).toLocaleString()}`;
        document.getElementById('compensationPlans').textContent = data.total_plans;
    } catch (err) {
        console.error("Error fetching stats:", err);
    }
}

// Run when page loads
fetchCompensationStats();
    </script>
</body>
</html>