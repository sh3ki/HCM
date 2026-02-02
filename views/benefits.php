<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Get user session data
$user = getCurrentUser();

// Initialize data arrays - will be loaded via JavaScript
$benefitPlans = [];
$recentEnrollments = [];
$benefitsStats = [];
$employees = [];
$providers = [];

// Get message from URL parameters (for redirects)
$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benefits Management - HCM System</title>
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
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }

        .modal-overlay[style*="display: flex"] .modal-container {
            transform: scale(1);
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
            <!-- Page Header -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Benefits Management</h1>
                    <p class="text-gray-600">Manage employee benefits, insurance plans, and enrollment</p>
                </div>
                <button onclick="openModal('addPlanModal')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Benefit Plan
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div id="alert" class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <div class="flex justify-between items-center">
                    <span><?php echo htmlspecialchars($message); ?></span>
                    <button onclick="document.getElementById('alert').remove()" class="text-lg font-bold">&times;</button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-shield-alt text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Plans</p>
                            <p id="activePlansCount" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-users text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Enrollments</p>
                            <p id="totalEnrollments" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-warning text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending Enrollments</p>
                            <p id="pendingEnrollments" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-peso-sign text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Monthly Premium</p>
                            <p id="totalPremium" class="text-2xl font-bold text-gray-900">₱0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Benefit Plans -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Benefit Plans</h3>
                        </div>
                        <div class="p-6">
                            <div id="benefitPlansList" class="space-y-4">
                                <!-- Benefit plans will be loaded here via JavaScript -->
                                <div class="text-center py-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                                    <p class="text-gray-500">Loading benefit plans...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <button onclick="showEnrollmentModal()" class="w-full text-left p-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                        <i class="fas fa-user-plus text-primary text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Enroll Employee</p>
                                        <p class="text-sm text-gray-600">Add employee to benefit plan</p>
                                    </div>
                                </button>

                                <button onclick="generateReport()" class="w-full text-left p-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                                        <i class="fas fa-file-alt text-success text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Generate Report</p>
                                        <p class="text-sm text-gray-600">Benefits enrollment report</p>
                                    </div>
                                </button>

                                <button onclick="manageDependents()" class="w-full text-left p-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                        <i class="fas fa-users text-warning text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Manage Dependents</p>
                                        <p class="text-sm text-gray-600">Add/remove dependents</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Enrollments -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Enrollments</h3>
                        </div>
                        <div class="p-6">
                            <div id="recentEnrollmentsList" class="space-y-4">
                                <!-- Recent enrollments will be loaded here via JavaScript -->
                                <div class="text-center py-4">
                                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto mb-2"></div>
                                    <p class="text-gray-500 text-sm">Loading recent enrollments...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Benefit Plan Modal -->
    <div id="addPlanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[80vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Add Benefit Plan</h3>
                    <button onclick="closeModal('addPlanModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="add_plan">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name</label>
                        <input type="text" name="plan_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Type</label>
                        <select name="plan_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Select Type</option>
                            <option value="HMO">HMO</option>
                            <option value="Life Insurance">Life Insurance</option>
                            <option value="Government Health">Government Health</option>
                            <option value="Social Security">Social Security</option>
                            <option value="Housing Fund">Housing Fund</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                        <select id="providerSelect" name="provider_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Select Provider</option>
                            <!-- Providers will be loaded via JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Premium</label>
                        <input type="text" name="monthly_premium" placeholder="₱0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Limit</label>
                        <input type="text" name="coverage_limit" placeholder="₱0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addPlanModal')" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">Add Plan</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Enroll Employee Modal -->
    <div id="enrollEmployeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Enroll Employee</h3>
                    <button onclick="closeModal('enrollEmployeeModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="enroll_employee">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                        <select id="employeeSelect" name="employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Select Employee</option>
                            <!-- Employees will be loaded via JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Benefit Plan</label>
                        <select id="planSelect" name="plan_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Select Plan</option>
                            <!-- Plans will be loaded via JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                        <input type="date" name="effective_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('enrollEmployeeModal')" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">Enroll</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- View Plan Details Modal -->
    <div id="viewPlanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Plan Details</h3>
                    <button onclick="closeModal('viewPlanModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="planDetailsContent">
                    <!-- Plan details will be loaded here -->
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                        <p class="text-gray-500">Loading plan details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div id="editPlanModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-content">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Benefit Plan</h3>
                        <button onclick="closeModal('editPlanModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="editPlanForm" class="p-6">
                <input type="hidden" id="editPlanId" name="plan_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name</label>
                        <input type="text" id="editPlanName" name="plan_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Type</label>
                        <select id="editPlanType" name="plan_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Select Type</option>
                            <option value="Individual">Individual</option>
                            <option value="Family">Family</option>
                            <option value="Dependent">Dependent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Premium (₱)</label>
                        <input type="number" id="editMonthlyPremium" name="monthly_premium" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Amount (₱)</label>
                        <input type="number" id="editCoverageAmount" name="coverage_amount" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employer Contribution (₱)</label>
                        <input type="number" id="editEmployerContribution" name="employer_contribution" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee Contribution (₱)</label>
                        <input type="number" id="editEmployeeContribution" name="employee_contribution" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="editDescription" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editPlanModal')" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">Update Plan</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // API configuration
        const API_BASE = '../api';
        let currentUser = <?php echo json_encode($user); ?>;

        // Initialize page on load
        document.addEventListener('DOMContentLoaded', function() {
            loadBenefitsData();
            loadEmployees();
            loadProviders();
        });

        // Load all benefits data
        async function loadBenefitsData() {
            try {
                // Load benefits overview for stats and recent enrollments
                const overviewResponse = await fetch(`${API_BASE}/benefits.php`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!overviewResponse.ok) {
                    throw new Error('Failed to load benefits overview');
                }

                const overviewData = await overviewResponse.json();
                if (overviewData.success) {
                    updateStats(overviewData.data.stats);
                    displayRecentEnrollments(overviewData.data.recent_enrollments);
                }

                // Load full benefit plans data
                const plansResponse = await fetch(`${API_BASE}/benefits.php/plans`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!plansResponse.ok) {
                    throw new Error('Failed to load benefit plans');
                }

                const plansData = await plansResponse.json();
                if (plansData.success) {
                    displayBenefitPlans(plansData.data);
                }

            } catch (error) {
                console.error('Error loading benefits data:', error);
                showAlert('Failed to load benefits data', 'error');
            }
        }

        // Update statistics
        function updateStats(stats) {
            document.getElementById('activePlansCount').textContent = stats.active_plans;
            document.getElementById('totalEnrollments').textContent = stats.total_enrollments;
            document.getElementById('pendingEnrollments').textContent = stats.pending_enrollments;
            document.getElementById('totalPremium').textContent = '₱' + Number(stats.total_monthly_premium).toLocaleString();
        }

        // Display benefit plans
        function displayBenefitPlans(plans) {
            console.log('displayBenefitPlans called with:', plans); // Debug log
            const container = document.getElementById('benefitPlansList');

            if (!plans || plans.length === 0) {
                container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No benefit plans found</p></div>';
                return;
            }

            // Check if plans have IDs
            plans.forEach((plan, index) => {
                console.log(`Plan ${index}:`, {id: plan.id, name: plan.plan_name}); // Debug log
            });

            container.innerHTML = plans.map((plan, index) => {
                console.log(`Rendering plan ${index}:`, {id: plan.id, name: plan.plan_name}); // Debug log
                if (!plan.id) {
                    console.error(`Plan ${index} has no ID:`, plan);
                }
                return `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h4 class="text-lg font-semibold text-gray-900">${escapeHtml(plan.plan_name)}</h4>
                                <span class="ml-3 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">${escapeHtml(plan.description || 'No description available')}</p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Type:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.plan_type)}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Provider:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider_name || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Monthly Premium:</span>
                                    <span class="font-medium text-gray-900">₱${plan.monthly_premium}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Coverage:</span>
                                    <span class="font-medium text-gray-900">₱${plan.coverage_amount}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center">
                                <i class="fas fa-users text-gray-400 text-sm mr-1"></i>
                                <span class="text-sm text-gray-600">${plan.enrolled_count} employees enrolled</span>
                            </div>
                        </div>
                        <div class="ml-4 flex space-x-2">
                            <button onclick="console.log('Edit button clicked for plan ID:', ${plan.id}); editPlan(${plan.id})" class="p-2 text-gray-400 hover:text-primary transition-colors" title="Edit Plan">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="console.log('View button clicked for plan ID:', ${plan.id}); viewPlanDetails(${plan.id})" class="p-2 text-gray-400 hover:text-info transition-colors" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');

            // Populate plan select in enrollment modal
            const planSelect = document.getElementById('planSelect');
            planSelect.innerHTML = '<option value="">Select Plan</option>' +
                plans.map(plan => `<option value="${plan.id}">${escapeHtml(plan.plan_name)} - ₱${plan.monthly_premium}</option>`).join('');
        }

        // Display recent enrollments
        function displayRecentEnrollments(enrollments) {
            const container = document.getElementById('recentEnrollmentsList');

            if (!enrollments || enrollments.length === 0) {
                container.innerHTML = '<div class="text-center py-4"><p class="text-gray-500 text-sm">No recent enrollments</p></div>';
                return;
            }

            container.innerHTML = enrollments.map(enrollment => `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">${escapeHtml(enrollment.first_name)} ${escapeHtml(enrollment.last_name)}</p>
                        <p class="text-sm text-gray-600">${escapeHtml(enrollment.plan_name)}</p>
                        <p class="text-xs text-gray-500">${formatDate(enrollment.enrollment_date)}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusClass(enrollment.status)}">
                        ${escapeHtml(enrollment.status)}
                    </span>
                </div>
            `).join('');
        }

        // Load employees for enrollment
        async function loadEmployees() {
            try {
                const response = await fetch(`${API_BASE}/employees.php`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data && data.data.employees) {
                        const employeeSelect = document.getElementById('employeeSelect');
                        employeeSelect.innerHTML = '<option value="">Select Employee</option>' +
                            data.data.employees.map(emp => `<option value="${emp.id}">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)} - ${escapeHtml(emp.dept_name || 'No Department')}</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading employees:', error);
            }
        }

        // Load insurance providers
        async function loadProviders() {
            try {
                const response = await fetch(`${API_BASE}/benefits.php/providers`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const providerSelect = document.getElementById('providerSelect');
                        providerSelect.innerHTML = '<option value="">Select Provider</option>' +
                            data.data.map(provider => `<option value="${provider.id}">${escapeHtml(provider.provider_name)} (${escapeHtml(provider.provider_type)})</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading providers:', error);
            }
        }


        // Modal functions
        function showEditModal(modalId) {
            console.log('Opening edit modal');

            // Remove any existing dynamic modal
            const existingModal = document.getElementById('dynamicEditModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create dynamic modal with the edit form content
            const dynamicModal = document.createElement('div');
            dynamicModal.id = 'dynamicEditModal';
            dynamicModal.innerHTML = `
                <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);">
                    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #111827;">Edit Benefit Plan</h3>
                            <button onclick="closeDynamicEditModal()" style="color: #9ca3af; hover: #4b5563; background: none; border: none; font-size: 16px; cursor: pointer;">
                                ✕
                            </button>
                        </div>
                    </div>
                    <form id="dynamicEditPlanForm" style="padding: 24px;">
                        <input type="hidden" id="dynamicEditPlanId" name="plan_id">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Plan Name</label>
                                <input type="text" id="dynamicEditPlanName" name="plan_name" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Plan Type</label>
                                <select id="dynamicEditPlanType" name="plan_type" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                                    <option value="">Select Type</option>
                                    <option value="Individual">Individual</option>
                                    <option value="Family">Family</option>
                                    <option value="Dependent">Dependent</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Monthly Premium (₱)</label>
                                <input type="number" id="dynamicEditMonthlyPremium" name="monthly_premium" step="0.01" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Coverage Amount (₱)</label>
                                <input type="number" id="dynamicEditCoverageAmount" name="coverage_amount" step="0.01" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Employer Contribution (₱)</label>
                                <input type="number" id="dynamicEditEmployerContribution" name="employer_contribution" step="0.01" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Employee Contribution (₱)</label>
                                <input type="number" id="dynamicEditEmployeeContribution" name="employee_contribution" step="0.01" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Description</label>
                                <textarea id="dynamicEditDescription" name="description" rows="3" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;"></textarea>
                            </div>
                        </div>
                        <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" onclick="closeDynamicEditModal()" style="padding: 8px 16px; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer;">Cancel</button>
                            <button type="submit" style="padding: 8px 16px; background: #1b68ff; color: white; border-radius: 6px; border: none; cursor: pointer;">Update Plan</button>
                        </div>
                    </form>
                </div>
            `;

            // Apply dynamic modal styles with !important
            dynamicModal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0,0,0,0.5) !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 16px !important;
            `;

            // Add form submission handler
            document.body.appendChild(dynamicModal);

            // Add form submit handler
            document.getElementById('dynamicEditPlanForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                updatePlan(formData);
            });

            console.log('Dynamic edit modal created');
        }

        function closeDynamicEditModal() {
            const modal = document.getElementById('dynamicEditModal');
            if (modal) {
                modal.remove();
            }
        }

        // Create dynamic enrollment modal with searchable employee selection
        function showEnrollmentModal(planId = null) {
            console.log('Opening enrollment modal for plan:', planId);

            // Remove any existing dynamic modal
            const existingModal = document.getElementById('dynamicEnrollmentModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create dynamic modal
            const dynamicModal = document.createElement('div');
            dynamicModal.id = 'dynamicEnrollmentModal';
            dynamicModal.innerHTML = `
                <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);">
                    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #111827;">Enroll Employee in Benefits</h3>
                            <button onclick="closeDynamicEnrollmentModal()" style="color: #9ca3af; hover: #4b5563; background: none; border: none; font-size: 16px; cursor: pointer;">
                                ✕
                            </button>
                        </div>
                    </div>
                    <form id="dynamicEnrollmentForm" style="padding: 24px;">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Search Employee</label>
                                <div style="position: relative;">
                                    <input type="text" id="employeeSearch" placeholder="Type employee name or ID..."
                                           style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px;"
                                           autocomplete="off">
                                    <div id="employeeDropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;">
                                        <!-- Search results will appear here -->
                                    </div>
                                </div>
                                <input type="hidden" id="selectedEmployeeId" name="employee_id">
                                <div id="selectedEmployee" style="margin-top: 8px; padding: 8px; background: #f3f4f6; border-radius: 4px; display: none;">
                                    <span id="selectedEmployeeInfo"></span>
                                    <button type="button" onclick="clearSelectedEmployee()" style="float: right; background: none; border: none; color: #6b7280; cursor: pointer;">✕</button>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Benefit Plan</label>
                                <select id="dynamicPlanSelect" name="plan_id" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                                    <option value="">Select Plan</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Effective Date</label>
                                <input type="date" id="effectiveDate" name="effective_date"
                                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Coverage Start Date</label>
                                <input type="date" id="coverageStartDate" name="coverage_start_date"
                                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px;" required>
                            </div>
                        </div>
                        <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" onclick="closeDynamicEnrollmentModal()" style="padding: 8px 16px; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer;">Cancel</button>
                            <button type="submit" style="padding: 8px 16px; background: #10b981; color: white; border-radius: 6px; border: none; cursor: pointer;">Enroll Employee</button>
                        </div>
                    </form>
                </div>
            `;

            // Apply dynamic modal styles with !important
            dynamicModal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0,0,0,0.5) !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 16px !important;
            `;

            document.body.appendChild(dynamicModal);

            // Load plans and set up event handlers
            loadPlansForEnrollment(planId);
            setupEmployeeSearch();
            setupEnrollmentFormHandler();

            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('effectiveDate').value = today;
            document.getElementById('coverageStartDate').value = today;

            console.log('Dynamic enrollment modal created');
        }

        function closeDynamicEnrollmentModal() {
            const modal = document.getElementById('dynamicEnrollmentModal');
            if (modal) {
                modal.remove();
            }
        }

        // Load plans for enrollment dropdown
        async function loadPlansForEnrollment(selectedPlanId = null) {
            const planSelect = document.getElementById('dynamicPlanSelect');
            if (!planSelect) return;

            // Clear existing options except the first one
            planSelect.innerHTML = '<option value="">Select Plan</option>';

            try {
                const response = await fetch(`${API_BASE}/benefits.php/plans`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success && data.data) {
                    data.data.forEach(plan => {
                        const option = document.createElement('option');
                        option.value = plan.id;
                        option.textContent = `${plan.plan_name} (₱${parseFloat(plan.monthly_premium).toLocaleString()}/month)`;
                        if (plan.id == selectedPlanId) {
                            option.selected = true;
                        }
                        planSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading plans:', error);
                planSelect.innerHTML = '<option value="">Error loading plans</option>';
            }
        }

        // Set up employee search functionality
        function setupEmployeeSearch() {
            const searchInput = document.getElementById('employeeSearch');
            const dropdown = document.getElementById('employeeDropdown');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();

                clearTimeout(searchTimeout);

                if (query.length < 2) {
                    dropdown.style.display = 'none';
                    return;
                }

                // Debounce the search
                searchTimeout = setTimeout(() => {
                    searchEmployees(query);
                }, 300);
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#employeeSearch') && !e.target.closest('#employeeDropdown')) {
                    dropdown.style.display = 'none';
                }
            });
        }

        // Search employees via API
        async function searchEmployees(query) {
            const dropdown = document.getElementById('employeeDropdown');

            try {
                const response = await fetch(`${API_BASE}/employees.php?search=${encodeURIComponent(query)}&limit=10`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.data.employees) {
                    displayEmployeeSearchResults(data.data.employees);
                } else {
                    dropdown.innerHTML = '<div style="padding: 8px; color: #6b7280;">No employees found</div>';
                    dropdown.style.display = 'block';
                }
            } catch (error) {
                console.error('Error searching employees:', error);
                dropdown.innerHTML = '<div style="padding: 8px; color: #dc2626;">Error searching employees</div>';
                dropdown.style.display = 'block';
            }
        }

        // Display employee search results
        function displayEmployeeSearchResults(employees) {
            const dropdown = document.getElementById('employeeDropdown');

            if (employees.length === 0) {
                dropdown.innerHTML = '<div style="padding: 8px; color: #6b7280;">No employees found</div>';
            } else {
                dropdown.innerHTML = employees.map(employee => `
                    <div onclick="selectEmployee(${employee.id}, '${employee.first_name} ${employee.last_name}', '${employee.employee_id}', '${employee.department || 'N/A'}')"
                         style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f3f4f6; hover: background-color: #f9fafb;"
                         onmouseover="this.style.backgroundColor='#f9fafb'"
                         onmouseout="this.style.backgroundColor='white'">
                        <div style="font-weight: 500;">${employee.first_name} ${employee.last_name}</div>
                        <div style="font-size: 12px; color: #6b7280;">ID: ${employee.employee_id} • ${employee.department || 'No Department'}</div>
                    </div>
                `).join('');
            }

            dropdown.style.display = 'block';
        }

        // Select an employee from search results
        function selectEmployee(id, name, employeeId, department) {
            document.getElementById('selectedEmployeeId').value = id;
            document.getElementById('employeeSearch').value = '';
            document.getElementById('employeeDropdown').style.display = 'none';

            document.getElementById('selectedEmployeeInfo').textContent = `${name} (ID: ${employeeId}) - ${department}`;
            document.getElementById('selectedEmployee').style.display = 'block';
        }

        // Clear selected employee
        function clearSelectedEmployee() {
            document.getElementById('selectedEmployeeId').value = '';
            document.getElementById('selectedEmployee').style.display = 'none';
            document.getElementById('employeeSearch').focus();
        }

        // Set up enrollment form submission
        function setupEnrollmentFormHandler() {
            document.getElementById('dynamicEnrollmentForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(e.target);

                // Validate employee selection
                if (!formData.get('employee_id')) {
                    alert('Please select an employee');
                    return;
                }

                enrollEmployee(formData);
            });
        }

        function closeModal(modalId) {
            console.log('Closing modal:', modalId);
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error('Modal not found:', modalId);
                return;
            }
            modal.style.display = 'none';
        }

        // Create benefit plan
        async function createBenefitPlan(formData) {
            try {
                const response = await fetch(`${API_BASE}/benefits.php/plans`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('Benefit plan created successfully!', 'success');
                    closeModal('addPlanModal');
                    loadBenefitsData();
                } else {
                    showAlert(data.message || 'Failed to create benefit plan', 'error');
                }
            } catch (error) {
                console.error('Error creating benefit plan:', error);
                showAlert('Failed to create benefit plan', 'error');
            }
        }

        // Enroll employee
        async function enrollEmployee(formData) {
            try {
                // Convert FormData to plain object
                const enrollmentData = {
                    employee_id: formData.get('employee_id'),
                    insurance_plan_id: formData.get('plan_id'),
                    enrollment_date: formData.get('effective_date'),
                    effective_date: formData.get('coverage_start_date'),
                    status: 'Active',
                    employee_premium: 0,
                    employer_premium: 0,
                    dependents_count: 0,
                    beneficiary_info: JSON.stringify({
                        primary_beneficiary: {
                            name: '',
                            relationship: '',
                            percentage: 100
                        }
                    })
                };

                const response = await fetch(`${API_BASE}/benefits.php/enrollments`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(enrollmentData)
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('Employee enrolled successfully!', 'success');
                    closeDynamicEnrollmentModal();
                    loadBenefitsData();
                } else {
                    showAlert(data.error || 'Failed to enroll employee', 'error');
                }
            } catch (error) {
                console.error('Error enrolling employee:', error);
                showAlert('Failed to enroll employee', 'error');
            }
        }

        // View plan details
        async function viewPlanDetails(planId) {
            try {
                openModal('viewPlanModal');

                const response = await fetch(`${API_BASE}/benefits.php/plans/${planId}`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    displayPlanDetails(data.data);
                } else {
                    document.getElementById('planDetailsContent').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-500">Failed to load plan details: ${data.error}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading plan details:', error);
                document.getElementById('planDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-500">Error loading plan details</p>
                    </div>
                `;
            }
        }

        // Display plan details in modal
        function displayPlanDetails(plan) {
            const benefitsCoverage = plan.benefits_coverage ?
                (typeof plan.benefits_coverage === 'string' ?
                    JSON.parse(plan.benefits_coverage) : plan.benefits_coverage) : null;

            const benefitsHtml = benefitsCoverage && benefitsCoverage.benefits ?
                benefitsCoverage.benefits.map(benefit => `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2 mb-2">${benefit}</span>`).join('') :
                '<span class="text-gray-500">No specific benefits listed</span>';

            document.getElementById('planDetailsContent').innerHTML = `
                <div class="space-y-6">
                    <!-- Plan Overview -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">${escapeHtml(plan.plan_name)}</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Plan Code:</span>
                                <span class="font-medium text-gray-900">${escapeHtml(plan.plan_code)}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Plan Type:</span>
                                <span class="font-medium text-gray-900">${escapeHtml(plan.plan_type)}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">${escapeHtml(plan.status)}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Enrolled Employees:</span>
                                <span class="font-medium text-gray-900">${plan.enrolled_count}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Provider Information -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Provider Information</h5>
                        <div class="bg-white border rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Provider:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.name || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Type:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.type || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Contact Person:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.contact_person || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Email:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.contact_email || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Phone:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.contact_phone || 'N/A')}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Address:</span>
                                    <span class="font-medium text-gray-900">${escapeHtml(plan.provider.address || 'N/A')}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Financial Details</h5>
                        <div class="bg-white border rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Coverage Amount:</span>
                                    <span class="font-bold text-green-600">₱${plan.coverage_amount}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Monthly Premium:</span>
                                    <span class="font-bold text-blue-600">₱${plan.monthly_premium}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Employer Contribution:</span>
                                    <span class="font-medium text-gray-900">₱${plan.employer_contribution}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Employee Contribution:</span>
                                    <span class="font-medium text-gray-900">₱${plan.employee_contribution}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Dates -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Plan Dates</h5>
                        <div class="bg-white border rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Effective Date:</span>
                                    <span class="font-medium text-gray-900">${formatDate(plan.effective_date)}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Expiry Date:</span>
                                    <span class="font-medium text-gray-900">${plan.expiry_date ? formatDate(plan.expiry_date) : 'No expiry'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Description</h5>
                        <div class="bg-white border rounded-lg p-4">
                            <p class="text-gray-700">${escapeHtml(plan.description || 'No description available')}</p>
                        </div>
                    </div>

                    <!-- Benefits Coverage -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Benefits Coverage</h5>
                        <div class="bg-white border rounded-lg p-4">
                            ${benefitsHtml}
                        </div>
                    </div>

                    <!-- Enrolled Employees -->
                    <div>
                        <h5 class="text-md font-semibold text-gray-900 mb-3">Enrolled Employees (${plan.enrolled_count})</h5>
                        <div class="bg-white border rounded-lg">
                            ${plan.enrolled_employees && plan.enrolled_employees.length > 0 ? `
                                <div class="max-h-60 overflow-y-auto">
                                    <table class="min-w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrollment Date</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            ${plan.enrolled_employees.map(emp => `
                                                <tr>
                                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(emp.dept_name || 'N/A')}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">${formatDate(emp.enrollment_date)}</td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusClass(emp.status)}">${escapeHtml(emp.status)}</span>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            ` : `
                                <div class="p-4 text-center text-gray-500">
                                    No employees enrolled in this plan yet
                                </div>
                            `}
                        </div>
                    </div>
                </div>
            `;
        }

        // Edit plan
        async function editPlan(planId) {
            console.log('Edit plan called with ID:', planId); // Debug log
            try {
                // First fetch the plan details
                const response = await fetch(`${API_BASE}/benefits.php/plans/${planId}`, {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                console.log('Plan data received:', data); // Debug log
                if (data.success) {
                    console.log('Creating modal and populating form...'); // Debug log
                    showEditModal('editPlanModal');
                    // Populate form after modal is created
                    setTimeout(() => {
                        populateEditForm(data.data);
                    }, 100);
                } else {
                    showAlert('Failed to load plan details: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error loading plan for edit:', error);
                showAlert('Error loading plan details', 'error');
            }
        }

        // Populate edit form with plan data
        function populateEditForm(plan) {
            console.log('Populating edit form with plan:', plan);
            try {
                document.getElementById('dynamicEditPlanId').value = plan.id;
                document.getElementById('dynamicEditPlanName').value = plan.plan_name;
                document.getElementById('dynamicEditPlanType').value = plan.plan_type;
                document.getElementById('dynamicEditMonthlyPremium').value = parseFloat((plan.monthly_premium || '0').toString().replace(/,/g, ''));
                document.getElementById('dynamicEditCoverageAmount').value = parseFloat((plan.coverage_amount || '0').toString().replace(/,/g, ''));
                document.getElementById('dynamicEditEmployerContribution').value = parseFloat((plan.employer_contribution || '0').toString().replace(/,/g, ''));
                document.getElementById('dynamicEditEmployeeContribution').value = parseFloat((plan.employee_contribution || '0').toString().replace(/,/g, ''));
                document.getElementById('dynamicEditDescription').value = plan.description || '';

                console.log('Form populated successfully');
                return true;
            } catch (error) {
                console.error('Error populating form:', error);
                return false;
            }
        }

        // Update plan
        async function updatePlan(formData) {
            try {
                const planId = formData.get('plan_id');
                const updateData = {
                    plan_name: formData.get('plan_name'),
                    plan_type: formData.get('plan_type'),
                    monthly_premium: parseFloat(formData.get('monthly_premium')),
                    coverage_amount: parseFloat(formData.get('coverage_amount')),
                    employer_contribution: parseFloat(formData.get('employer_contribution')) || 0,
                    employee_contribution: parseFloat(formData.get('employee_contribution')) || 0,
                    description: formData.get('description')
                };

                const response = await fetch(`${API_BASE}/benefits.php/plans/${planId}`, {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(updateData)
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('Plan updated successfully!', 'success');
                    closeDynamicEditModal();
                    loadBenefitsData(); // Refresh the plan list
                } else {
                    showAlert(data.error || 'Failed to update plan', 'error');
                }
            } catch (error) {
                console.error('Error updating plan:', error);
                showAlert('Failed to update plan', 'error');
            }
        }

        function generateReport() {
            // Redirect to reports page with benefits report focus
            window.location.href = 'reports.php?report=benefits';
        }

        function manageDependents() {
            // Redirect to dependents management page
            window.location.href = 'dependents.php';
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function getStatusClass(status) {
            switch (status.toLowerCase()) {
                case 'active':
                case 'approved':
                    return 'bg-green-100 text-green-800';
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800';
                case 'cancelled':
                case 'inactive':
                    return 'bg-red-100 text-red-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg z-50 ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
                type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
                'bg-blue-100 border border-blue-400 text-blue-700'
            }`;
            alertDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-lg font-bold ml-4">&times;</button>
                </div>
            `;
            document.body.appendChild(alertDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Handle form submissions
        document.addEventListener('submit', function(e) {
            if (e.target.closest('#addPlanModal form')) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = {
                    provider_id: formData.get('provider_id'),
                    plan_code: formData.get('plan_name').replace(/\s+/g, '_').toUpperCase(),
                    plan_name: formData.get('plan_name'),
                    plan_type: formData.get('plan_type'),
                    monthly_premium: parseFloat(formData.get('monthly_premium').replace(/[^\d.]/g, '')),
                    coverage_amount: parseFloat(formData.get('coverage_limit').replace(/[^\d.]/g, '')),
                    effective_date: new Date().toISOString().split('T')[0]
                };
                createBenefitPlan(data);
            }

            if (e.target.closest('#enrollEmployeeModal form')) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = {
                    employee_id: parseInt(formData.get('employee_id')),
                    insurance_plan_id: parseInt(formData.get('plan_id')),
                    enrollment_date: new Date().toISOString().split('T')[0],
                    effective_date: formData.get('effective_date')
                };
                enrollEmployee(data);
            }

            if (e.target.closest('#editPlanForm')) {
                e.preventDefault();
                const formData = new FormData(e.target);
                updatePlan(formData);
            }
        });

        // Close modal when clicking outside
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
    </script>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>
</body>
</html>