<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Check if user has employee role and appropriate permissions
$currentUser = getCurrentUser();
checkPermission(['tax_deduction']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Deductions - HCM System</title>
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
            <!-- Notification Container -->
            <div id="notificationContainer" class="fixed top-20 right-4 z-40 space-y-2"></div>

            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <i class="fas fa-receipt mr-3"></i>My Tax & Deductions
                        </h1>
                        <p class="text-green-100">Track your tax withholdings and mandatory contributions</p>
                    </div>
                    <div class="hidden md:block">
                        <i class="fas fa-calculator text-white text-6xl opacity-20"></i>
                    </div>
                </div>
            </div>

            <!-- Year Filter -->
            <div class="bg-white rounded-lg shadow-md border-l-4 border-green-500 p-5 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-calendar text-green-500 text-xl"></i>
                        <div class="flex-1">
                            <label for="yearFilter" class="block text-sm font-semibold text-gray-700 mb-2">Select Year:</label>
                            <select id="yearFilter" onchange="loadTaxData()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-calendar-day text-green-500 text-xl"></i>
                        <div class="flex-1">
                            <label for="monthFilter" class="block text-sm font-semibold text-gray-700 mb-2">Select Month:</label>
                            <select id="monthFilter" onchange="loadTaxData()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadTaxData()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg transition flex items-center justify-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            Refresh Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tax Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-md border-l-4 border-blue-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-blue-800">Income Tax</h3>
                        <div class="bg-blue-500 rounded-full p-2">
                            <i class="fas fa-money-bill-wave text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="totalIncomeTax" class="text-3xl font-bold text-blue-900">₱0.00</p>
                    <p class="text-xs text-blue-600 mt-2 period-text"><i class="fas fa-calendar-check mr-1"></i>Your total for this year</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-md border-l-4 border-green-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-green-800">SSS</h3>
                        <div class="bg-green-500 rounded-full p-2">
                            <i class="fas fa-shield-alt text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="totalSSS" class="text-3xl font-bold text-green-900">₱0.00</p>
                    <p class="text-xs text-green-600 mt-2 period-text"><i class="fas fa-calendar-check mr-1"></i>Your contributions</p>
                </div>

                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg shadow-md border-l-4 border-red-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-red-800">PhilHealth</h3>
                        <div class="bg-red-500 rounded-full p-2">
                            <i class="fas fa-heartbeat text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="totalPhilHealth" class="text-3xl font-bold text-red-900">₱0.00</p>
                    <p class="text-xs text-red-600 mt-2 period-text"><i class="fas fa-calendar-check mr-1"></i>Health contributions</p>
                </div>

                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg shadow-md border-l-4 border-yellow-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-yellow-800">Pag-IBIG</h3>
                        <div class="bg-yellow-500 rounded-full p-2">
                            <i class="fas fa-home text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="totalPagIBIG" class="text-3xl font-bold text-yellow-900">₱0.00</p>
                    <p class="text-xs text-yellow-600 mt-2 period-text"><i class="fas fa-calendar-check mr-1"></i>Housing fund</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Tax Deductions Chart -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
                        <i class="fas fa-chart-bar text-primary mr-2"></i>Your Monthly Tax Payments
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">Track your monthly income tax deductions</p>
                    <div style="height: 250px;">
                        <canvas id="monthlyTaxChart"></canvas>
                    </div>
                </div>

                <!-- Deduction Breakdown Chart -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
                        <i class="fas fa-chart-pie text-primary mr-2"></i>Your Total Deductions
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">See where your contributions go</p>
                    <div style="height: 250px;">
                        <canvas id="deductionBreakdownChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Tax Deductions Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-list-alt text-primary mr-2"></i>Your Deduction History
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">Detailed breakdown of all your deductions</p>
                        </div>
                        <button onclick="exportToCSV()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-file-excel mr-2"></i>Export to CSV
                        </button>
                    </div>
                    <div id="taxTable" class="overflow-x-auto">
                        <div class="text-center py-8">
                            <div class="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                            <p class="text-gray-600">Loading tax data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '../api';
        let taxData = [];
        let monthlyTaxChart, deductionBreakdownChart;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            populateYearFilter();
            loadTaxData();
        });

        // Populate year filter
        function populateYearFilter() {
            const yearFilter = document.getElementById('yearFilter');
            const currentYear = new Date().getFullYear();
            
            yearFilter.innerHTML = '';
            for (let year = currentYear; year >= currentYear - 5; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true;
                yearFilter.appendChild(option);
            }
        }

        // Load tax data from API
        async function loadTaxData() {
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;

            try {
                const token = localStorage.getItem('token');
                let url = `${API_BASE_URL}/payroll.php?action=my_tax_deductions&year=${year}`;
                if (month) {
                    url += `&month=${month}`;
                }
                
                const response = await fetch(url, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const data = await response.json();
                if (data.success) {
                    taxData = data.data || [];
                    updateSummaryCards();
                    updateCharts();
                    displayTaxTable();
                } else {
                    throw new Error(data.message || 'Failed to load tax data');
                }
            } catch (error) {
                console.error('Error loading tax data:', error);
                document.getElementById('taxTable').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                        <p class="text-gray-600">Unable to load tax deduction data</p>
                    </div>
                `;
            }
        }

        // Update summary cards
        function updateSummaryCards() {
            const totals = {
                incomeTax: 0,
                sss: 0,
                philHealth: 0,
                pagIBIG: 0
            };

            taxData.forEach(record => {
                totals.incomeTax += parseFloat(record.income_tax || 0);
                totals.sss += parseFloat(record.sss || 0);
                totals.philHealth += parseFloat(record.philhealth || 0);
                totals.pagIBIG += parseFloat(record.pagibig || 0);
            });

            const month = document.getElementById('monthFilter').value;
            const periodText = month ? 'for selected month' : 'year to date';

            document.getElementById('totalIncomeTax').textContent = `₱${totals.incomeTax.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
            document.getElementById('totalSSS').textContent = `₱${totals.sss.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
            document.getElementById('totalPhilHealth').textContent = `₱${totals.philHealth.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
            document.getElementById('totalPagIBIG').textContent = `₱${totals.pagIBIG.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
            
            // Update period text in cards
            document.querySelectorAll('.period-text').forEach(el => {
                el.textContent = periodText;
            });
        }

        // Update charts
        function updateCharts() {
            // Monthly Tax Chart
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthlyData = new Array(12).fill(0);

            taxData.forEach(record => {
                const month = new Date(record.pay_period_end).getMonth();
                monthlyData[month] += parseFloat(record.income_tax || 0);
            });

            const ctx1 = document.getElementById('monthlyTaxChart');
            if (monthlyTaxChart) monthlyTaxChart.destroy();
            
            monthlyTaxChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Income Tax',
                        data: monthlyData,
                        backgroundColor: 'rgba(27, 104, 255, 0.7)',
                        borderColor: 'rgba(27, 104, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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
                                    return 'Tax: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });

            // Deduction Breakdown Pie Chart
            const totals = {
                incomeTax: 0,
                sss: 0,
                philHealth: 0,
                pagIBIG: 0
            };

            taxData.forEach(record => {
                totals.incomeTax += parseFloat(record.income_tax || 0);
                totals.sss += parseFloat(record.sss || 0);
                totals.philHealth += parseFloat(record.philhealth || 0);
                totals.pagIBIG += parseFloat(record.pagibig || 0);
            });

            const ctx2 = document.getElementById('deductionBreakdownChart');
            if (deductionBreakdownChart) deductionBreakdownChart.destroy();

            deductionBreakdownChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Income Tax', 'SSS', 'PhilHealth', 'Pag-IBIG'],
                    datasets: [{
                        data: [totals.incomeTax, totals.sss, totals.philHealth, totals.pagIBIG],
                        backgroundColor: [
                            'rgba(27, 104, 255, 0.7)',
                            'rgba(58, 210, 159, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(251, 191, 36, 0.7)'
                        ],
                        borderColor: [
                            'rgba(27, 104, 255, 1)',
                            'rgba(58, 210, 159, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(251, 191, 36, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ₱' + context.parsed.toLocaleString('en-PH', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }

        // Display tax table
        function displayTaxTable() {
            const container = document.getElementById('taxTable');

            if (!taxData || taxData.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No tax deduction records found</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Tax</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SSS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PhilHealth</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pag-IBIG</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Deductions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            taxData.forEach(record => {
                const totalDeductions = parseFloat(record.income_tax || 0) + 
                                       parseFloat(record.sss || 0) + 
                                       parseFloat(record.philhealth || 0) + 
                                       parseFloat(record.pagibig || 0);

                html += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(record.pay_period_start)} - ${formatDate(record.pay_period_end)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ₱${parseFloat(record.gross_pay || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₱${parseFloat(record.income_tax || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₱${parseFloat(record.sss || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₱${parseFloat(record.philhealth || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₱${parseFloat(record.pagibig || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-700">
                            ₱${totalDeductions.toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // Export to CSV
        function exportToCSV() {
            if (!taxData || taxData.length === 0) {
                showNotification('No data to export', 'warning');
                return;
            }

            let csv = 'Period Start,Period End,Gross Pay,Income Tax,SSS,PhilHealth,Pag-IBIG,Total Deductions\n';

            taxData.forEach(record => {
                const totalDeductions = parseFloat(record.income_tax || 0) + 
                                       parseFloat(record.sss || 0) + 
                                       parseFloat(record.philhealth || 0) + 
                                       parseFloat(record.pagibig || 0);

                csv += `${record.pay_period_start},${record.pay_period_end},${record.gross_pay || 0},${record.income_tax || 0},${record.sss || 0},${record.philhealth || 0},${record.pagibig || 0},${totalDeductions}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tax_deductions_${document.getElementById('yearFilter').value}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showNotification('Tax deductions exported successfully', 'success');
        }

        // Helper: Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };

            const notification = document.createElement('div');
            notification.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-500`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            container.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
    </script>

    <!-- Include common scripts -->
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
