<script>
// Toggle sidebar
document.getElementById('toggleSidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
});

// Toggle user dropdown
document.getElementById('user-menu-button').addEventListener('click', function() {
    document.getElementById('dropdown').classList.toggle('hidden');
});

// Close dropdown when clicking outside
window.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdown');
    const button = document.getElementById('user-menu-button');
    if (!button.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// OTP modal logic for new users
(function initOtpModal() {
    const otpModal = document.getElementById('otp-modal');
    if (!otpModal) return;

    const otpInput = document.getElementById('otp-input');
    const confirmBtn = document.getElementById('otp-confirm');
    const resendBtn = document.getElementById('otp-resend');
    const otpMessage = document.getElementById('otp-message');

    document.body.classList.add('overflow-hidden');

    async function sendOtp() {
        otpMessage.textContent = '';
        otpMessage.classList.remove('text-red-600', 'text-green-600');
        resendBtn.disabled = true;
        resendBtn.classList.add('opacity-60');

        try {
            const response = await fetch('/HCM/api/otp.php?action=send', { method: 'POST' });
            
            if (!response.ok) {
                console.error('OTP API HTTP error:', response.status, response.statusText);
                otpMessage.className = 'text-red-600 text-sm mt-2';
                otpMessage.textContent = `HTTP Error: ${response.status}`;
                return;
            }
            
            const data = await response.json();
            console.log('OTP API response:', data);
            
            if (data.success) {
                otpMessage.className = 'text-green-600 text-sm mt-2';
                otpMessage.textContent = 'OTP sent to your email';
            } else {
                otpMessage.className = 'text-red-600 text-sm mt-2';
                otpMessage.textContent = data.error || 'Failed to send OTP';
            }
        } catch (error) {
            console.error('OTP send error:', error);
            otpMessage.className = 'text-red-600 text-sm mt-2';
            otpMessage.textContent = 'Failed to send OTP. Please try again.';
        } finally {
            setTimeout(() => {
                resendBtn.disabled = false;
                resendBtn.classList.remove('opacity-60');
            }, 60000);
        }
    }

    async function verifyOtp() {
        otpMessage.textContent = '';
        const otp = (otpInput.value || '').trim();
        if (!otp) {
            otpMessage.textContent = 'Please enter the OTP.';
            return;
        }

        try {
            const response = await fetch('/HCM/api/otp.php?action=verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp })
            });
            const data = await response.json();
            if (data.success) {
                location.reload();
                return;
            }
            otpMessage.textContent = data.error || 'Invalid OTP.';
        } catch (error) {
            console.error('OTP verify error:', error);
            otpMessage.textContent = 'Verification failed. Please try again.';
        }
    }

    confirmBtn.addEventListener('click', verifyOtp);
    resendBtn.addEventListener('click', sendOtp);
    otpInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            verifyOtp();
        }
    });

    sendOtp();
})();

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });

    return isValid;
}

// Success message display
function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded max-w-sm';
    alert.innerHTML = `
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">Success!</p>
                <p class="text-sm">${message}</p>
            </div>
        </div>
    `;

    document.body.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Error message display
function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'fixed top-4 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded max-w-sm';
    alert.innerHTML = `
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">Error!</p>
                <p class="text-sm">${message}</p>
            </div>
        </div>
    `;

    document.body.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Search functionality
function initializeSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Data table initialization
function initializeDataTable() {
    // Add sorting functionality to tables
    const sortableHeaders = document.querySelectorAll('[data-sort]');

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = this.dataset.sort;
            const order = this.dataset.order === 'asc' ? 'desc' : 'asc';

            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.querySelector(`[data-sort="${column}"]`).textContent;
                const bValue = b.querySelector(`[data-sort="${column}"]`).textContent;

                return order === 'asc'
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });

            // Update table
            rows.forEach(row => tbody.appendChild(row));

            // Update header
            this.dataset.order = order;
        });
    });
}

// Global search functionality
function initializeGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    const searchContent = document.getElementById('searchContent');
    const searchLoading = document.getElementById('searchLoading');
    let searchTimeout;

    if (!searchInput) return; // Exit if search input doesn't exist

    // Get search context from data attribute
    const searchContext = searchInput.getAttribute('data-context') || 'global';

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            hideSearchResults();
            return;
        }

        // Show loading
        searchLoading.classList.remove('hidden');
        searchResults.classList.add('hidden');

        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300); // Debounce for 300ms
    });

    searchInput.addEventListener('blur', function() {
        // Hide results after a short delay to allow clicking on results
        setTimeout(() => {
            hideSearchResults();
        }, 200);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            searchResults.classList.remove('hidden');
        }
    });

    async function performSearch(query) {
        try {
            const response = await fetch(`../api/search.php?q=${encodeURIComponent(query)}&limit=10&context=${encodeURIComponent(searchContext)}`);
            const data = await response.json();

            searchLoading.classList.add('hidden');

            if (data.success) {
                displaySearchResults(data.data, searchContext);
            } else {
                displayError('Search failed');
            }
        } catch (error) {
            searchLoading.classList.add('hidden');
            displayError('Search error');
            console.error('Search error:', error);
        }
    }

    function displaySearchResults(results, context) {
        let html = '';

        if (results.total === 0) {
            html = '<div class="p-3 text-gray-500 text-sm">No results found</div>';
        } else {
            // Employees section - shown in all contexts
            if (results.employees.length > 0) {
                const sectionLabel = context === 'global' ? 'Employees' : 
                                   context === 'payroll' ? 'Employees in Payroll' :
                                   context === 'reports' ? 'Employees in Reports' :
                                   context === 'leaves' ? 'Employees with Leaves' :
                                   context === 'benefits' ? 'Employees with Benefits' :
                                   context === 'attendance' ? 'Employees in Attendance' : 'Employees';
                
                html += `<div class="border-b border-gray-100 pb-2 mb-2"><div class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">${sectionLabel}</div>`;
                results.employees.forEach(employee => {
                    const targetPage = context === 'payroll' ? 'payroll.php' :
                                     context === 'reports' ? 'reports.php' :
                                     context === 'leaves' ? 'leaves.php' :
                                     context === 'benefits' ? 'benefits.php' :
                                     context === 'attendance' ? 'attendance.php' : 'employees.php';
                    
                    html += `
                        <a href="${targetPage}?id=${employee.id}" class="block px-3 py-2 hover:bg-gray-50 rounded-md">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${employee.first_name} ${employee.last_name}</div>
                                    <div class="text-sm text-gray-500">${employee.position_title || ''} ${employee.dept_name ? '• ' + employee.dept_name : ''}</div>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // Departments section - only shown in global context
            if (context === 'global' && results.departments.length > 0) {
                html += '<div class="border-b border-gray-100 pb-2 mb-2"><div class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">Departments</div>';
                results.departments.forEach(dept => {
                    html += `
                        <a href="employees.php?dept=${dept.id}" class="block px-3 py-2 hover:bg-gray-50 rounded-md">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-building text-green-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${dept.name}</div>
                                    <div class="text-sm text-gray-500">${dept.description || 'Department'}</div>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // Positions section - only shown in global context
            if (context === 'global' && results.positions.length > 0) {
                html += '<div><div class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">Positions</div>';
                results.positions.forEach(position => {
                    html += `
                        <a href="employees.php?position=${position.id}" class="block px-3 py-2 hover:bg-gray-50 rounded-md">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-briefcase text-purple-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${position.name}</div>
                                    <div class="text-sm text-gray-500">Position</div>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }
        }

        searchContent.innerHTML = html;
        searchResults.classList.remove('hidden');
    }

    function displayError(message) {
        searchContent.innerHTML = `<div class="p-3 text-red-500 text-sm">${message}</div>`;
        searchResults.classList.remove('hidden');
    }

    function hideSearchResults() {
        searchResults.classList.add('hidden');
        searchLoading.classList.add('hidden');
    }

    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeGlobalSearch();
});
</script>

<!-- Include HCM Chatbot -->
<?php include __DIR__ . '/../../includes/chatbot.php'; ?>