<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Check if user has employee role and appropriate permissions
$currentUser = getCurrentUser();
checkPermission(['payslip']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips - HCM System</title>
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
            <!-- Notification Container -->
            <div id="notificationContainer" class="fixed top-20 right-4 z-40 space-y-2"></div>

            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <i class="fas fa-file-invoice-dollar mr-3"></i>My Payslips
                        </h1>
                        <p class="text-blue-100">Welcome! Here you can view and download all your payment records</p>
                    </div>
                    <div class="hidden md:block">
                        <i class="fas fa-wallet text-white text-6xl opacity-20"></i>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-md border-l-4 border-primary p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-filter text-primary mr-2"></i>Filter Your Payslips
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="yearFilter" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt text-gray-500 mr-1"></i>Year
                        </label>
                        <select id="yearFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label for="monthFilter" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select id="monthFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
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
                    <div class="flex items-end">
                        <button onclick="filterPayslips()" class="w-full bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payslips List -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-history text-primary mr-2"></i>Your Payment History
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">All your payslips in one place</p>
                        </div>
                    </div>
                    <div id="payslipsTable" class="overflow-x-auto">
                        <div class="text-center py-8">
                            <div class="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                            <p class="text-gray-600">Loading payslips...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payslip Modal (same layout as admin payroll) -->
    <div id="payslip-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('payslip-modal')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Employee Payslip</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('payslip-modal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="payslip-content" class="space-y-4">
                        <!-- Payslip content will be inserted here -->
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                        <button type="button" class="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400" onclick="closeModal('payslip-modal')">Close</button>
                        <button type="button" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700" onclick="printPayslip()">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '../api';
        let allPayslips = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadPayslips();
            populateYearFilter();
        });

        // Populate year filter
        function populateYearFilter() {
            const yearFilter = document.getElementById('yearFilter');
            const currentYear = new Date().getFullYear();
            
            yearFilter.innerHTML = '<option value="">All Years</option>';
            for (let year = currentYear; year >= currentYear - 5; year--) {
                yearFilter.innerHTML += `<option value="${year}">${year}</option>`;
            }
        }

        // Load payslips from API
        async function loadPayslips() {
            try {
                const response = await fetch(`${API_BASE_URL}/payroll.php?action=my_payslips`);
                const data = await response.json();

                if (data.success) {
                    allPayslips = data.data || [];
                    displayPayslips(allPayslips);
                } else {
                    throw new Error(data.error || data.message || 'Failed to load payslips');
                }
            } catch (error) {
                console.error('Error loading payslips:', error);
                document.getElementById('payslipsTable').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                        <p class="text-gray-600">No payslips found or unable to load data</p>
                    </div>
                `;
            }
        }

        // Filter payslips
        function filterPayslips() {
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;

            let filtered = allPayslips;

            if (year) {
                filtered = filtered.filter(p => new Date(p.pay_date).getFullYear() == year);
            }

            if (month) {
                filtered = filtered.filter(p => new Date(p.pay_date).getMonth() + 1 == month);
            }

            displayPayslips(filtered);
        }

        // Display payslips in table
        function displayPayslips(payslips) {
            const container = document.getElementById('payslipsTable');

            if (!payslips || payslips.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No payslips found</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            payslips.forEach(payslip => {
                const statusValue = (payslip.status || '').toString().toLowerCase();
                const statusClass = statusValue === 'paid' ? 'bg-green-100 text-green-800' : 
                                   statusValue === 'approved' ? 'bg-blue-100 text-blue-800' :
                                   statusValue === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                   statusValue === 'draft' ? 'bg-gray-100 text-gray-800' :
                                   'bg-gray-100 text-gray-800';

                html += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(payslip.pay_period_start)} - ${formatDate(payslip.pay_period_end)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(payslip.pay_date)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ₱${parseFloat(payslip.gross_pay).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₱${parseFloat(payslip.total_deductions).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            ₱${parseFloat(payslip.net_pay).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="${statusClass} text-xs font-medium px-2.5 py-0.5 rounded">
                                ${payslip.status || 'Unknown'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="viewPayslip(${payslip.id})" class="text-primary hover:text-blue-700 mr-3" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="downloadPayslip(${payslip.id})" class="text-green-600 hover:text-green-700 mr-3" title="Download">
                                <i class="fas fa-download"></i>
                            </button>
                            <button onclick="printPayslip(${payslip.id})" class="text-gray-600 hover:text-gray-700" title="Print">
                                <i class="fas fa-print"></i>
                            </button>
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

        // View payslip details
        async function viewPayslip(payslipId) {
            try {
                const response = await fetch(`${API_BASE_URL}/payroll.php?action=get_payslip&id=${payslipId}`);
                const data = await response.json();

                if (data.success) {
                    showPayslipModal(data.data);
                } else {
                    showNotification('Failed to load payslip details', 'error');
                }
            } catch (error) {
                console.error('Error viewing payslip:', error);
                showNotification('Error loading payslip', 'error');
            }
        }

        // Download payslip as PDF
        async function downloadPayslip(payslipId) {
            try {
                const response = await fetch(`${API_BASE_URL}/payroll.php?action=get_payslip&id=${payslipId}`);
                const data = await response.json();

                if (data.success) {
                    openPayslipPrintWindow(data.data, true);
                } else {
                    showNotification('Failed to download payslip', 'error');
                }
            } catch (error) {
                console.error('Error downloading payslip:', error);
                showNotification('Error downloading payslip', 'error');
            }
        }

        // Print payslip
        async function printPayslip(payslipId = null) {
            try {
                if (payslipId) {
                    const response = await fetch(`${API_BASE_URL}/payroll.php?action=get_payslip&id=${payslipId}`);
                    const data = await response.json();
                    if (!data.success) {
                        showNotification('Failed to load payslip for print', 'error');
                        return;
                    }
                    openPayslipPrintWindow(data.data, false);
                } else if (window.currentPayslipData) {
                    openPayslipPrintWindow(window.currentPayslipData, false);
                }
            } catch (error) {
                console.error('Error printing payslip:', error);
                showNotification('Error printing payslip', 'error');
            }
        }

        function buildPayslipHtml(payslip) {
            const payPeriodLabel = `${formatDate(payslip.pay_period_start)} - ${formatDate(payslip.pay_period_end)}`;

            const deductions = {
                incomeTax: parseFloat(payslip.withholding_tax || 0),
                sss: parseFloat(payslip.sss_contribution || 0),
                philhealth: parseFloat(payslip.philhealth_contribution || 0),
                pagibig: parseFloat(payslip.pagibig_contribution || 0)
            };

            const deductionsTotal = parseFloat(payslip.total_deductions || 0);
            const fallbackTotal = deductions.incomeTax + deductions.sss + deductions.philhealth + deductions.pagibig;
            const totalDeductions = deductionsTotal || fallbackTotal;

            return `
                <div class="bg-white border rounded-lg p-6">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">PAYSLIP</h2>
                        <p class="text-gray-600">Pay Period: ${payPeriodLabel}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Employee Information</h3>
                            <div class="space-y-1 text-sm">
                                <p><span class="font-medium">Name:</span> ${payslip.employee_name}</p>
                                <p><span class="font-medium">Employee ID:</span> ${payslip.employee_code}</p>
                                <p><span class="font-medium">Department:</span> ${payslip.department || 'N/A'}</p>
                                <p><span class="font-medium">Position:</span> ${payslip.position_title || 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Pay Details</h3>
                            <div class="space-y-1 text-sm">
                                <p><span class="font-medium">Pay Date:</span> ${formatDate(payslip.pay_date)}</p>
                                <p><span class="font-medium">Pay Method:</span> Bank Transfer</p>
                                <p><span class="font-medium">Currency:</span> PHP</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3 text-green-700">EARNINGS</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Basic Salary:</span>
                                    <span class="font-medium">₱${formatCurrency(payslip.basic_salary)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Allowances:</span>
                                    <span class="font-medium">₱${formatCurrency(payslip.allowances)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Overtime:</span>
                                    <span class="font-medium">₱${formatCurrency(payslip.overtime_pay)}</span>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <div class="flex justify-between font-semibold text-green-700">
                                        <span>Total Earnings:</span>
                                        <span>₱${formatCurrency(payslip.gross_pay)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3 text-red-700">DEDUCTIONS</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Income Tax:</span>
                                    <span class="font-medium">₱${formatCurrency(deductions.incomeTax)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>SSS:</span>
                                    <span class="font-medium">₱${formatCurrency(deductions.sss)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>PhilHealth:</span>
                                    <span class="font-medium">₱${formatCurrency(deductions.philhealth)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pag-IBIG:</span>
                                    <span class="font-medium">₱${formatCurrency(deductions.pagibig)}</span>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <div class="flex justify-between font-semibold text-red-700">
                                        <span>Total Deductions:</span>
                                        <span>₱${formatCurrency(totalDeductions)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t mt-6 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900">NET PAY:</span>
                            <span class="text-2xl font-bold text-blue-600">₱${formatCurrency(payslip.net_pay)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        function showPayslipModal(payslip) {
            const payslipContent = document.getElementById('payslip-content');
            payslipContent.innerHTML = buildPayslipHtml(payslip);

            window.currentPayslipData = payslip;
            openModal('payslip-modal');
        }

        function openPayslipPrintWindow(payslip, autoPrint = false) {
            const payslipContent = buildPayslipHtml(payslip);
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payslip - ${payslip.employee_name || 'Employee'}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .grid { display: grid; }
                        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
                        .gap-6 { gap: 1.5rem; }
                        .mb-6 { margin-bottom: 1.5rem; }
                        .font-bold { font-weight: bold; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .text-gray-600 { color: #4b5563; }
                        .text-gray-900 { color: #111827; }
                        .text-green-700 { color: #15803d; }
                        .text-red-700 { color: #b91c1c; }
                        .text-blue-600 { color: #2563eb; }
                        .border { border: 1px solid #e5e7eb; }
                        .rounded-lg { border-radius: 0.5rem; }
                        .p-6 { padding: 1.5rem; }
                        .pt-2 { padding-top: 0.5rem; }
                        .pt-4 { padding-top: 1rem; }
                        .mt-2 { margin-top: 0.5rem; }
                        .mt-6 { margin-top: 1.5rem; }
                        .border-t { border-top: 1px solid #e5e7eb; }
                        .space-y-1 > * + * { margin-top: 0.25rem; }
                        .space-y-2 > * + * { margin-top: 0.5rem; }
                        .text-sm { font-size: 0.875rem; }
                        .text-xl { font-size: 1.25rem; }
                        .text-2xl { font-size: 1.5rem; }
                        .font-semibold { font-weight: 600; }
                        .flex { display: flex; }
                        .justify-between { justify-content: space-between; }
                        .items-center { align-items: center; }
                    </style>
                </head>
                <body>
                    ${payslipContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            if (autoPrint) {
                printWindow.focus();
                printWindow.print();
            }
        }

        function formatCurrency(value) {
            const number = parseFloat(value || 0);
            return number.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Helper: Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
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
