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
    <title>Profile - HCM System</title>
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

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center">
            <div class="animate-spin h-12 w-12 border-4 border-primary border-t-transparent mx-auto mb-4"></div>
            <p class="text-gray-600">Loading profile data...</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-4 sm:ml-64">
        <div class="p-4 rounded-lg mt-14">
            <!-- Notification Container -->
            <div id="notificationContainer" class="fixed top-20 right-4 z-40 space-y-2"></div>

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
                <p class="text-gray-600">Manage your personal information and account settings</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Overview Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="text-center">
                            <div id="profileAvatar" class="w-24 h-24 mx-auto bg-primary rounded-full flex items-center justify-center text-white text-2xl font-bold mb-4">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <h3 id="profileName" class="text-lg font-semibold text-gray-900">
                                <!-- Will be populated by JavaScript -->
                            </h3>
                            <p id="profileRole" class="text-sm text-gray-600">
                                <!-- Will be populated by JavaScript -->
                            </p>
                            <p id="profileEmail" class="text-sm text-gray-500">
                                <!-- Will be populated by JavaScript -->
                            </p>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <div id="profileDetails" class="space-y-3 text-sm">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                            <p class="text-sm text-gray-600">Update your personal details and contact information</p>
                        </div>

                        <form id="profileForm" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" id="first_name" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="employee_email" id="employee_email" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" name="phone" id="phone"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                    <input type="text" name="country" id="country" value="Philippines" readonly
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" id="address" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                    <input type="text" name="city" id="city"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                                    <input type="text" name="state" id="state"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ZIP Code</label>
                                    <input type="text" name="zip_code" id="zip_code"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>

                            <!-- Emergency Contact Section -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Emergency Contact Information</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                                        <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Relationship</label>
                                        <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship"
                                               placeholder="e.g., Spouse, Parent, Sibling"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" id="updateProfileBtn" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                            <p class="text-sm text-gray-600">Ensure your account is using a strong password</p>
                        </div>

                        <form id="passwordForm" class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" name="new_password" id="new_password" required minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" id="changePasswordBtn" class="bg-danger text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Activity -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Account Activity</h3>
                            <p class="text-sm text-gray-600">Recent activity on your account</p>
                        </div>

                        <div class="p-6">
                            <div id="accountActivity" class="space-y-4">
                                <!-- Will be populated by JavaScript -->
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
        // Client-Side Profile Manager
        class ClientProfileManager {
            constructor() {
                this.apiBase = '../api/profile.php';
                this.profileData = null;
                this.init();
            }

            async init() {
                await this.loadProfileData();
                this.bindEvents();
                this.hideLoading();
            }

            async loadProfileData() {
                try {
                    const response = await fetch(this.apiBase);
                    const result = await response.json();

                    if (result.success) {
                        this.profileData = result.data;
                        this.renderProfile();
                    } else {
                        this.showNotification(result.error || 'Failed to load profile', 'error');
                    }
                } catch (error) {
                    console.error('Load profile error:', error);
                    this.showNotification('Failed to load profile data', 'error');
                }
            }

            renderProfile() {
                if (!this.profileData) return;

                const data = this.profileData;

                // Profile Avatar
                const initials = this.getInitials(data.first_name, data.last_name, data.username);
                document.getElementById('profileAvatar').textContent = initials;

                // Profile Name
                const fullName = this.getFullName(data.first_name, data.middle_name, data.last_name) || data.username;
                document.getElementById('profileName').textContent = fullName;

                // Profile Role and Email
                document.getElementById('profileRole').textContent = data.role_name || 'User';
                document.getElementById('profileEmail').textContent = data.employee_email || data.email || '';

                // Profile Details
                this.renderProfileDetails(data);

                // Form Fields
                this.populateForm(data);

                // Account Activity
                this.renderAccountActivity(data);
            }

            getInitials(firstName, lastName, username) {
                if (firstName && lastName) {
                    return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
                } else if (username) {
                    return username.substring(0, 2).toUpperCase();
                } else {
                    return 'U';
                }
            }

            getFullName(firstName, middleName, lastName) {
                const parts = [firstName, middleName, lastName].filter(part => part && part.trim());
                return parts.join(' ');
            }

            renderProfileDetails(data) {
                const details = [
                    { label: 'Employee ID', value: data.employee_id || 'N/A' },
                    { label: 'Department', value: data.department_name || 'N/A' },
                    { label: 'Position', value: data.position_title || 'N/A' },
                    { label: 'Hire Date', value: data.hire_date ? this.formatDate(data.hire_date) : 'N/A' },
                    { label: 'Status', value: data.employment_status || 'Active', isStatus: true }
                ];

                const container = document.getElementById('profileDetails');
                container.innerHTML = details.map(item => `
                    <div class="flex justify-between">
                        <span class="text-gray-600">${item.label}:</span>
                        <span class="font-medium ${item.isStatus ? this.getStatusClass(item.value) : ''}">${item.value}</span>
                    </div>
                `).join('');
            }

            populateForm(data) {
                const fields = [
                    'first_name', 'middle_name', 'last_name', 'employee_email', 'phone',
                    'date_of_birth', 'address', 'city', 'state', 'zip_code',
                    'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'
                ];

                fields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element && data[field]) {
                        element.value = data[field];
                    }
                });
            }

            renderAccountActivity(data) {
                const activities = [
                    {
                        icon: 'fas fa-sign-in-alt',
                        iconBg: 'bg-green-100',
                        iconColor: 'text-green-600',
                        title: 'Last Login',
                        description: data.last_login ? this.formatDateTime(data.last_login) : 'Never'
                    },
                    {
                        icon: 'fas fa-user-shield',
                        iconBg: 'bg-blue-100',
                        iconColor: 'text-blue-600',
                        title: 'Account Status',
                        description: data.is_active ? 'Active' : 'Inactive',
                        status: data.is_active ? 'Active' : 'Inactive'
                    }
                ];

                const container = document.getElementById('accountActivity');
                container.innerHTML = activities.map(activity => `
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <div class="w-8 h-8 ${activity.iconBg} rounded-full flex items-center justify-center mr-3">
                                <i class="${activity.icon} ${activity.iconColor} text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${activity.title}</p>
                                <p class="text-xs text-gray-500">${activity.description}</p>
                            </div>
                        </div>
                        ${activity.status ? `<span class="px-2 py-1 text-xs rounded-full ${this.getStatusClass(activity.status)}">${activity.status}</span>` : ''}
                    </div>
                `).join('');
            }

            getStatusClass(status) {
                return status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            }

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            formatDateTime(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            bindEvents() {
                // Profile update form
                document.getElementById('profileForm').addEventListener('submit', (e) => this.handleProfileUpdate(e));

                // Password change form
                document.getElementById('passwordForm').addEventListener('submit', (e) => this.handlePasswordChange(e));
            }

            async handleProfileUpdate(e) {
                e.preventDefault();

                const form = e.target;
                const submitBtn = document.getElementById('updateProfileBtn');
                const originalText = submitBtn.innerHTML;

                this.setLoading(submitBtn, 'Updating Profile...');

                try {
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    const response = await fetch(this.apiBase, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showNotification('Profile updated successfully!', 'success');
                        await this.loadProfileData(); // Refresh data
                    } else {
                        this.showNotification(result.error || 'Failed to update profile', 'error');
                    }
                } catch (error) {
                    console.error('Profile update error:', error);
                    this.showNotification('Network error occurred', 'error');
                } finally {
                    this.setLoading(submitBtn, originalText, false);
                }
            }

            async handlePasswordChange(e) {
                e.preventDefault();

                const form = e.target;
                const submitBtn = document.getElementById('changePasswordBtn');
                const originalText = submitBtn.innerHTML;

                this.setLoading(submitBtn, 'Changing Password...');

                try {
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    data.action = 'change_password';

                    const response = await fetch(this.apiBase, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showNotification('Password changed successfully!', 'success');
                        form.reset();
                    } else {
                        this.showNotification(result.error || 'Failed to change password', 'error');
                    }
                } catch (error) {
                    console.error('Password change error:', error);
                    this.showNotification('Network error occurred', 'error');
                } finally {
                    this.setLoading(submitBtn, originalText, false);
                }
            }

            setLoading(button, text, loading = true) {
                if (loading) {
                    button.disabled = true;
                    button.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        ${text}
                    `;
                } else {
                    button.disabled = false;
                    button.innerHTML = text;
                }
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
            new ClientProfileManager();
        });
    </script>
</body>
</html>