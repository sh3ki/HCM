<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - HCM System</title>
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
            <!-- Alert Messages -->
            <div id="alert-container"></div>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Leave Management</h1>
                    <p class="text-gray-600">Manage employee leave requests and balances</p>
                </div>
                <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center" onclick="openModal('apply-leave-modal')">
                    <i class="fas fa-plus mr-2"></i>
                    Apply for Leave
                </button>
            </div>

            <!-- Leave Balance Cards -->
            <div id="leave-balance-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Loading placeholder -->
                <div class="col-span-full flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </div>

            <!-- Leave Summary Statistics -->
            <div id="leave-summary-stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-calendar-alt text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Requests</p>
                            <p id="total-requests" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-warning text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                            <p id="pending-requests" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Approved</p>
                            <p id="approved-requests" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-times-circle text-danger text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Rejected</p>
                            <p id="rejected-requests" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter and Search Bar -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="search" id="leave-search" class="w-full bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary focus:border-primary block pl-10 p-2.5" placeholder="Search by employee name or leave type...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-500"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <select id="leave-type-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Types</option>
                        </select>

                        <select id="status-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>

                        <button id="export-btn" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Leave Requests</h3>
                    <p id="requests-count" class="text-sm text-gray-600">Loading requests...</p>
                </div>

                <div class="overflow-x-auto">
                    <table id="leave-table" class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 cursor-pointer" data-sort="employee">Employee</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="leave_type">Leave Type</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="start_date">Start Date</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="end_date">End Date</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="days">Days</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="status">Status</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="applied_date">Applied Date</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leave-table-body">
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center">
                                    <div class="flex justify-center">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div id="pagination-info" class="text-sm text-gray-700">
                            Loading...
                        </div>
                        <div id="pagination-buttons" class="flex space-x-1">
                            <!-- Pagination buttons will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Leave Details Modal -->
    <div id="leave-details-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('leave-details-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Leave Request Details</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('leave-details-modal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="leave-details-content" class="space-y-6">
                        <div class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('leave-details-modal')">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Leave Modal -->
    <div id="reject-leave-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('reject-leave-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="reject-leave-form" onsubmit="submitLeaveRejection(event)">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Reject Leave Request</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('reject-leave-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <input type="hidden" id="reject-leave-id" name="leave_id" value="">

                            <!-- Leave Info Display -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800">Confirm Rejection</h4>
                                        <p class="text-sm text-red-700">You are about to reject this leave request. This action cannot be undone.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Leave Details -->
                            <div id="reject-leave-details" class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Leave Request Details</h4>
                                <div class="space-y-1 text-sm text-gray-600">
                                    <div class="flex justify-between">
                                        <span>Employee:</span>
                                        <span id="reject-employee-name" class="font-medium">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Leave Type:</span>
                                        <span id="reject-leave-type" class="font-medium">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Duration:</span>
                                        <span id="reject-leave-duration" class="font-medium">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Dates:</span>
                                        <span id="reject-leave-dates" class="font-medium">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection Reason -->
                            <div>
                                <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-1">
                                    Reason for Rejection <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="rejection-reason"
                                    name="reason"
                                    required
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Please provide a reason for rejecting this leave request..."
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">This reason will be visible to the employee.</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors" onclick="closeModal('reject-leave-modal')">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Reject Leave
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Apply Leave Modal -->
    <div id="apply-leave-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('apply-leave-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form id="apply-leave-form" onsubmit="submitLeaveApplication(event)">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Apply for Leave</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('apply-leave-modal')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Employee <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="hidden" id="employee-id-input" name="employee_id" value="" required>
                                    <input
                                        type="text"
                                        id="employee-search"
                                        placeholder="Search employee by name or ID..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                        autocomplete="off"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Employee Search Results -->
                                <div id="employee-search-results" class="hidden absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-auto">
                                    <!-- Results will be populated here -->
                                </div>

                                <!-- Selected Employee Display -->
                                <div id="selected-employee" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p id="selected-employee-name" class="text-sm font-medium text-gray-900"></p>
                                                <p id="selected-employee-details" class="text-xs text-gray-500"></p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="clearSelectedEmployee()" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                                <select id="leave-type-select" name="leave_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">Select Leave Type</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input type="date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <textarea name="reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Please provide a reason for your leave request"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contact person during leave (optional)">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supporting Documents</label>

                                <!-- File Upload Area -->
                                <div id="file-upload-area" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors cursor-pointer">
                                    <input type="file" id="documents-input" name="documents" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                                    <div id="upload-placeholder">
                                        <div class="mx-auto w-12 h-12 text-gray-400 mb-3">
                                            <i class="fas fa-cloud-upload-alt text-4xl"></i>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <span class="font-medium text-primary">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">PDF, DOC, DOCX, JPG, PNG up to 10MB each</p>
                                    </div>
                                </div>

                                <!-- File Preview List -->
                                <div id="file-preview-list" class="mt-3 space-y-2 hidden">
                                    <h4 class="text-sm font-medium text-gray-700">Selected Files:</h4>
                                    <div id="file-preview-container" class="space-y-2"></div>
                                </div>

                                <p class="text-xs text-gray-500 mt-2">Upload medical certificates, travel documents, etc. (optional)</p>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('apply-leave-modal')">Cancel</button>
                                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Submit Application</button>
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
        // Global variables
        let currentPage = 1;
        let currentFilters = {};
        let leaveTypes = [];
        let currentUserEmployeeId = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadLeaveTypes();
            loadLeaveBalance();
            loadLeaveRequests();
            getCurrentUserEmployeeId();
            setupEventListeners();
        });

        // Get current user's employee ID (not auto-setting since we have search now)
        async function getCurrentUserEmployeeId() {
            try {
                const response = await fetch('../api/employees.php');
                const result = await response.json();

                if (result.success && result.data.employees.length > 0) {
                    // Find current user's employee record
                    const currentUser = result.data.employees[0]; // Simplified - in real app, filter by current user
                    currentUserEmployeeId = currentUser.id;
                    // Don't auto-set anymore since we have employee search
                }
            } catch (error) {
                console.error('Error getting current user employee ID:', error);
            }
        }

        // Load leave types
        async function loadLeaveTypes() {
            try {
                const response = await fetch('../api/leaves.php?action=types');
                const result = await response.json();

                if (result.success) {
                    leaveTypes = result.data;
                    populateLeaveTypeSelects();
                }
            } catch (error) {
                console.error('Error loading leave types:', error);
            }
        }

        // Populate leave type select elements
        function populateLeaveTypeSelects() {
            const modalSelect = document.getElementById('leave-type-select');
            const filterSelect = document.getElementById('leave-type-filter');

            // Clear existing options (except first)
            modalSelect.innerHTML = '<option value="">Select Leave Type</option>';
            filterSelect.innerHTML = '<option value="">All Types</option>';

            leaveTypes.forEach(type => {
                modalSelect.innerHTML += `<option value="${type.id}">${type.leave_name} (${type.max_days_per_year} days max)</option>`;
                filterSelect.innerHTML += `<option value="${type.id}">${type.leave_name}</option>`;
            });
        }

        // Load leave balance
        async function loadLeaveBalance() {
            try {
                const response = await fetch('../api/leaves.php?action=balance');
                const result = await response.json();

                if (result.success) {
                    renderLeaveBalanceCards(result.data.balances);
                }
            } catch (error) {
                console.error('Error loading leave balance:', error);
                document.getElementById('leave-balance-cards').innerHTML =
                    '<div class="col-span-full text-center text-red-600">Error loading leave balance</div>';
            }
        }

        // Render leave balance cards
        function renderLeaveBalanceCards(balances) {
            const container = document.getElementById('leave-balance-cards');
            const colors = ['blue', 'green', 'purple', 'red', 'yellow', 'indigo'];

            container.innerHTML = balances.map((balance, index) => {
                const color = colors[index % colors.length];
                const percentage = balance.max_days_per_year > 0 ? (balance.remaining_days / balance.max_days_per_year) * 100 : 0;

                return `
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600">${balance.leave_name}</h3>
                            <div class="w-3 h-3 bg-${color}-500 rounded-full"></div>
                        </div>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-bold text-gray-900">${balance.remaining_days}</p>
                            <p class="text-sm text-gray-500 ml-1">/ ${balance.max_days_per_year} days</p>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-${color}-500 h-2 rounded-full" style="width: ${percentage}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">${balance.used_days} days used</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Load leave requests
        async function loadLeaveRequests(page = 1, filters = {}) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 25,
                    ...filters
                });

                const response = await fetch(`../api/leaves.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    renderLeaveRequestsTable(result.data.leaves);
                    renderPagination(result.data.pagination);
                    updateSummaryStats(result.data.leaves);
                    document.getElementById('requests-count').textContent =
                        `${result.data.pagination.total} requests found`;
                }
            } catch (error) {
                console.error('Error loading leave requests:', error);
                document.getElementById('leave-table-body').innerHTML =
                    '<tr><td colspan="9" class="px-6 py-8 text-center text-red-600">Error loading leave requests</td></tr>';
            }
        }

        // Render leave requests table
        function renderLeaveRequestsTable(leaves) {
            const tbody = document.getElementById('leave-table-body');

            if (leaves.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-gray-500">No leave requests found</td></tr>';
                return;
            }

            tbody.innerHTML = leaves.map(leave => {
                const statusClass = getStatusClass(leave.status);
                const canApproveReject = leave.status === 'Pending';
                const canCancel = leave.status === 'Approved' && new Date(leave.start_date) > new Date();

                return `
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-300 rounded-full mr-3 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${leave.employee_name}</div>
                                    <div class="text-gray-500">${leave.emp_id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                ${leave.leave_name}
                            </span>
                        </td>
                        <td class="px-6 py-4">${formatDate(leave.start_date)}</td>
                        <td class="px-6 py-4">${formatDate(leave.end_date)}</td>
                        <td class="px-6 py-4 font-medium">${leave.total_days} day${leave.total_days > 1 ? 's' : ''}</td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600" title="${leave.reason}">
                                ${leave.reason.length > 30 ? leave.reason.substring(0, 30) + '...' : leave.reason}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="${statusClass} text-xs font-medium px-2.5 py-0.5 rounded">
                                ${leave.status}
                            </span>
                        </td>
                        <td class="px-6 py-4">${formatDate(leave.applied_date)}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <button class="text-blue-600 hover:text-blue-800" title="View Details" onclick="viewLeaveDetails(${leave.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${canApproveReject ? `
                                    <button class="text-green-600 hover:text-green-800" title="Approve" onclick="approveLeave(${leave.id})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-800" title="Reject" onclick="rejectLeave(${leave.id})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                                ${canCancel ? `
                                    <button class="text-orange-600 hover:text-orange-800" title="Cancel" onclick="cancelLeave(${leave.id})">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Update summary statistics
        function updateSummaryStats(leaves) {
            const stats = leaves.reduce((acc, leave) => {
                acc.total++;
                acc[leave.status.toLowerCase()] = (acc[leave.status.toLowerCase()] || 0) + 1;
                return acc;
            }, { total: 0, pending: 0, approved: 0, rejected: 0, cancelled: 0 });

            document.getElementById('total-requests').textContent = stats.total;
            document.getElementById('pending-requests').textContent = stats.pending;
            document.getElementById('approved-requests').textContent = stats.approved;
            document.getElementById('rejected-requests').textContent = stats.rejected;
        }

        // Helper functions
        function getStatusClass(status) {
            switch (status) {
                case 'Approved': return 'bg-green-100 text-green-800';
                case 'Rejected': return 'bg-red-100 text-red-800';
                case 'Cancelled': return 'bg-gray-100 text-gray-800';
                default: return 'bg-yellow-100 text-yellow-800';
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Event listeners
        function setupEventListeners() {
            // Filter events
            document.getElementById('leave-type-filter').addEventListener('change', applyFilters);
            document.getElementById('status-filter').addEventListener('change', applyFilters);
            document.getElementById('leave-search').addEventListener('input', debounce(applyFilters, 300));

            // Form date validation
            document.querySelector('input[name="start_date"]').addEventListener('change', calculateLeaveDays);
            document.querySelector('input[name="end_date"]').addEventListener('change', calculateLeaveDays);

            // File upload events
            setupFileUpload();

            // Employee search events
            setupEmployeeSearch();
        }

        // File upload functionality
        function setupFileUpload() {
            const fileInput = document.getElementById('documents-input');
            const uploadArea = document.getElementById('file-upload-area');
            const previewList = document.getElementById('file-preview-list');
            const previewContainer = document.getElementById('file-preview-container');

            let selectedFiles = [];

            // File input change event
            fileInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });

            // Drag and drop events
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-primary', 'bg-blue-50');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-primary', 'bg-blue-50');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-primary', 'bg-blue-50');
                handleFiles(e.dataTransfer.files);
            });

            function handleFiles(files) {
                const newFiles = Array.from(files);
                const validFiles = [];

                newFiles.forEach(file => {
                    if (validateFile(file)) {
                        validFiles.push(file);
                    }
                });

                selectedFiles = [...selectedFiles, ...validFiles];
                updateFilePreview();
                updateFileInput();
            }

            function validateFile(file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];

                if (file.size > maxSize) {
                    showAlert(`File "${file.name}" is too large. Maximum size is 10MB.`, 'error');
                    return false;
                }

                if (!allowedTypes.includes(file.type)) {
                    showAlert(`File "${file.name}" is not a supported format. Please use PDF, DOC, DOCX, JPG, or PNG.`, 'error');
                    return false;
                }

                // Check for duplicates
                if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    showAlert(`File "${file.name}" is already selected.`, 'error');
                    return false;
                }

                return true;
            }

            function updateFilePreview() {
                if (selectedFiles.length === 0) {
                    previewList.classList.add('hidden');
                    return;
                }

                previewList.classList.remove('hidden');
                previewContainer.innerHTML = '';

                selectedFiles.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg border';

                    const fileIcon = getFileIcon(file.type);
                    const fileSize = formatFileSize(file.size);

                    fileItem.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <i class="${fileIcon} text-gray-500 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${file.name}</p>
                                <p class="text-xs text-gray-500">${fileSize}</p>
                            </div>
                        </div>
                        <button type="button" onclick="removeFile(${index})" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    `;

                    previewContainer.appendChild(fileItem);
                });
            }

            function updateFileInput() {
                try {
                    // Create a new DataTransfer object to update the file input
                    const dt = new DataTransfer();
                    selectedFiles.forEach(file => {
                        dt.items.add(file);
                    });
                    fileInput.files = dt.files;
                } catch (error) {
                    console.error('Error in updateFileInput:', error);
                }
            }

            // Global function to remove files
            window.removeFile = function(index) {
                selectedFiles.splice(index, 1);
                updateFilePreview();
                updateFileInput();
            };

            function getFileIcon(mimeType) {
                if (mimeType.includes('pdf')) return 'fas fa-file-pdf text-red-500';
                if (mimeType.includes('word') || mimeType.includes('document')) return 'fas fa-file-word text-blue-500';
                if (mimeType.includes('image')) return 'fas fa-file-image text-green-500';
                return 'fas fa-file text-gray-500';
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        }

        // Employee search functionality
        function setupEmployeeSearch() {
            const searchInput = document.getElementById('employee-search');
            const searchResults = document.getElementById('employee-search-results');
            const selectedEmployee = document.getElementById('selected-employee');
            let employees = [];
            let searchTimeout;

            // Load all employees for search
            loadEmployeesForSearch();

            async function loadEmployeesForSearch() {
                try {
                    const response = await fetch('../api/employees.php');
                    const result = await response.json();

                    if (result.success) {
                        employees = result.data.employees;
                    }
                } catch (error) {
                    console.error('Error loading employees:', error);
                }
            }

            // Search input event
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    searchEmployees(query);
                }, 300);
            });

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });

            function searchEmployees(query) {
                const filtered = employees.filter(emp => {
                    const name = `${emp.first_name} ${emp.last_name}`.toLowerCase();
                    const empId = emp.employee_id.toLowerCase();
                    const email = emp.email.toLowerCase();
                    const searchTerm = query.toLowerCase();

                    return name.includes(searchTerm) ||
                           empId.includes(searchTerm) ||
                           email.includes(searchTerm);
                }).slice(0, 10); // Limit to 10 results

                displaySearchResults(filtered);
            }

            function displaySearchResults(results) {
                if (results.length === 0) {
                    searchResults.innerHTML = '<div class="p-3 text-gray-500 text-sm">No employees found</div>';
                    searchResults.classList.remove('hidden');
                    return;
                }

                const resultsHtml = results.map(emp => `
                    <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick="selectEmployee(${emp.id}, '${emp.first_name} ${emp.last_name}', '${emp.employee_id}', '${emp.dept_name || 'N/A'}', '${emp.position_title || 'N/A'}')">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${emp.first_name} ${emp.last_name}</p>
                                <p class="text-xs text-gray-500">${emp.employee_id} • ${emp.dept_name || 'N/A'} • ${emp.position_title || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                `).join('');

                searchResults.innerHTML = resultsHtml;
                searchResults.classList.remove('hidden');
            }

            // Global function to select employee
            window.selectEmployee = function(id, name, empId, department, position) {
                document.getElementById('employee-id-input').value = id;
                document.getElementById('selected-employee-name').textContent = name;
                document.getElementById('selected-employee-details').textContent = `${empId} • ${department} • ${position}`;

                selectedEmployee.classList.remove('hidden');
                searchInput.style.display = 'none';
                searchResults.classList.add('hidden');
            };

            // Global function to clear selected employee
            window.clearSelectedEmployee = function() {
                document.getElementById('employee-id-input').value = '';
                document.getElementById('employee-search').value = '';
                selectedEmployee.classList.add('hidden');
                searchInput.style.display = 'block';
                searchResults.classList.add('hidden');
            };
        }

        // Apply filters
        function applyFilters() {
            const filters = {
                leave_type: document.getElementById('leave-type-filter').value,
                status: document.getElementById('status-filter').value,
                search: document.getElementById('leave-search').value
            };

            currentFilters = Object.fromEntries(
                Object.entries(filters).filter(([_, value]) => value !== '')
            );

            currentPage = 1;
            loadLeaveRequests(currentPage, currentFilters);
        }

        // Submit leave application
        async function submitLeaveApplication(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            // Validate employee selection
            if (!formData.get('employee_id')) {
                showAlert('Please select an employee for this leave application', 'error');
                return;
            }

            // Add emergency contact if provided
            const emergencyContact = formData.get('emergency_contact');
            if (emergencyContact) {
                formData.append('emergency_contact', emergencyContact);
            }


            try {
                const response = await fetch('../api/leaves.php', {
                    method: 'POST',
                    body: formData // Send FormData directly to handle files
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Leave application submitted successfully!', 'success');
                    closeModal('apply-leave-modal');
                    form.reset();
                    // Reset file upload
                    document.getElementById('file-preview-list').classList.add('hidden');
                    document.getElementById('file-preview-container').innerHTML = '';
                    // Reset employee selection
                    clearSelectedEmployee();
                    loadLeaveRequests();
                    loadLeaveBalance();
                } else {
                    showAlert(result.error || 'Failed to submit leave application', 'error');
                }
            } catch (error) {
                console.error('Error submitting leave application:', error);
                showAlert('Error submitting leave application', 'error');
            }
        }

        // Leave action functions
        async function approveLeave(leaveId) {
            if (!confirm('Are you sure you want to approve this leave request?')) return;

            try {
                const response = await fetch(`../api/leaves.php?id=${leaveId}&action=approve`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notes: '' })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Leave request approved successfully!', 'success');
                    loadLeaveRequests(currentPage, currentFilters);
                } else {
                    showAlert(result.error || 'Failed to approve leave request', 'error');
                }
            } catch (error) {
                console.error('Error approving leave:', error);
                showAlert('Error approving leave request', 'error');
            }
        }

        async function rejectLeave(leaveId) {
            try {
                // Find the leave data from the current table for display in modal
                const leaveRow = document.querySelector(`button[onclick="rejectLeave(${leaveId})"]`).closest('tr');
                const cells = leaveRow.querySelectorAll('td');

                // Extract leave data from the table row
                const employeeName = cells[0].querySelector('.font-medium').textContent;
                const leaveType = cells[1].querySelector('span').textContent;
                const startDate = cells[2].textContent;
                const endDate = cells[3].textContent;
                const duration = cells[4].textContent;

                // Populate modal with leave details
                document.getElementById('reject-leave-id').value = leaveId;
                document.getElementById('reject-employee-name').textContent = employeeName;
                document.getElementById('reject-leave-type').textContent = leaveType;
                document.getElementById('reject-leave-duration').textContent = duration;
                document.getElementById('reject-leave-dates').textContent = `${startDate} to ${endDate}`;

                // Clear previous reason and open modal
                document.getElementById('rejection-reason').value = '';
                openModal('reject-leave-modal');

            } catch (error) {
                console.error('Error preparing rejection modal:', error);
                showAlert('Error opening rejection modal', 'error');
            }
        }

        async function submitLeaveRejection(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const leaveId = formData.get('leave_id');
            const reason = formData.get('reason');

            if (!reason.trim()) {
                showAlert('Please provide a reason for rejection', 'error');
                return;
            }

            try {
                const response = await fetch(`../api/leaves.php?id=${leaveId}&action=reject`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notes: reason })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Leave request rejected successfully!', 'success');
                    closeModal('reject-leave-modal');
                    form.reset();
                    loadLeaveRequests(currentPage, currentFilters);
                } else {
                    showAlert(result.error || 'Failed to reject leave request', 'error');
                }
            } catch (error) {
                console.error('Error rejecting leave:', error);
                showAlert('Error rejecting leave request', 'error');
            }
        }

        async function cancelLeave(leaveId) {
            if (!confirm('Are you sure you want to cancel this leave request?')) return;

            try {
                const response = await fetch(`../api/leaves.php?id=${leaveId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Leave request cancelled successfully!', 'success');
                    loadLeaveRequests(currentPage, currentFilters);
                    loadLeaveBalance();
                } else {
                    showAlert(result.error || 'Failed to cancel leave request', 'error');
                }
            } catch (error) {
                console.error('Error cancelling leave:', error);
                showAlert('Error cancelling leave request', 'error');
            }
        }

        async function viewLeaveDetails(leaveId) {
            try {
                // Open modal and show loading
                openModal('leave-details-modal');

                // Fetch leave details
                const response = await fetch(`../api/leaves.php?id=${leaveId}`);
                const result = await response.json();

                if (result.success) {
                    renderLeaveDetails(result.data);
                } else {
                    document.getElementById('leave-details-content').innerHTML = `
                        <div class="text-center text-red-600 py-8">
                            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                            <p>Failed to load leave details</p>
                            <p class="text-sm">${result.error || 'Unknown error'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading leave details:', error);
                document.getElementById('leave-details-content').innerHTML = `
                    <div class="text-center text-red-600 py-8">
                        <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                        <p>Error loading leave details</p>
                        <p class="text-sm">Please try again later</p>
                    </div>
                `;
            }
        }

        function renderLeaveDetails(leave) {
            const statusClass = getStatusClass(leave.status);
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Employee Information</h4>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gray-300 rounded-full mr-3 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${leave.employee_name || 'N/A'}</div>
                                    <div class="text-gray-500 text-sm">${leave.emp_id || 'N/A'}</div>
                                    <div class="text-gray-500 text-sm">${leave.department_name || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Leave Details</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Leave Type:</span>
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                    ${leave.leave_name || 'N/A'}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="${statusClass} text-xs font-medium px-2.5 py-0.5 rounded">
                                    ${leave.status || 'N/A'}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Days:</span>
                                <span class="font-medium">${leave.total_days || 0} day${(leave.total_days || 0) > 1 ? 's' : ''}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Timeline</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-gray-600 text-sm">Start Date</div>
                            <div class="font-medium">${leave.start_date ? formatDate(leave.start_date) : 'N/A'}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-gray-600 text-sm">End Date</div>
                            <div class="font-medium">${leave.end_date ? formatDate(leave.end_date) : 'N/A'}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-gray-600 text-sm">Applied Date</div>
                            <div class="font-medium">${leave.applied_date ? formatDate(leave.applied_date) : 'N/A'}</div>
                        </div>
                    </div>
                </div>

                <!-- Reason -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Reason</h4>
                    <p class="text-gray-700">${leave.reason || 'No reason provided'}</p>
                </div>

                ${leave.emergency_contact ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Emergency Contact</h4>
                    <p class="text-gray-700">${leave.emergency_contact}</p>
                </div>
                ` : ''}

                ${leave.notes ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Notes</h4>
                    <p class="text-gray-700">${leave.notes}</p>
                </div>
                ` : ''}

                ${leave.documents && leave.documents.length > 0 ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Supporting Documents</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${leave.documents.map(doc => {
                            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(doc.file_name.split('.').pop().toLowerCase());
                            return `
                                <div class="bg-white p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                                    ${isImage ? `
                                        <div class="mb-3">
                                            <img
                                                src="../uploads/leave_documents/${doc.file_name}"
                                                alt="${doc.original_name || doc.file_name}"
                                                class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                                                onclick="previewImage('../uploads/leave_documents/${doc.file_name}', '${doc.original_name || doc.file_name}')"
                                            >
                                        </div>
                                    ` : `
                                        <div class="mb-3 flex justify-center">
                                            <div class="w-16 h-16 flex items-center justify-center bg-gray-100 rounded-lg">
                                                <i class="${getDocumentIcon(doc.file_name)} text-2xl"></i>
                                            </div>
                                        </div>
                                    `}
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-900 mb-1 truncate" title="${doc.original_name || doc.file_name}">
                                            ${doc.original_name || doc.file_name}
                                        </p>
                                        <p class="text-xs text-gray-500 mb-2">
                                            ${formatFileSize(doc.file_size)} • ${formatDate(doc.uploaded_at)}
                                        </p>
                                        <div class="flex justify-center space-x-2">
                                            <a
                                                href="../uploads/leave_documents/${doc.file_name}"
                                                target="_blank"
                                                class="text-primary hover:text-blue-700 text-sm flex items-center"
                                                title="Download"
                                            >
                                                <i class="fas fa-download mr-1"></i>
                                                Download
                                            </a>
                                            ${isImage ? `
                                                <button
                                                    onclick="previewImage('../uploads/leave_documents/${doc.file_name}', '${doc.original_name || doc.file_name}')"
                                                    class="text-green-600 hover:text-green-700 text-sm flex items-center"
                                                    title="Preview"
                                                >
                                                    <i class="fas fa-eye mr-1"></i>
                                                    Preview
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                ` : ''}

                ${(leave.approved_by_name && leave.status === 'Approved') || (leave.rejected_by_name && leave.status === 'Rejected') ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Action History</h4>
                    <div class="space-y-2">
                        ${leave.approved_by_name && leave.status === 'Approved' ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600">Approved by:</span>
                            <span class="font-medium">${leave.approved_by_name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Approved on:</span>
                            <span class="font-medium">${leave.approved_date ? formatDate(leave.approved_date) : 'N/A'}</span>
                        </div>
                        ` : ''}
                        ${leave.rejected_by_name && leave.status === 'Rejected' ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rejected by:</span>
                            <span class="font-medium">${leave.rejected_by_name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rejected on:</span>
                            <span class="font-medium">${leave.approved_date ? formatDate(leave.approved_date) : 'N/A'}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            `;

            document.getElementById('leave-details-content').innerHTML = content;
        }

        function getDocumentIcon(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            switch (extension) {
                case 'pdf': return 'fas fa-file-pdf text-red-500';
                case 'doc':
                case 'docx': return 'fas fa-file-word text-blue-500';
                case 'jpg':
                case 'jpeg':
                case 'png': return 'fas fa-file-image text-green-500';
                default: return 'fas fa-file text-gray-500';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function previewImage(imageSrc, imageName) {
            // Create modal for image preview
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75';
            modal.onclick = function() { modal.remove(); };

            modal.innerHTML = `
                <div class="max-w-4xl max-h-screen p-4" onclick="event.stopPropagation()">
                    <div class="bg-white rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-lg font-medium text-gray-900">${imageName}</h3>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="p-4 text-center">
                            <img
                                src="${imageSrc}"
                                alt="${imageName}"
                                class="max-w-full max-h-96 mx-auto rounded"
                                style="max-height: 70vh"
                            >
                        </div>
                        <div class="p-4 border-t bg-gray-50 text-center">
                            <a
                                href="${imageSrc}"
                                target="_blank"
                                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center"
                            >
                                <i class="fas fa-download mr-2"></i>
                                Download Original
                            </a>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
        }

        // Calculate leave days
        function calculateLeaveDays() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);

                if (end >= start) {
                    const timeDiff = end.getTime() - start.getTime();
                    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    console.log('Leave days calculated:', dayDiff);
                } else {
                    alert('End date must be after start date');
                    document.querySelector('input[name="end_date"]').value = '';
                }
            }
        }

        // Pagination
        function renderPagination(pagination) {
            const container = document.getElementById('pagination-buttons');
            const info = document.getElementById('pagination-info');

            info.textContent = `Showing ${((pagination.page - 1) * pagination.limit) + 1} to ${Math.min(pagination.page * pagination.limit, pagination.total)} of ${pagination.total} results`;

            let buttons = '';

            // Previous button
            buttons += `
                <button onclick="changePage(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}
                    class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 ${pagination.page <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                    Previous
                </button>
            `;

            // Page numbers
            for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.totalPages, pagination.page + 2); i++) {
                buttons += `
                    <button onclick="changePage(${i})"
                        class="px-3 py-2 text-sm leading-tight ${i === pagination.page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300'} hover:bg-blue-700">
                        ${i}
                    </button>
                `;
            }

            // Next button
            buttons += `
                <button onclick="changePage(${pagination.page + 1})" ${pagination.page >= pagination.totalPages ? 'disabled' : ''}
                    class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 ${pagination.page >= pagination.totalPages ? 'opacity-50 cursor-not-allowed' : ''}">
                    Next
                </button>
            `;

            container.innerHTML = buttons;
        }

        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            loadLeaveRequests(currentPage, currentFilters);
        }

        // Utility functions
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' :
                              type === 'error' ? 'bg-red-100 border-red-400 text-red-700' :
                              'bg-blue-100 border-blue-400 text-blue-700';

            alertContainer.innerHTML = `
                <div class="${alertClass} border px-4 py-3 rounded mb-4">
                    ${message}
                    <button type="button" class="float-right" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html>