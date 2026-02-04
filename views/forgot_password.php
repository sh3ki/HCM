<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HCM System</title>
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
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-primary rounded-full flex items-center justify-center">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Reset Password
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Enter your email to receive a verification code
                </p>
            </div>

            <!-- Step 1: Email Input -->
            <div id="step-email" class="mt-8 space-y-6">
                <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
                <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"></div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Enter your email address"
                        >
                    </div>
                </div>

                <div>
                    <button
                        id="send-otp-btn"
                        type="button"
                        onclick="sendOtp()"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-paper-plane text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        Send Verification Code
                    </button>
                </div>

                <div class="text-center">
                    <a href="login.php" class="text-sm font-medium text-primary hover:text-blue-500">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </div>

            <!-- Step 2: OTP Verification -->
            <div id="step-otp" class="mt-8 space-y-6 hidden">
                <div id="otp-error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
                
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                    <p class="text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        A 6-digit code has been sent to your email
                    </p>
                </div>

                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">
                        Verification Code
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-shield-alt text-gray-400"></i>
                        </div>
                        <input
                            id="otp"
                            name="otp"
                            type="text"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            required
                            class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm text-center text-2xl tracking-widest font-bold"
                            placeholder="000000"
                        >
                    </div>
                </div>

                <div>
                    <button
                        id="verify-otp-btn"
                        type="button"
                        onclick="verifyOtp()"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-check text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        Verify Code
                    </button>
                </div>

                <div class="text-center">
                    <button
                        id="resend-btn"
                        type="button"
                        onclick="resendOtp()"
                        class="text-sm font-medium text-primary hover:text-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-redo mr-1"></i> Resend Code
                    </button>
                    <span id="resend-timer" class="text-sm text-gray-500 ml-2"></span>
                </div>
            </div>

            <!-- Step 3: New Password -->
            <div id="step-password" class="mt-8 space-y-6 hidden">
                <div id="password-error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
                
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <p class="text-sm">
                        <i class="fas fa-check-circle mr-2"></i>
                        Code verified! Enter your new password
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        New Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Enter new password"
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Must be at least 6 characters
                    </p>
                </div>

                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input
                            id="confirm-password"
                            name="confirm-password"
                            type="password"
                            required
                            class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Confirm new password"
                        >
                    </div>
                </div>

                <div>
                    <button
                        id="reset-password-btn"
                        type="button"
                        onclick="resetPassword()"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-success hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-check text-green-300 group-hover:text-green-200"></i>
                        </span>
                        Reset Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100"></div>
        <svg class="absolute inset-0 h-full w-full stroke-blue-200/50" fill="none" viewBox="0 0 200 200">
            <defs>
                <pattern id="pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M.5 40V.5H40" fill="none" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#pattern)" />
        </svg>
    </div>

    <script>
        let resendTimeout;
        let resendSeconds = 60;

        function showError(step, message) {
            const errorDiv = document.getElementById(`${step}-error-message`);
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
                setTimeout(() => errorDiv.classList.add('hidden'), 5000);
            }
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('success-message');
            if (successDiv) {
                successDiv.textContent = message;
                successDiv.classList.remove('hidden');
                setTimeout(() => successDiv.classList.add('hidden'), 5000);
            }
        }

        function disableButton(buttonId, isDisabled) {
            const btn = document.getElementById(buttonId);
            if (btn) {
                btn.disabled = isDisabled;
                if (isDisabled) {
                    btn.classList.add('opacity-60', 'cursor-not-allowed');
                } else {
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            }
        }

        function startResendTimer() {
            resendSeconds = 60;
            disableButton('resend-btn', true);
            const timerSpan = document.getElementById('resend-timer');
            
            resendTimeout = setInterval(() => {
                resendSeconds--;
                if (timerSpan) {
                    timerSpan.textContent = `(${resendSeconds}s)`;
                }
                
                if (resendSeconds <= 0) {
                    clearInterval(resendTimeout);
                    disableButton('resend-btn', false);
                    if (timerSpan) timerSpan.textContent = '';
                }
            }, 1000);
        }

        async function sendOtp() {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                showError('error', 'Please enter your email address');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('error', 'Please enter a valid email address');
                return;
            }

            disableButton('send-otp-btn', true);

            try {
                const response = await fetch('/HCM/api/forgot_password.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('step-email').classList.add('hidden');
                    document.getElementById('step-otp').classList.remove('hidden');
                    startResendTimer();
                } else {
                    showError('error', data.error || 'Failed to send code');
                    disableButton('send-otp-btn', false);
                }
            } catch (error) {
                console.error('Send OTP error:', error);
                showError('error', 'Failed to send code. Please try again.');
                disableButton('send-otp-btn', false);
            }
        }

        async function resendOtp() {
            const email = document.getElementById('email').value.trim();
            
            disableButton('resend-btn', true);

            try {
                const response = await fetch('/HCM/api/forgot_password.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('Code resent to your email');
                    startResendTimer();
                } else {
                    showError('otp', data.error || 'Failed to resend code');
                    disableButton('resend-btn', false);
                }
            } catch (error) {
                console.error('Resend OTP error:', error);
                showError('otp', 'Failed to resend code. Please try again.');
                disableButton('resend-btn', false);
            }
        }

        async function verifyOtp() {
            const otp = document.getElementById('otp').value.trim();
            
            if (!otp || otp.length !== 6) {
                showError('otp', 'Please enter a valid 6-digit code');
                return;
            }

            disableButton('verify-otp-btn', true);

            try {
                const response = await fetch('/HCM/api/forgot_password.php?action=verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ otp })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('step-otp').classList.add('hidden');
                    document.getElementById('step-password').classList.remove('hidden');
                } else {
                    showError('otp', data.error || 'Invalid code');
                    disableButton('verify-otp-btn', false);
                }
            } catch (error) {
                console.error('Verify OTP error:', error);
                showError('otp', 'Verification failed. Please try again.');
                disableButton('verify-otp-btn', false);
            }
        }

        async function resetPassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (!password || password.length < 6) {
                showError('password', 'Password must be at least 6 characters');
                return;
            }

            if (password !== confirmPassword) {
                showError('password', 'Passwords do not match');
                return;
            }

            disableButton('reset-password-btn', true);

            try {
                const response = await fetch('/HCM/api/forgot_password.php?action=reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password, confirm_password: confirmPassword })
                });

                const data = await response.json();

                if (data.success) {
                    // Show success and redirect
                    const successDiv = document.getElementById('password-error-message');
                    successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded';
                    successDiv.textContent = 'Password reset successful! Redirecting to login...';
                    successDiv.classList.remove('hidden');
                    
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showError('password', data.error || 'Failed to reset password');
                    disableButton('reset-password-btn', false);
                }
            } catch (error) {
                console.error('Reset password error:', error);
                showError('password', 'Failed to reset password. Please try again.');
                disableButton('reset-password-btn', false);
            }
        }

        // Allow Enter key to submit
        document.getElementById('email').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendOtp();
        });

        document.getElementById('otp').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') verifyOtp();
        });

        document.getElementById('confirm-password').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') resetPassword();
        });

        // Auto-format OTP input
        document.getElementById('otp').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
