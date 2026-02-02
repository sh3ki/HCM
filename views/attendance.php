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
    <title>Attendance Management - HCM System</title>
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
            <!-- Success/Error Messages -->
            <div id="message-container" class="hidden mb-4">
                <div id="message-alert" class="px-4 py-3 rounded"></div>
            </div>

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Attendance Management</h1>
                <p class="text-gray-600">Track employee attendance and working hours</p>
            </div>

            <!-- Quick Clock Actions -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    <div class="text-sm text-gray-500" id="current-time"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="attendance-controls">
                    <!-- Current Status -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-center">
                            <div id="status-icon" class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100">
                                <i class="fas fa-clock text-gray-500 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Current Status</p>
                            <p id="status-text" class="text-xs text-gray-500 capitalize">Loading...</p>
                        </div>
                    </div>

                    <!-- Clock In/Out -->
                    <div>
                        <button id="clock-btn" class="w-full bg-gray-300 text-gray-500 px-4 py-3 rounded-lg cursor-not-allowed flex items-center justify-center" disabled>
                            <i class="fas fa-clock mr-2"></i>
                            <span>Loading...</span>
                        </button>
                    </div>

                    <!-- Break Actions -->
                    <div>
                        <button id="break-btn" class="w-full bg-gray-300 text-gray-500 px-4 py-3 rounded-lg cursor-not-allowed flex items-center justify-center" disabled>
                            <i class="fas fa-coffee mr-2"></i>
                            <span>Loading...</span>
                        </button>
                    </div>

                    <!-- Today's Hours -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto mb-2 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-hourglass-half text-blue-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Today's Hours</p>
                            <p id="total-hours" class="text-xs text-gray-500">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6" id="summary-cards">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-users text-primary text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Employees</p>
                            <p id="total-employees" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-success text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Present Today</p>
                            <p id="present-today" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-times-circle text-danger text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Absent Today</p>
                            <p id="absent-today" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-warning text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Late Arrivals</p>
                            <p id="late-arrivals" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-sign-out-alt text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Early Departures</p>
                            <p id="early-departures" class="text-2xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter and Search Bar -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="search" id="attendance-search" class="w-full bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary focus:border-primary block pl-10 p-2.5" placeholder="Search by employee name or ID...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-500"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <input type="date" id="date-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5">

                        <select id="status-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Status</option>
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                            <option value="Late">Late</option>
                            <option value="Half Day">Half Day</option>
                        </select>

                        <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Records Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 id="table-title" class="text-lg font-semibold text-gray-900">Attendance Records</h3>
                    <p id="records-count" class="text-sm text-gray-600">Loading...</p>
                </div>

                <div class="overflow-x-auto">
                    <table id="attendance-table" class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 cursor-pointer" data-sort="employee">Employee</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="date">Date</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="time_in">Time In</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="time_out">Time Out</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="break_time">Break Time</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="total_hours">Total Hours</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="status">Status</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-tbody">
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Loading attendance records...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div id="pagination-info" class="text-sm text-gray-700">
                            Loading...
                        </div>
                        <div id="pagination-controls" class="flex space-x-1">
                            <!-- Pagination buttons will be added dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Attendance Details</h3>
                    <button onclick="attendanceManager.closeModal('viewDetailsModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="py-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Employee Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">Employee Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Name:</span>
                                    <span id="modal-employee-name" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Employee ID:</span>
                                    <span id="modal-employee-id" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Department:</span>
                                    <span id="modal-department" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date:</span>
                                    <span id="modal-date" class="font-medium">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Information -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">Attendance Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span id="modal-status" class="px-2 py-1 rounded text-xs font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time In:</span>
                                    <span id="modal-time-in" class="font-medium text-green-600">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time Out:</span>
                                    <span id="modal-time-out" class="font-medium text-red-600">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Late Minutes:</span>
                                    <span id="modal-late-minutes" class="font-medium">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Break Information -->
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">Break Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Break Start:</span>
                                    <span id="modal-break-start" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Break End:</span>
                                    <span id="modal-break-end" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Break Duration:</span>
                                    <span id="modal-break-duration" class="font-medium">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hours Summary -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">Hours Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Hours:</span>
                                    <span id="modal-total-hours" class="font-bold text-lg">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Regular Hours:</span>
                                    <span id="modal-regular-hours" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Overtime Hours:</span>
                                    <span id="modal-overtime-hours" class="font-medium text-orange-600">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Undertime Minutes:</span>
                                    <span id="modal-undertime-minutes" class="font-medium">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Notes</h4>
                        <div id="modal-notes" class="text-gray-600 min-h-[40px] italic">
                            No notes available for this attendance record.
                        </div>
                    </div>

                    <!-- Timeline (if multiple entries) -->
                    <div id="modal-timeline" class="mt-6 hidden">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Timeline</h4>
                        <div class="space-y-2" id="modal-timeline-content">
                            <!-- Timeline items will be inserted here -->
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end pt-4 border-t space-x-3">
                    <button onclick="attendanceManager.openAddNoteModal(attendanceManager.currentViewId)"
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Add Note
                    </button>
                    <button onclick="attendanceManager.closeModal('viewDetailsModal')"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div id="addNoteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Add Note to Attendance Record</h3>
                    <button onclick="attendanceManager.closeModal('addNoteModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="py-6">
                    <form id="addNoteForm">
                        <!-- Employee Info Display -->
                        <div class="bg-gray-50 p-3 rounded-lg mb-4">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <span class="text-sm text-gray-600">Employee:</span>
                                    <span id="note-employee-name" class="font-medium">-</span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Date:</span>
                                    <span id="note-date" class="font-medium">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Note Type Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Note Type</label>
                            <select id="noteType" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
                                <option value="general">General Note</option>
                                <option value="correction">Time Correction</option>
                                <option value="explanation">Explanation</option>
                                <option value="admin">Administrative Note</option>
                                <option value="system">System Note</option>
                            </select>
                        </div>

                        <!-- Note Content -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Note Content</label>
                            <textarea id="noteContent"
                                      rows="4"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                      placeholder="Enter your note here..."
                                      required></textarea>
                        </div>

                        <!-- Priority Level -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="priority" value="low" checked class="form-radio text-blue-600">
                                    <span class="ml-2">Low</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="priority" value="medium" class="form-radio text-yellow-600">
                                    <span class="ml-2">Medium</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="priority" value="high" class="form-radio text-red-600">
                                    <span class="ml-2">High</span>
                                </label>
                            </div>
                        </div>

                        <!-- Visibility Options -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="visibility" value="admin" checked class="form-radio text-blue-600">
                                    <span class="ml-2">Admin Only</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="visibility" value="employee" class="form-radio text-green-600">
                                    <span class="ml-2">Employee Visible</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end pt-4 border-t space-x-3">
                    <button onclick="attendanceManager.saveNote()"
                            class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Save Note
                    </button>
                    <button onclick="attendanceManager.closeModal('addNoteModal')"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center space-x-3">
                <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
                <span class="text-gray-700">Loading...</span>
            </div>
        </div>
    </div>

    <!-- JavaScript for Interactivity -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        // Attendance Management Class
        class AttendanceManager {
            constructor() {
                this.baseURL = '../api/attendance.php';
                this.currentPage = 1;
                this.pageSize = 25;
                this.attendanceStatus = null;
                this.init();
            }

            init() {
                this.updateCurrentTime();
                setInterval(() => this.updateCurrentTime(), 1000);

                this.loadAttendanceStatus();
                this.loadAttendanceRecords();
                this.updateSummaryCards();
                this.setupEventListeners();

                // Refresh status every 30 seconds
                setInterval(() => this.loadAttendanceStatus(), 30000);
                // Refresh summary every 60 seconds
                setInterval(() => this.updateSummaryCards(), 60000);
            }

            async makeAPICall(url, options = {}) {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'Content-Type': 'application/json',
                            ...options.headers
                        },
                        ...options
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                } catch (error) {
                    console.error('API call failed:', error);
                    this.showMessage('Network error occurred. Please try again.', 'error');
                    throw error;
                }
            }

            async loadAttendanceStatus() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=status`);
                    if (data.success) {
                        this.attendanceStatus = data.data;
                        this.updateAttendanceControls();
                        await this.loadTodayAttendance();
                    }
                } catch (error) {
                    console.error('Failed to load attendance status:', error);
                }
            }

            async loadTodayAttendance() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=today`);
                    if (data.success && data.data) {
                        const attendance = data.data;
                        document.getElementById('total-hours').textContent =
                            attendance.total_hours ? `${attendance.total_hours}h` : '0h';
                    }
                } catch (error) {
                    console.error('Failed to load today attendance:', error);
                }
            }

            updateAttendanceControls() {
                const status = this.attendanceStatus;

                // Update status display
                const statusIcon = document.getElementById('status-icon');
                const statusText = document.getElementById('status-text');
                const clockBtn = document.getElementById('clock-btn');
                const breakBtn = document.getElementById('break-btn');

                if (status.is_clocked_in) {
                    if (status.is_on_break) {
                        statusIcon.className = 'w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center bg-yellow-100';
                        statusIcon.querySelector('i').className = 'fas fa-coffee text-yellow-600 text-xl';
                        statusText.textContent = 'On Break';
                    } else {
                        statusIcon.className = 'w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center bg-green-100';
                        statusIcon.querySelector('i').className = 'fas fa-clock text-green-600 text-xl';
                        statusText.textContent = 'Clocked In';
                    }
                } else {
                    statusIcon.className = 'w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center bg-gray-100';
                    statusIcon.querySelector('i').className = 'fas fa-clock text-gray-500 text-xl';
                    statusText.textContent = 'Clocked Out';
                }

                // Update clock button
                if (status.can_clock_in) {
                    clockBtn.className = 'w-full bg-success text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center';
                    clockBtn.disabled = false;
                    clockBtn.onclick = () => this.clockIn();
                    clockBtn.innerHTML = '<i class="fas fa-play mr-2"></i><span>Clock In</span>';
                } else if (status.can_clock_out) {
                    clockBtn.className = 'w-full bg-danger text-white px-4 py-3 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center';
                    clockBtn.disabled = false;
                    clockBtn.onclick = () => this.clockOut();
                    clockBtn.innerHTML = '<i class="fas fa-stop mr-2"></i><span>Clock Out</span>';
                } else {
                    clockBtn.className = 'w-full bg-gray-300 text-gray-500 px-4 py-3 rounded-lg cursor-not-allowed flex items-center justify-center';
                    clockBtn.disabled = true;
                    clockBtn.onclick = null;
                    clockBtn.innerHTML = '<i class="fas fa-clock mr-2"></i><span>Unavailable</span>';
                }

                // Update break button
                if (status.can_start_break) {
                    breakBtn.className = 'w-full bg-warning text-white px-4 py-3 rounded-lg hover:bg-yellow-600 transition-colors flex items-center justify-center';
                    breakBtn.disabled = false;
                    breakBtn.onclick = () => this.startBreak();
                    breakBtn.innerHTML = '<i class="fas fa-coffee mr-2"></i><span>Start Break</span>';
                } else if (status.can_end_break) {
                    breakBtn.className = 'w-full bg-primary text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center';
                    breakBtn.disabled = false;
                    breakBtn.onclick = () => this.endBreak();
                    breakBtn.innerHTML = '<i class="fas fa-play mr-2"></i><span>End Break</span>';
                } else {
                    breakBtn.className = 'w-full bg-gray-300 text-gray-500 px-4 py-3 rounded-lg cursor-not-allowed flex items-center justify-center';
                    breakBtn.disabled = true;
                    breakBtn.onclick = null;
                    breakBtn.innerHTML = '<i class="fas fa-coffee mr-2"></i><span>Start Break</span>';
                }
            }

            async clockIn() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=clock-in`, {
                        method: 'POST'
                    });

                    if (data.success) {
                        this.showMessage('Successfully clocked in!', 'success');
                        await this.loadAttendanceStatus();
                        await this.loadAttendanceRecords();
                    } else {
                        this.showMessage(data.error || 'Failed to clock in', 'error');
                    }
                } catch (error) {
                    console.error('Clock in failed:', error);
                }
            }

            async clockOut() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=clock-out`, {
                        method: 'POST'
                    });

                    if (data.success) {
                        this.showMessage('Successfully clocked out!', 'success');
                        await this.loadAttendanceStatus();
                        await this.loadAttendanceRecords();
                    } else {
                        this.showMessage(data.error || 'Failed to clock out', 'error');
                    }
                } catch (error) {
                    console.error('Clock out failed:', error);
                }
            }

            async startBreak() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=break-start`, {
                        method: 'POST'
                    });

                    if (data.success) {
                        this.showMessage('Break started!', 'success');
                        await this.loadAttendanceStatus();
                    } else {
                        this.showMessage(data.error || 'Failed to start break', 'error');
                    }
                } catch (error) {
                    console.error('Start break failed:', error);
                }
            }

            async endBreak() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=break-end`, {
                        method: 'POST'
                    });

                    if (data.success) {
                        this.showMessage('Break ended!', 'success');
                        await this.loadAttendanceStatus();
                    } else {
                        this.showMessage(data.error || 'Failed to end break', 'error');
                    }
                } catch (error) {
                    console.error('End break failed:', error);
                }
            }

            async loadAttendanceRecords() {
                try {
                    const dateFilter = document.getElementById('date-filter').value;
                    const statusFilter = document.getElementById('status-filter').value;

                    let url = `${this.baseURL}?page=${this.currentPage}&limit=${this.pageSize}`;
                    if (dateFilter) url += `&date_from=${dateFilter}&date_to=${dateFilter}`;
                    if (statusFilter) url += `&status=${statusFilter}`;

                    const data = await this.makeAPICall(url);

                    if (data.success) {
                        this.renderAttendanceTable(data.data.records);
                        this.renderPagination(data.data.pagination);
                        this.updateSummaryCards();
                    }
                } catch (error) {
                    console.error('Failed to load attendance records:', error);
                    document.getElementById('attendance-tbody').innerHTML =
                        '<tr><td colspan="8" class="px-6 py-8 text-center text-red-500">Failed to load records</td></tr>';
                }
            }

            renderAttendanceTable(records) {
                const tbody = document.getElementById('attendance-tbody');

                if (!records || records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No records found</td></tr>';
                    return;
                }

                const rows = records.map(record => {
                    const statusClass = this.getStatusClass(record.status);
                    const breakTime = this.calculateBreakTime(record.break_start, record.break_end);

                    return `
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${record.employee_name || 'N/A'}</div>
                                        <div class="text-gray-500">${record.employee_code || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">${this.formatDate(record.attendance_date)}</td>
                            <td class="px-6 py-4">
                                <span class="${record.time_in ? 'text-green-600' : 'text-gray-400'}">
                                    ${record.time_in || '-'}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="${record.time_out ? 'text-red-600' : 'text-gray-400'}">
                                    ${record.time_out || '-'}
                                </span>
                            </td>
                            <td class="px-6 py-4">${breakTime}</td>
                            <td class="px-6 py-4 font-medium">${record.total_hours || '0'}h</td>
                            <td class="px-6 py-4">
                                <span class="${statusClass} text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                    ${record.status}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800" title="View Details" onclick="attendanceManager.viewDetails(${record.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-800" title="Add Note" onclick="attendanceManager.addNote(${record.id})">
                                        <i class="fas fa-sticky-note"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                tbody.innerHTML = rows;
                document.getElementById('records-count').textContent = `${records.length} records found`;
            }

            getStatusClass(status) {
                switch (status) {
                    case 'Present': return 'bg-green-100 text-green-800';
                    case 'Absent': return 'bg-red-100 text-red-800';
                    case 'Late': return 'bg-yellow-100 text-yellow-800';
                    case 'Half Day': return 'bg-blue-100 text-blue-800';
                    default: return 'bg-gray-100 text-gray-800';
                }
            }

            calculateBreakTime(breakStart, breakEnd) {
                if (!breakStart) return '-';
                if (!breakEnd) return 'In Progress';

                const start = new Date(`1970-01-01T${breakStart}`);
                const end = new Date(`1970-01-01T${breakEnd}`);
                const diff = (end - start) / 1000 / 60; // minutes

                const hours = Math.floor(diff / 60);
                const minutes = Math.floor(diff % 60);

                if (hours > 0) {
                    return `${hours}h ${minutes}m`;
                } else {
                    return `${minutes}m`;
                }
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            renderPagination(pagination) {
                const info = document.getElementById('pagination-info');
                const controls = document.getElementById('pagination-controls');

                const start = ((pagination.page - 1) * pagination.limit) + 1;
                const end = Math.min(pagination.page * pagination.limit, pagination.total);

                info.textContent = `Showing ${start} to ${end} of ${pagination.total} results`;

                let buttons = '';

                // Previous button
                buttons += `
                    <button onclick="attendanceManager.goToPage(${pagination.page - 1})"
                            ${pagination.page <= 1 ? 'disabled' : ''}
                            class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 ${pagination.page <= 1 ? 'cursor-not-allowed opacity-50' : ''}">
                        Previous
                    </button>
                `;

                // Page numbers
                for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.totalPages, pagination.page + 2); i++) {
                    const isActive = i === pagination.page;
                    buttons += `
                        <button onclick="attendanceManager.goToPage(${i})"
                                class="px-3 py-2 text-sm leading-tight ${isActive ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300'} border hover:bg-blue-700">
                            ${i}
                        </button>
                    `;
                }

                // Next button
                buttons += `
                    <button onclick="attendanceManager.goToPage(${pagination.page + 1})"
                            ${pagination.page >= pagination.totalPages ? 'disabled' : ''}
                            class="px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 ${pagination.page >= pagination.totalPages ? 'cursor-not-allowed opacity-50' : ''}">
                        Next
                    </button>
                `;

                controls.innerHTML = buttons;
            }

            async updateSummaryCards() {
                try {
                    const data = await this.makeAPICall(`${this.baseURL}?action=summary`);
                    if (data.success) {
                        const summary = data.data;
                        document.getElementById('total-employees').textContent = summary.total_employees || 0;
                        document.getElementById('present-today').textContent = summary.present_today || 0;
                        document.getElementById('absent-today').textContent = summary.absent_today || 0;
                        document.getElementById('late-arrivals').textContent = summary.late_arrivals || 0;
                        document.getElementById('early-departures').textContent = summary.early_departures || 0;
                    }
                } catch (error) {
                    console.error('Failed to load summary:', error);
                    // Set default values on error
                    document.getElementById('total-employees').textContent = '-';
                    document.getElementById('present-today').textContent = '-';
                    document.getElementById('absent-today').textContent = '-';
                    document.getElementById('late-arrivals').textContent = '-';
                    document.getElementById('early-departures').textContent = '-';
                }
            }

            goToPage(page) {
                this.currentPage = page;
                this.loadAttendanceRecords();
            }

            setupEventListeners() {
                // Set today's date as default (use 2025-09-16 for testing)
                const today = '2025-09-16'; // For testing - normally would be: new Date().toISOString().split('T')[0];
                document.getElementById('date-filter').value = today;

                document.getElementById('date-filter').addEventListener('change', () => {
                    this.currentPage = 1;
                    this.loadAttendanceRecords();
                });

                document.getElementById('status-filter').addEventListener('change', () => {
                    this.currentPage = 1;
                    this.loadAttendanceRecords();
                });

                document.getElementById('attendance-search').addEventListener('input', (e) => {
                    this.searchTable(e.target.value);
                });

                // Add keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    // ESC key to close modals
                    if (e.key === 'Escape') {
                        const modals = ['viewDetailsModal', 'addNoteModal'];
                        modals.forEach(modalId => {
                            const modal = document.getElementById(modalId);
                            if (!modal.classList.contains('hidden')) {
                                this.closeModal(modalId);
                            }
                        });
                    }
                });

                // Click outside modal to close
                ['viewDetailsModal', 'addNoteModal'].forEach(modalId => {
                    document.getElementById(modalId).addEventListener('click', (e) => {
                        if (e.target.id === modalId) {
                            this.closeModal(modalId);
                        }
                    });
                });
            }

            searchTable(searchTerm) {
                const rows = document.querySelectorAll('#attendance-tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const matches = text.includes(searchTerm.toLowerCase());
                    row.style.display = matches ? '' : 'none';
                });
            }

            updateCurrentTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });
                const dateString = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('current-time').textContent = `${dateString} - ${timeString}`;
            }

            showMessage(message, type = 'info') {
                const container = document.getElementById('message-container');
                const alert = document.getElementById('message-alert');

                const typeClasses = {
                    success: 'bg-green-100 border border-green-400 text-green-700',
                    error: 'bg-red-100 border border-red-400 text-red-700',
                    warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
                    info: 'bg-blue-100 border border-blue-400 text-blue-700'
                };

                alert.className = `px-4 py-3 rounded ${typeClasses[type] || typeClasses.info}`;
                alert.textContent = message;
                container.classList.remove('hidden');

                setTimeout(() => {
                    container.classList.add('hidden');
                }, 5000);
            }

            async viewDetails(id) {
                try {
                    this.showLoading(true);
                    this.currentViewId = id;

                    // Get detailed attendance record
                    const data = await this.makeAPICall(`${this.baseURL}?action=details&id=${id}`);

                    if (data.success) {
                        this.populateViewDetailsModal(data.data);
                        this.openModal('viewDetailsModal');
                    } else {
                        this.showMessage('Failed to load attendance details', 'error');
                    }
                } catch (error) {
                    console.error('Failed to load details:', error);
                    this.showMessage('Failed to load attendance details', 'error');
                } finally {
                    this.showLoading(false);
                }
            }

            populateViewDetailsModal(record) {
                // Employee Information
                document.getElementById('modal-employee-name').textContent = record.employee_name || 'Unknown';
                document.getElementById('modal-employee-id').textContent = record.employee_code || 'N/A';
                document.getElementById('modal-department').textContent = record.department || 'N/A';
                document.getElementById('modal-date').textContent = this.formatDate(record.attendance_date);

                // Attendance Information
                const statusElement = document.getElementById('modal-status');
                statusElement.textContent = record.status;
                statusElement.className = `px-2 py-1 rounded text-xs font-medium ${this.getStatusClass(record.status)}`;

                document.getElementById('modal-time-in').textContent = record.time_in || '-';
                document.getElementById('modal-time-out').textContent = record.time_out || '-';
                document.getElementById('modal-late-minutes').textContent = record.late_minutes ? `${record.late_minutes} min` : '0 min';

                // Break Information
                document.getElementById('modal-break-start').textContent = record.break_start || '-';
                document.getElementById('modal-break-end').textContent = record.break_end || '-';
                document.getElementById('modal-break-duration').textContent = this.calculateBreakTime(record.break_start, record.break_end);

                // Hours Summary
                document.getElementById('modal-total-hours').textContent = record.total_hours ? `${record.total_hours}h` : '0h';
                document.getElementById('modal-regular-hours').textContent = record.regular_hours ? `${record.regular_hours}h` : '0h';
                document.getElementById('modal-overtime-hours').textContent = record.overtime_hours ? `${record.overtime_hours}h` : '0h';
                document.getElementById('modal-undertime-minutes').textContent = record.undertime_minutes ? `${record.undertime_minutes} min` : '0 min';

                // Notes
                const notesElement = document.getElementById('modal-notes');
                if (record.notes && record.notes.trim()) {
                    notesElement.innerHTML = `<div class="whitespace-pre-wrap">${record.notes}</div>`;
                    notesElement.classList.remove('italic');
                } else {
                    notesElement.innerHTML = 'No notes available for this attendance record.';
                    notesElement.classList.add('italic');
                }

                // Timeline (if needed)
                this.populateTimeline(record);
            }

            populateTimeline(record) {
                const timelineContainer = document.getElementById('modal-timeline');
                const timelineContent = document.getElementById('modal-timeline-content');

                let events = [];

                if (record.time_in) {
                    events.push({
                        time: record.time_in,
                        event: 'Clocked In',
                        icon: 'fa-sign-in-alt',
                        color: 'text-green-600'
                    });
                }

                if (record.break_start) {
                    events.push({
                        time: record.break_start,
                        event: 'Break Started',
                        icon: 'fa-coffee',
                        color: 'text-yellow-600'
                    });
                }

                if (record.break_end) {
                    events.push({
                        time: record.break_end,
                        event: 'Break Ended',
                        icon: 'fa-play',
                        color: 'text-blue-600'
                    });
                }

                if (record.time_out) {
                    events.push({
                        time: record.time_out,
                        event: 'Clocked Out',
                        icon: 'fa-sign-out-alt',
                        color: 'text-red-600'
                    });
                }

                if (events.length > 1) {
                    timelineContainer.classList.remove('hidden');
                    timelineContent.innerHTML = events.map(event => `
                        <div class="flex items-center space-x-3 p-2 bg-white rounded border">
                            <i class="fas ${event.icon} ${event.color}"></i>
                            <span class="font-medium">${event.time}</span>
                            <span class="text-gray-600">${event.event}</span>
                        </div>
                    `).join('');
                } else {
                    timelineContainer.classList.add('hidden');
                }
            }

            addNote(id) {
                this.openAddNoteModal(id);
            }

            async openAddNoteModal(id) {
                try {
                    this.currentNoteId = id;
                    this.showLoading(true);

                    // Get attendance record for context
                    const data = await this.makeAPICall(`${this.baseURL}?action=details&id=${id}`);

                    if (data.success) {
                        const record = data.data;
                        document.getElementById('note-employee-name').textContent = record.employee_name || 'Unknown';
                        document.getElementById('note-date').textContent = this.formatDate(record.attendance_date);

                        // Reset form
                        document.getElementById('addNoteForm').reset();

                        this.openModal('addNoteModal');
                    } else {
                        this.showMessage('Failed to load record information', 'error');
                    }
                } catch (error) {
                    console.error('Failed to open note modal:', error);
                    this.showMessage('Failed to open note form', 'error');
                } finally {
                    this.showLoading(false);
                }
            }

            async saveNote() {
                const noteType = document.getElementById('noteType').value;
                const noteContent = document.getElementById('noteContent').value.trim();
                const priority = document.querySelector('input[name="priority"]:checked').value;
                const visibility = document.querySelector('input[name="visibility"]:checked').value;

                if (!noteContent) {
                    this.showMessage('Please enter a note', 'warning');
                    return;
                }

                try {
                    this.showLoading(true);

                    const data = await this.makeAPICall(`${this.baseURL}?action=add-note`, {
                        method: 'POST',
                        body: JSON.stringify({
                            attendance_id: this.currentNoteId,
                            note_type: noteType,
                            note_content: noteContent,
                            priority: priority,
                            visibility: visibility
                        })
                    });

                    if (data.success) {
                        this.showMessage('Note saved successfully!', 'success');
                        this.closeModal('addNoteModal');

                        // Refresh the view details modal if it's open
                        if (this.currentViewId === this.currentNoteId) {
                            this.viewDetails(this.currentViewId);
                        }
                    } else {
                        this.showMessage(data.error || 'Failed to save note', 'error');
                    }
                } catch (error) {
                    console.error('Failed to save note:', error);
                    this.showMessage('Failed to save note', 'error');
                } finally {
                    this.showLoading(false);
                }
            }

            openModal(modalId) {
                document.getElementById(modalId).classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
                document.body.style.overflow = 'auto';

                // Clear form data if it's the note modal
                if (modalId === 'addNoteModal') {
                    document.getElementById('addNoteForm').reset();
                }
            }

            showLoading(show) {
                const overlay = document.getElementById('loadingOverlay');
                if (show) {
                    overlay.classList.remove('hidden');
                } else {
                    overlay.classList.add('hidden');
                }
            }
        }

        // Initialize attendance manager when page loads
        let attendanceManager;
        document.addEventListener('DOMContentLoaded', function() {
            attendanceManager = new AttendanceManager();
        });
    </script>
</body>
</html>