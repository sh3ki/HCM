<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Check if user is admin
$currentUser = getCurrentUser();
if (!$currentUser || !isset($currentUser['role']) || strtolower($currentUser['role']) !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - HCM System</title>
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
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-button.active {
            border-bottom: 2px solid #1b68ff;
            color: #1b68ff;
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
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Admin Management</h1>
                <p class="text-gray-600">Comprehensive admin tools for managing employees, performance, and analytics</p>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex overflow-x-auto border-b border-gray-200">
                    <button class="tab-button active px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="employee-search">
                        <i class="fas fa-search mr-2"></i>Employee Search & Filter
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="tax-records">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Tax Records
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="performance">
                        <i class="fas fa-chart-line mr-2"></i>Performance Analysis
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="goals">
                        <i class="fas fa-bullseye mr-2"></i>Performance Goals
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="salary-structure">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Salary Structures
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="salary-comparison">
                        <i class="fas fa-balance-scale mr-2"></i>Salary Comparison
                    </button>
                    <button class="tab-button px-6 py-3 text-sm font-medium whitespace-nowrap" data-tab="ai-insights">
                        <i class="fas fa-brain mr-2"></i>AI Insights
                    </button>
                </div>

                <!-- Tab Content: Employee Search & Filter -->
                <div id="employee-search" class="tab-content active p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Advanced Employee Search & Filter</h2>
                    
                    <!-- Advanced Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="advSearchQuery" placeholder="Name, Email, ID..." 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select id="advSearchDepartment" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                            <select id="advSearchPosition" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Positions</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="advSearchStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salary Range</label>
                            <select id="advSearchSalaryRange" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Ranges</option>
                                <option value="0-25000">₱0 - ₱25,000</option>
                                <option value="25000-50000">₱25,000 - ₱50,000</option>
                                <option value="50000-75000">₱50,000 - ₱75,000</option>
                                <option value="75000-100000">₱75,000 - ₱100,000</option>
                                <option value="100000-999999">₱100,000+</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date</label>
                            <input type="date" id="advSearchHireDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                            <select id="advSearchGender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="performAdvancedSearch()" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Employee</th>
                                        <th class="px-6 py-3">Department</th>
                                        <th class="px-6 py-3">Position</th>
                                        <th class="px-6 py-3">Salary</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="employeeSearchResults">
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-search text-4xl mb-2"></i>
                                            <p>Use the filters above to search for employees</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Tax Records -->
                <div id="tax-records" class="tab-content p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Tax Records Review</h2>
                        <div class="flex gap-2">
                            <select id="taxYear" class="px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="2026">2026</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                            </select>
                            <select id="taxPeriod" class="px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Periods</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                            </select>
                            <button onclick="loadTaxRecords()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-sync mr-2"></i>Load
                            </button>
                        </div>
                    </div>

                    <!-- Tax Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm text-blue-600 mb-1">Total Tax Withheld</p>
                            <p class="text-2xl font-bold text-blue-900" id="totalTaxWithheld">₱0.00</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <p class="text-sm text-green-600 mb-1">Total Taxable Income</p>
                            <p class="text-2xl font-bold text-green-900" id="totalTaxableIncome">₱0.00</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <p class="text-sm text-purple-600 mb-1">SSS Contributions</p>
                            <p class="text-2xl font-bold text-purple-900" id="totalSSS">₱0.00</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                            <p class="text-sm text-orange-600 mb-1">PhilHealth + Pag-IBIG</p>
                            <p class="text-2xl font-bold text-orange-900" id="totalOtherContributions">₱0.00</p>
                        </div>
                    </div>

                    <!-- Tax Records Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Employee</th>
                                        <th class="px-6 py-3">Period</th>
                                        <th class="px-6 py-3">Gross Income</th>
                                        <th class="px-6 py-3">Taxable Income</th>
                                        <th class="px-6 py-3">Tax Withheld</th>
                                        <th class="px-6 py-3">SSS</th>
                                        <th class="px-6 py-3">PhilHealth</th>
                                        <th class="px-6 py-3">Pag-IBIG</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="taxRecordsTable">
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-file-invoice-dollar text-4xl mb-2"></i>
                                            <p>Select a year and period to view tax records</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Performance Analysis -->
                <div id="performance" class="tab-content p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Performance Analysis</h2>

                    <!-- Performance Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Top Performers -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-green-900">
                                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Performers
                                </h3>
                                <span class="bg-green-200 text-green-800 px-3 py-1 rounded-full text-sm font-medium" id="topPerformersCount">0</span>
                            </div>
                            <div id="topPerformersList" class="space-y-2">
                                <p class="text-green-700">Loading...</p>
                            </div>
                        </div>

                        <!-- Underperformers -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 p-6 rounded-lg border border-red-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-red-900">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Needs Attention
                                </h3>
                                <span class="bg-red-200 text-red-800 px-3 py-1 rounded-full text-sm font-medium" id="underperformersCount">0</span>
                            </div>
                            <div id="underperformersList" class="space-y-2">
                                <p class="text-red-700">Loading...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Chart -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Performance Overview</h3>
                        <div class="h-64">
                            <canvas id="departmentPerformanceChart"></canvas>
                        </div>
                    </div>

                    <!-- Detailed Performance Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Employee</th>
                                        <th class="px-6 py-3">Department</th>
                                        <th class="px-6 py-3">Avg Rating</th>
                                        <th class="px-6 py-3">Goals Achievement</th>
                                        <th class="px-6 py-3">Evaluations</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="performanceTable">
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading performance data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Performance Goals -->
                <div id="goals" class="tab-content p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Performance Goals Management</h2>
                        <button onclick="openSetGoalModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Set New Goal
                        </button>
                    </div>

                    <!-- Goals Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm text-blue-600 mb-1">Total Goals</p>
                            <p class="text-2xl font-bold text-blue-900" id="totalGoals">0</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <p class="text-sm text-green-600 mb-1">Completed</p>
                            <p class="text-2xl font-bold text-green-900" id="completedGoals">0</p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <p class="text-sm text-yellow-600 mb-1">In Progress</p>
                            <p class="text-2xl font-bold text-yellow-900" id="inProgressGoals">0</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <p class="text-sm text-purple-600 mb-1">Avg Progress</p>
                            <p class="text-2xl font-bold text-purple-900" id="avgGoalProgress">0%</p>
                        </div>
                    </div>

                    <!-- Goals Filter -->
                    <div class="flex gap-2 mb-4">
                        <select id="goalsEmployeeFilter" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Employees</option>
                        </select>
                        <select id="goalsStatusFilter" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Status</option>
                            <option value="not_started">Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                        <select id="goalsPriorityFilter" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                        <button onclick="loadGoals()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>

                    <!-- Goals Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Goal</th>
                                        <th class="px-6 py-3">Employee</th>
                                        <th class="px-6 py-3">Category</th>
                                        <th class="px-6 py-3">Priority</th>
                                        <th class="px-6 py-3">Progress</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Target Date</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="goalsTable">
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-bullseye text-4xl mb-2"></i>
                                            <p>No goals found. Set a new goal to get started.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Salary Structures -->
                <div id="salary-structure" class="tab-content p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Salary Structure Assignment</h2>
                        <button onclick="openAssignSalaryStructureModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Assign Structure
                        </button>
                    </div>

                    <!-- Salary Structures Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-600">Available Structures</h3>
                                <i class="fas fa-list text-primary text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900" id="totalStructures">7</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-600">Assigned Employees</h3>
                                <i class="fas fa-users text-green-600 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900" id="totalAssignedEmployees">0</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-600">Unassigned</h3>
                                <i class="fas fa-user-slash text-red-600 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900" id="totalUnassignedEmployees">0</p>
                        </div>
                    </div>

                    <!-- Salary Structures List -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900">Salary Structures</h3>
                        </div>
                        <div class="p-6">
                            <div id="salaryStructuresList" class="space-y-4">
                                <p class="text-gray-500">Loading salary structures...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Assignments -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900">Employee Salary Structure Assignments</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Employee</th>
                                        <th class="px-6 py-3">Current Structure</th>
                                        <th class="px-6 py-3">Grade Level</th>
                                        <th class="px-6 py-3">Salary Range</th>
                                        <th class="px-6 py-3">Current Salary</th>
                                        <th class="px-6 py-3">Assigned Date</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="salaryAssignmentsTable">
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading assignments...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Salary Comparison -->
                <div id="salary-comparison" class="tab-content p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Salary Comparison Across Departments</h2>

                    <!-- Comparison Chart -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Average Salary by Department</h3>
                            <div class="flex gap-2">
                                <button onclick="updateSalaryChart('avg')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Average</button>
                                <button onclick="updateSalaryChart('min')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Minimum</button>
                                <button onclick="updateSalaryChart('max')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Maximum</button>
                                <button onclick="updateSalaryChart('total')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Total</button>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="salaryComparisonChart"></canvas>
                        </div>
                    </div>

                    <!-- Department Comparison Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Department</th>
                                        <th class="px-6 py-3">Employees</th>
                                        <th class="px-6 py-3">Avg Salary</th>
                                        <th class="px-6 py-3">Min Salary</th>
                                        <th class="px-6 py-3">Max Salary</th>
                                        <th class="px-6 py-3">Total Cost</th>
                                        <th class="px-6 py-3">Avg Annual</th>
                                    </tr>
                                </thead>
                                <tbody id="salaryComparisonTable">
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading comparison data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: AI Insights -->
                <div id="ai-insights" class="tab-content p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">AI-Powered Insights & Recommendations</h2>

                    <!-- AI Query Section -->
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 p-6 rounded-lg border border-purple-200 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-robot text-purple-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ask AI Assistant</h3>
                                <textarea id="aiQuery" rows="3" placeholder="Ask anything about your workforce, performance trends, salary benchmarks, or get recommendations..." 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary mb-3"></textarea>
                                <button onclick="queryAI()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                                    <i class="fas fa-paper-plane mr-2"></i>Ask AI
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- AI Response -->
                    <div id="aiResponse" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 hidden">
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-lightbulb text-blue-600 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Response</h3>
                                <div id="aiResponseContent" class="text-gray-700"></div>
                                <div class="mt-4 flex gap-2">
                                    <button onclick="rateAIResponse(true)" class="text-sm text-green-600 hover:text-green-700">
                                        <i class="fas fa-thumbs-up mr-1"></i>Helpful
                                    </button>
                                    <button onclick="rateAIResponse(false)" class="text-sm text-red-600 hover:text-red-700">
                                        <i class="fas fa-thumbs-down mr-1"></i>Not Helpful
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Recommendations -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Salary Recommendations -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-coins text-green-600 mr-2"></i>Salary Recommendations
                                </h3>
                                <button onclick="generateAIRecommendations('salary')" class="text-sm text-primary hover:text-blue-700">
                                    <i class="fas fa-sync mr-1"></i>Generate
                                </button>
                            </div>
                            <div id="salaryRecommendations" class="space-y-3">
                                <p class="text-gray-500 text-sm">Click "Generate" to get AI-powered salary recommendations</p>
                            </div>
                        </div>

                        <!-- Performance Recommendations -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>Performance Recommendations
                                </h3>
                                <button onclick="generateAIRecommendations('performance')" class="text-sm text-primary hover:text-blue-700">
                                    <i class="fas fa-sync mr-1"></i>Generate
                                </button>
                            </div>
                            <div id="performanceRecommendations" class="space-y-3">
                                <p class="text-gray-500 text-sm">Click "Generate" to get AI-powered performance recommendations</p>
                            </div>
                        </div>

                        <!-- Retention Risks -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Retention Risks
                                </h3>
                                <button onclick="generateAIRecommendations('retention')" class="text-sm text-primary hover:text-blue-700">
                                    <i class="fas fa-sync mr-1"></i>Generate
                                </button>
                            </div>
                            <div id="retentionRecommendations" class="space-y-3">
                                <p class="text-gray-500 text-sm">Click "Generate" to identify potential retention risks</p>
                            </div>
                        </div>

                        <!-- Training Recommendations -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-graduation-cap text-purple-600 mr-2"></i>Training Needs
                                </h3>
                                <button onclick="generateAIRecommendations('training')" class="text-sm text-primary hover:text-blue-700">
                                    <i class="fas fa-sync mr-1"></i>Generate
                                </button>
                            </div>
                            <div id="trainingRecommendations" class="space-y-3">
                                <p class="text-gray-500 text-sm">Click "Generate" to get training recommendations</p>
                            </div>
                        </div>
                    </div>

                    <!-- AI Interaction History -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900">Recent AI Interactions</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Timestamp</th>
                                        <th class="px-6 py-3">Query</th>
                                        <th class="px-6 py-3">Type</th>
                                        <th class="px-6 py-3">Confidence</th>
                                        <th class="px-6 py-3">Helpful</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="aiInteractionHistory">
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No AI interactions yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals will go here -->
    <!-- Set Goal Modal -->
    <div id="setGoalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900">Set Performance Goal</h3>
                <button onclick="closeSetGoalModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="setGoalForm" onsubmit="submitGoal(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select id="goalEmployee" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Goal Title *</label>
                        <input type="text" id="goalTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="goalDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Goal Type *</label>
                        <select id="goalType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="individual">Individual</option>
                            <option value="team">Team</option>
                            <option value="organizational">Organizational</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="goalCategory" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="productivity">Productivity</option>
                            <option value="quality">Quality</option>
                            <option value="innovation">Innovation</option>
                            <option value="leadership">Leadership</option>
                            <option value="collaboration">Collaboration</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                        <select id="goalPriority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Value</label>
                        <input type="text" id="goalTargetValue" placeholder="e.g., 100 sales" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
                        <input type="date" id="goalStartDate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Date *</label>
                        <input type="date" id="goalTargetDate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeSetGoalModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Set Goal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Salary Structure Modal -->
    <div id="assignSalaryStructureModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900">Assign Salary Structure</h3>
                <button onclick="closeAssignSalaryStructureModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="assignSalaryStructureForm" onsubmit="submitSalaryStructureAssignment(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select id="structureEmployee" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salary Structure *</label>
                        <select id="salaryStructure" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Select Structure</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective From *</label>
                        <input type="date" id="structureEffectiveFrom" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea id="structureNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeAssignSalaryStructureModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-check mr-2"></i>Assign
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Global variables
        let currentAIInteractionId = null;
        let salaryComparisonChartInstance = null;
        let departmentPerformanceChartInstance = null;

        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Load data for the active tab
                loadTabData(tabId);
            });
        });

        // Load data when tab is activated
        function loadTabData(tabId) {
            switch(tabId) {
                case 'employee-search':
                    loadFiltersData();
                    break;
                case 'tax-records':
                    // Will be loaded when user clicks Load button
                    break;
                case 'performance':
                    loadPerformanceData();
                    break;
                case 'goals':
                    loadGoals();
                    break;
                case 'salary-structure':
                    loadSalaryStructures();
                    break;
                case 'salary-comparison':
                    loadSalaryComparison();
                    break;
                case 'ai-insights':
                    loadAIInteractionHistory();
                    break;
            }
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadFiltersData();
        });

        function loadFiltersData() {
            // Load departments and positions for filters
            fetch('../api/employees.php?departments=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const deptSelect = document.getElementById('advSearchDepartment');
                        if (data.data.departments) {
                            data.data.departments.forEach(dept => {
                                const option = document.createElement('option');
                                option.value = dept;
                                option.textContent = dept;
                                deptSelect.appendChild(option);
                            });
                        }
                    }
                });

            fetch('../api/employees.php?positions=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const posSelect = document.getElementById('advSearchPosition');
                        if (data.data.positions) {
                            data.data.positions.forEach(pos => {
                                const option = document.createElement('option');
                                option.value = pos;
                                option.textContent = pos;
                                posSelect.appendChild(option);
                            });
                        }
                    }
                });
        }

        function performAdvancedSearch() {
            const query = document.getElementById('advSearchQuery').value;
            const department = document.getElementById('advSearchDepartment').value;
            const position = document.getElementById('advSearchPosition').value;
            const status = document.getElementById('advSearchStatus').value;
            const salaryRange = document.getElementById('advSearchSalaryRange').value;
            const hireDate = document.getElementById('advSearchHireDate').value;
            const gender = document.getElementById('advSearchGender').value;

            const params = new URLSearchParams();
            if (query) params.append('search', query);
            if (department) params.append('department', department);
            if (position) params.append('position', position);
            if (status) params.append('status', status);
            if (salaryRange) params.append('salary_range', salaryRange);
            if (hireDate) params.append('hire_date', hireDate);
            if (gender) params.append('gender', gender);

            fetch(`../api/admin.php?action=search_employees&${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.data);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to search employees');
                });
        }

        function displaySearchResults(employees) {
            const tbody = document.getElementById('employeeSearchResults');
            tbody.innerHTML = '';

            if (!employees || employees.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No employees found</td></tr>';
                return;
            }

            employees.forEach(emp => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img class="w-10 h-10 rounded-full mr-3" src="${emp.profile_picture || 'https://via.placeholder.com/40'}" alt="">
                            <div>
                                <div class="font-medium text-gray-900">${emp.first_name} ${emp.last_name}</div>
                                <div class="text-gray-500 text-sm">${emp.email}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">${emp.department || 'N/A'}</td>
                    <td class="px-6 py-4">${emp.position || 'N/A'}</td>
                    <td class="px-6 py-4">₱${parseFloat(emp.basic_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                            emp.employment_status === 'Active' ? 'bg-green-100 text-green-800' :
                            emp.employment_status === 'Inactive' ? 'bg-gray-100 text-gray-800' :
                            emp.employment_status === 'On Leave' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                        }">
                            ${emp.employment_status}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="viewEmployeeDetails(${emp.id})" class="text-primary hover:text-blue-700">
                            <i class="fas fa-eye mr-1"></i>View
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function loadTaxRecords() {
            const year = document.getElementById('taxYear').value;
            const period = document.getElementById('taxPeriod').value;

            fetch(`../api/admin.php?action=get_tax_records&year=${year}&period=${period}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTaxRecords(data.data);
                        updateTaxSummary(data.summary);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displayTaxRecords(records) {
            const tbody = document.getElementById('taxRecordsTable');
            tbody.innerHTML = '';

            if (!records || records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-gray-500">No tax records found</td></tr>';
                return;
            }

            records.forEach(record => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">${record.employee_name}</td>
                    <td class="px-6 py-4">${record.tax_period}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.gross_income).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.taxable_income).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.tax_withheld).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.sss_contribution).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.philhealth_contribution).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(record.pagibig_contribution).toLocaleString()}</td>
                    <td class="px-6 py-4">
                        <button onclick="viewTaxDetails(${record.id})" class="text-primary hover:text-blue-700">
                            <i class="fas fa-eye mr-1"></i>Details
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateTaxSummary(summary) {
            if (summary) {
                document.getElementById('totalTaxWithheld').textContent = '₱' + parseFloat(summary.total_tax_withheld || 0).toLocaleString();
                document.getElementById('totalTaxableIncome').textContent = '₱' + parseFloat(summary.total_taxable_income || 0).toLocaleString();
                document.getElementById('totalSSS').textContent = '₱' + parseFloat(summary.total_sss || 0).toLocaleString();
                document.getElementById('totalOtherContributions').textContent = '₱' + parseFloat(summary.total_other || 0).toLocaleString();
            }
        }

        function loadPerformanceData() {
            fetch('../api/admin.php?action=get_performance_data')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTopPerformers(data.data.top_performers);
                        displayUnderperformers(data.data.underperformers);
                        displayPerformanceTable(data.data.all_performance);
                        renderDepartmentPerformanceChart(data.data.department_performance);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displayTopPerformers(performers) {
            const container = document.getElementById('topPerformersList');
            const count = document.getElementById('topPerformersCount');
            count.textContent = performers.length;

            if (!performers || performers.length === 0) {
                container.innerHTML = '<p class="text-green-700 text-sm">No data available</p>';
                return;
            }

            container.innerHTML = performers.slice(0, 5).map(emp => `
                <div class="bg-white p-3 rounded border border-green-200 flex justify-between items-center">
                    <div>
                        <div class="font-medium text-gray-900">${emp.employee_name}</div>
                        <div class="text-sm text-gray-600">${emp.department || 'N/A'}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-green-600">${parseFloat(emp.avg_rating).toFixed(1)}</div>
                        <div class="text-xs text-gray-500">${emp.evaluation_count} evals</div>
                    </div>
                </div>
            `).join('');
        }

        function displayUnderperformers(performers) {
            const container = document.getElementById('underperformersList');
            const count = document.getElementById('underperformersCount');
            count.textContent = performers.length;

            if (!performers || performers.length === 0) {
                container.innerHTML = '<p class="text-red-700 text-sm">No data available</p>';
                return;
            }

            container.innerHTML = performers.slice(0, 5).map(emp => `
                <div class="bg-white p-3 rounded border border-red-200 flex justify-between items-center">
                    <div>
                        <div class="font-medium text-gray-900">${emp.employee_name}</div>
                        <div class="text-sm text-gray-600">${emp.department || 'N/A'}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-red-600">${parseFloat(emp.avg_rating).toFixed(1)}</div>
                        <div class="text-xs text-gray-500">${emp.evaluation_count} evals</div>
                    </div>
                </div>
            `).join('');
        }

        function displayPerformanceTable(data) {
            const tbody = document.getElementById('performanceTable');
            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No performance data available</td></tr>';
                return;
            }

            data.forEach(emp => {
                const row = document.createElement('tr');
                const ratingClass = emp.avg_performance_rating >= 4 ? 'text-green-600' : 
                                   emp.avg_performance_rating < 3 ? 'text-red-600' : 'text-yellow-600';
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">${emp.employee_name}</td>
                    <td class="px-6 py-4">${emp.department || 'N/A'}</td>
                    <td class="px-6 py-4 ${ratingClass} font-semibold">${parseFloat(emp.avg_performance_rating || 0).toFixed(1)}</td>
                    <td class="px-6 py-4">${parseFloat(emp.avg_goals_achievement || 0).toFixed(0)}%</td>
                    <td class="px-6 py-4">${emp.total_evaluations || 0}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                            emp.avg_performance_rating >= 4 ? 'bg-green-100 text-green-800' :
                            emp.avg_performance_rating < 3 ? 'bg-red-100 text-red-800' :
                            'bg-yellow-100 text-yellow-800'
                        }">
                            ${emp.avg_performance_rating >= 4 ? 'Excellent' :
                              emp.avg_performance_rating < 3 ? 'Needs Improvement' : 'Good'}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="viewPerformanceDetails(${emp.employee_id})" class="text-primary hover:text-blue-700">
                            <i class="fas fa-chart-line mr-1"></i>View
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function renderDepartmentPerformanceChart(data) {
            if (!data || data.length === 0) return;

            const ctx = document.getElementById('departmentPerformanceChart').getContext('2d');
            
            if (departmentPerformanceChartInstance) {
                departmentPerformanceChartInstance.destroy();
            }

            departmentPerformanceChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.department),
                    datasets: [{
                        label: 'Average Performance Rating',
                        data: data.map(d => parseFloat(d.avg_rating)),
                        backgroundColor: 'rgba(27, 104, 255, 0.8)',
                        borderColor: 'rgba(27, 104, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5
                        }
                    }
                }
            });
        }

        function loadGoals() {
            const employeeFilter = document.getElementById('goalsEmployeeFilter').value;
            const statusFilter = document.getElementById('goalsStatusFilter').value;
            const priorityFilter = document.getElementById('goalsPriorityFilter').value;

            const params = new URLSearchParams();
            if (employeeFilter) params.append('employee_id', employeeFilter);
            if (statusFilter) params.append('status', statusFilter);
            if (priorityFilter) params.append('priority', priorityFilter);

            fetch(`../api/admin.php?action=get_goals&${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayGoals(data.data);
                        updateGoalsStatistics(data.statistics);
                    }
                })
                .catch(error => console.error('Error:', error));

            // Load employees for filter if not loaded
            if (document.getElementById('goalsEmployeeFilter').options.length === 1) {
                loadEmployeesForFilters();
            }
        }

        function loadEmployeesForFilters() {
            fetch('../api/employees.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.employees) {
                        // For goal employee filter
                        const goalsFilter = document.getElementById('goalsEmployeeFilter');
                        // For goal employee select
                        const goalEmployee = document.getElementById('goalEmployee');
                        // For structure employee select
                        const structureEmployee = document.getElementById('structureEmployee');

                        data.data.employees.forEach(emp => {
                            const option1 = document.createElement('option');
                            option1.value = emp.id;
                            option1.textContent = `${emp.first_name} ${emp.last_name}`;
                            goalsFilter.appendChild(option1);

                            const option2 = document.createElement('option');
                            option2.value = emp.id;
                            option2.textContent = `${emp.first_name} ${emp.last_name}`;
                            goalEmployee.appendChild(option2);

                            const option3 = document.createElement('option');
                            option3.value = emp.id;
                            option3.textContent = `${emp.first_name} ${emp.last_name}`;
                            structureEmployee.appendChild(option3);
                        });
                    }
                });
        }

        function displayGoals(goals) {
            const tbody = document.getElementById('goalsTable');
            tbody.innerHTML = '';

            if (!goals || goals.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No goals found</td></tr>';
                return;
            }

            goals.forEach(goal => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">${goal.goal_title}</div>
                        <div class="text-sm text-gray-500">${goal.category}</div>
                    </td>
                    <td class="px-6 py-4">${goal.employee_name}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            ${goal.category}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                            goal.priority === 'critical' ? 'bg-red-100 text-red-800' :
                            goal.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                            goal.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-green-100 text-green-800'
                        }">
                            ${goal.priority.toUpperCase()}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                                <div class="bg-primary h-2.5 rounded-full" style="width: ${goal.progress_percentage}%"></div>
                            </div>
                            <span class="text-sm font-medium">${parseFloat(goal.progress_percentage).toFixed(0)}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                            goal.status === 'completed' ? 'bg-green-100 text-green-800' :
                            goal.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                            goal.status === 'on_hold' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-gray-100 text-gray-800'
                        }">
                            ${goal.status.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td class="px-6 py-4">${goal.target_date}</td>
                    <td class="px-6 py-4">
                        <button onclick="editGoal(${goal.id})" class="text-primary hover:text-blue-700 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteGoal(${goal.id})" class="text-red-600 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateGoalsStatistics(stats) {
            if (stats) {
                document.getElementById('totalGoals').textContent = stats.total || 0;
                document.getElementById('completedGoals').textContent = stats.completed || 0;
                document.getElementById('inProgressGoals').textContent = stats.in_progress || 0;
                document.getElementById('avgGoalProgress').textContent = parseFloat(stats.avg_progress || 0).toFixed(0) + '%';
            }
        }

        function loadSalaryStructures() {
            fetch('../api/admin.php?action=get_salary_structures')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySalaryStructures(data.data.structures);
                        displaySalaryAssignments(data.data.assignments);
                        updateStructureStatistics(data.data.statistics);
                        populateSalaryStructureSelect(data.data.structures);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displaySalaryStructures(structures) {
            const container = document.getElementById('salaryStructuresList');
            
            if (!structures || structures.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No salary structures found</p>';
                return;
            }

            container.innerHTML = structures.map(structure => `
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-gray-900">${structure.structure_name}</h4>
                            <p class="text-sm text-gray-600 mt-1">${structure.description || ''}</p>
                            <div class="mt-2 flex items-center gap-4">
                                <span class="text-sm text-gray-700">
                                    <i class="fas fa-layer-group text-primary mr-1"></i>
                                    Grade: <strong>${structure.grade_level}</strong>
                                </span>
                                <span class="text-sm text-gray-700">
                                    <i class="fas fa-coins text-green-600 mr-1"></i>
                                    ₱${parseFloat(structure.min_salary).toLocaleString()} - ₱${parseFloat(structure.max_salary).toLocaleString()}
                                </span>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                            structure.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                        }">
                            ${structure.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                </div>
            `).join('');
        }

        function displaySalaryAssignments(assignments) {
            const tbody = document.getElementById('salaryAssignmentsTable');
            tbody.innerHTML = '';

            if (!assignments || assignments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No assignments found</td></tr>';
                return;
            }

            assignments.forEach(assignment => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">${assignment.employee_name}</div>
                        <div class="text-sm text-gray-500">${assignment.department || 'N/A'}</div>
                    </td>
                    <td class="px-6 py-4">${assignment.structure_name || 'Not Assigned'}</td>
                    <td class="px-6 py-4">${assignment.grade_level || 'N/A'}</td>
                    <td class="px-6 py-4">
                        ${assignment.min_salary && assignment.max_salary ? 
                          `₱${parseFloat(assignment.min_salary).toLocaleString()} - ₱${parseFloat(assignment.max_salary).toLocaleString()}` : 
                          'N/A'}
                    </td>
                    <td class="px-6 py-4">₱${parseFloat(assignment.current_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">${assignment.assigned_date || 'N/A'}</td>
                    <td class="px-6 py-4">
                        <button onclick="reassignStructure(${assignment.employee_id})" class="text-primary hover:text-blue-700">
                            <i class="fas fa-exchange-alt mr-1"></i>Reassign
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateStructureStatistics(stats) {
            if (stats) {
                document.getElementById('totalStructures').textContent = stats.total_structures || 7;
                document.getElementById('totalAssignedEmployees').textContent = stats.assigned_employees || 0;
                document.getElementById('totalUnassignedEmployees').textContent = stats.unassigned_employees || 0;
            }
        }

        function populateSalaryStructureSelect(structures) {
            const select = document.getElementById('salaryStructure');
            select.innerHTML = '<option value="">Select Structure</option>';
            
            if (structures) {
                structures.forEach(structure => {
                    if (structure.is_active) {
                        const option = document.createElement('option');
                        option.value = structure.id;
                        option.textContent = `${structure.structure_name} (${structure.grade_level})`;
                        select.appendChild(option);
                    }
                });
            }
        }

        function loadSalaryComparison() {
            fetch('../api/admin.php?action=get_salary_comparison')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySalaryComparisonTable(data.data);
                        renderSalaryComparisonChart(data.data, 'avg');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displaySalaryComparisonTable(data) {
            const tbody = document.getElementById('salaryComparisonTable');
            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No data available</td></tr>';
                return;
            }

            data.forEach(dept => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 font-medium text-gray-900">${dept.department_name}</td>
                    <td class="px-6 py-4">${dept.employee_count}</td>
                    <td class="px-6 py-4">₱${parseFloat(dept.avg_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(dept.min_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(dept.max_salary || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(dept.total_salary_cost || 0).toLocaleString()}</td>
                    <td class="px-6 py-4">₱${parseFloat(dept.avg_annual_cost || 0).toLocaleString()}</td>
                `;
                tbody.appendChild(row);
            });
        }

        function renderSalaryComparisonChart(data, metric = 'avg') {
            if (!data || data.length === 0) return;

            const ctx = document.getElementById('salaryComparisonChart').getContext('2d');
            
            if (salaryComparisonChartInstance) {
                salaryComparisonChartInstance.destroy();
            }

            let chartData, label;
            switch(metric) {
                case 'min':
                    chartData = data.map(d => parseFloat(d.min_salary || 0));
                    label = 'Minimum Salary';
                    break;
                case 'max':
                    chartData = data.map(d => parseFloat(d.max_salary || 0));
                    label = 'Maximum Salary';
                    break;
                case 'total':
                    chartData = data.map(d => parseFloat(d.total_salary_cost || 0));
                    label = 'Total Salary Cost';
                    break;
                default:
                    chartData = data.map(d => parseFloat(d.avg_salary || 0));
                    label = 'Average Salary';
            }

            salaryComparisonChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.department_name),
                    datasets: [{
                        label: label,
                        data: chartData,
                        backgroundColor: 'rgba(58, 210, 159, 0.8)',
                        borderColor: 'rgba(58, 210, 159, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateSalaryChart(metric) {
            fetch('../api/admin.php?action=get_salary_comparison')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSalaryComparisonChart(data.data, metric);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // AI Functions
        function queryAI() {
            const query = document.getElementById('aiQuery').value;
            if (!query.trim()) {
                alert('Please enter a question');
                return;
            }

            const responseDiv = document.getElementById('aiResponse');
            const contentDiv = document.getElementById('aiResponseContent');
            
            responseDiv.classList.remove('hidden');
            contentDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Processing your query...</div>';

            fetch('../api/admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'ai_query', query: query})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentAIInteractionId = data.interaction_id;
                        contentDiv.innerHTML = data.response;
                    } else {
                        contentDiv.innerHTML = '<p class="text-red-600">Error: ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    contentDiv.innerHTML = '<p class="text-red-600">Failed to get AI response</p>';
                });
        }

        function rateAIResponse(helpful) {
            if (!currentAIInteractionId) return;

            fetch('../api/admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'rate_ai_response',
                    interaction_id: currentAIInteractionId,
                    helpful: helpful
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for your feedback!');
                    }
                });
        }

        function generateAIRecommendations(type) {
            const containers = {
                'salary': 'salaryRecommendations',
                'performance': 'performanceRecommendations',
                'retention': 'retentionRecommendations',
                'training': 'trainingRecommendations'
            };

            const container = document.getElementById(containers[type]);
            container.innerHTML = '<p class="text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Generating recommendations...</p>';

            fetch('../api/admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'generate_ai_recommendations', type: type})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.recommendations) {
                        displayRecommendations(container, data.recommendations);
                    } else {
                        container.innerHTML = '<p class="text-red-600 text-sm">Failed to generate recommendations</p>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<p class="text-red-600 text-sm">Error generating recommendations</p>';
                });
        }

        function displayRecommendations(container, recommendations) {
            if (!recommendations || recommendations.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No recommendations available</p>';
                return;
            }

            container.innerHTML = recommendations.map(rec => `
                <div class="bg-gray-50 p-3 rounded border border-gray-200">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-gray-900 text-sm">${rec.title}</h4>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                            rec.priority === 'critical' ? 'bg-red-100 text-red-800' :
                            rec.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                            rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-green-100 text-green-800'
                        }">
                            ${rec.priority}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">${rec.recommendation}</p>
                    ${rec.confidence_score ? `<div class="text-xs text-gray-500">Confidence: ${parseFloat(rec.confidence_score).toFixed(0)}%</div>` : ''}
                </div>
            `).join('');
        }

        function loadAIInteractionHistory() {
            fetch('../api/admin.php?action=get_ai_history')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAIHistory(data.data);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displayAIHistory(history) {
            const tbody = document.getElementById('aiInteractionHistory');
            tbody.innerHTML = '';

            if (!history || history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No AI interactions yet</td></tr>';
                return;
            }

            history.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">${item.created_at}</td>
                    <td class="px-6 py-4">${item.query_text.substring(0, 50)}...</td>
                    <td class="px-6 py-4">${item.interaction_type}</td>
                    <td class="px-6 py-4">${item.confidence_score ? parseFloat(item.confidence_score).toFixed(0) + '%' : 'N/A'}</td>
                    <td class="px-6 py-4">
                        ${item.was_helpful === true ? '<i class="fas fa-thumbs-up text-green-600"></i>' :
                          item.was_helpful === false ? '<i class="fas fa-thumbs-down text-red-600"></i>' :
                          '<span class="text-gray-400">N/A</span>'}
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="viewAIDetails(${item.id})" class="text-primary hover:text-blue-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Modal functions
        function openSetGoalModal() {
            document.getElementById('setGoalModal').classList.remove('hidden');
            document.getElementById('setGoalModal').classList.add('flex');
            if (document.getElementById('goalEmployee').options.length === 1) {
                loadEmployeesForFilters();
            }
        }

        function closeSetGoalModal() {
            document.getElementById('setGoalModal').classList.add('hidden');
            document.getElementById('setGoalModal').classList.remove('flex');
            document.getElementById('setGoalForm').reset();
        }

        function submitGoal(event) {
            event.preventDefault();
            
            const formData = {
                action: 'set_goal',
                employee_id: document.getElementById('goalEmployee').value,
                goal_title: document.getElementById('goalTitle').value,
                goal_description: document.getElementById('goalDescription').value,
                goal_type: document.getElementById('goalType').value,
                category: document.getElementById('goalCategory').value,
                priority: document.getElementById('goalPriority').value,
                target_value: document.getElementById('goalTargetValue').value,
                start_date: document.getElementById('goalStartDate').value,
                target_date: document.getElementById('goalTargetDate').value
            };

            fetch('../api/admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Goal set successfully!');
                        closeSetGoalModal();
                        loadGoals();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Failed to set goal');
                });
        }

        function openAssignSalaryStructureModal() {
            document.getElementById('assignSalaryStructureModal').classList.remove('hidden');
            document.getElementById('assignSalaryStructureModal').classList.add('flex');
            if (document.getElementById('structureEmployee').options.length === 1) {
                loadEmployeesForFilters();
            }
            if (document.getElementById('salaryStructure').options.length === 1) {
                loadSalaryStructures();
            }
        }

        function closeAssignSalaryStructureModal() {
            document.getElementById('assignSalaryStructureModal').classList.add('hidden');
            document.getElementById('assignSalaryStructureModal').classList.remove('flex');
            document.getElementById('assignSalaryStructureForm').reset();
        }

        function submitSalaryStructureAssignment(event) {
            event.preventDefault();
            
            const formData = {
                action: 'assign_salary_structure',
                employee_id: document.getElementById('structureEmployee').value,
                salary_structure_id: document.getElementById('salaryStructure').value,
                effective_from: document.getElementById('structureEffectiveFrom').value,
                notes: document.getElementById('structureNotes').value
            };

            fetch('../api/admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Salary structure assigned successfully!');
                        closeAssignSalaryStructureModal();
                        loadSalaryStructures();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Failed to assign salary structure');
                });
        }

        // Placeholder functions for actions
        function viewEmployeeDetails(id) {
            window.location.href = `employee-profile.php?id=${id}`;
        }

        function viewTaxDetails(id) {
            alert('Tax details view coming soon');
        }

        function viewPerformanceDetails(id) {
            alert('Performance details view coming soon');
        }

        function editGoal(id) {
            alert('Edit goal feature coming soon');
        }

        function deleteGoal(id) {
            if (confirm('Are you sure you want to delete this goal?')) {
                fetch('../api/admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete_goal', goal_id: id})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Goal deleted successfully');
                            loadGoals();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    });
            }
        }

        function reassignStructure(employeeId) {
            alert('Reassign structure feature - will open assignment modal with employee pre-selected');
        }

        function viewAIDetails(id) {
            alert('AI interaction details view coming soon');
        }
    </script>
</body>
</html>
