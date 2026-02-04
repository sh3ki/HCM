<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Test - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1b68ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            🤖 HCM Chatbot - Test Page
                        </h1>
                        <p class="text-gray-600">
                            The chatbot should appear in the bottom right corner
                        </p>
                    </div>
                    <div class="text-6xl">💬</div>
                </div>

                <!-- Status Indicator -->
                <div id="status-indicator" class="mb-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Checking chatbot status...</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-blue-900 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Setup Instructions
                    </h2>
                    <ol class="list-decimal list-inside space-y-2 text-blue-800">
                        <li>Get your FREE Groq API key from <a href="https://console.groq.com" target="_blank" class="underline font-semibold">console.groq.com</a></li>
                        <li>Open <code class="bg-blue-100 px-2 py-1 rounded">config/groq.php</code></li>
                        <li>Replace <code class="bg-blue-100 px-2 py-1 rounded">gsk_YOUR_API_KEY_HERE</code> with your actual API key</li>
                        <li>Refresh this page and test the chatbot!</li>
                    </ol>
                </div>

                <!-- Test Scenarios -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
                        <h3 class="font-semibold text-purple-900 mb-2">
                            <i class="fas fa-user mr-2"></i>Employee Questions
                        </h3>
                        <ul class="text-sm text-purple-800 space-y-1">
                            <li>• How do I apply for leave?</li>
                            <li>• Where can I view my payslip?</li>
                            <li>• How do I clock in?</li>
                            <li>• Show me my benefits</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-teal-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 mb-2">
                            <i class="fas fa-user-shield mr-2"></i>Admin Questions
                        </h3>
                        <ul class="text-sm text-green-800 space-y-1">
                            <li>• How do I add a new employee?</li>
                            <li>• Where can I approve leaves?</li>
                            <li>• How do I generate reports?</li>
                            <li>• Where are the settings?</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-4xl mb-3">⚡</div>
                    <h3 class="font-semibold text-gray-900 mb-2">Super Fast</h3>
                    <p class="text-sm text-gray-600">Powered by Groq - fastest LLM inference</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="font-semibold text-gray-900 mb-2">100% Free</h3>
                    <p class="text-sm text-gray-600">No credit card, unlimited usage</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-4xl mb-3">🎯</div>
                    <h3 class="font-semibold text-gray-900 mb-2">Context-Aware</h3>
                    <p class="text-sm text-gray-600">Knows your role and current page</p>
                </div>
            </div>

            <!-- Technical Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-cog mr-2"></i>Technical Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong class="text-gray-700">Model:</strong>
                        <span class="text-gray-600">Llama 3.3 70B Versatile</span>
                    </div>
                    <div>
                        <strong class="text-gray-700">Provider:</strong>
                        <span class="text-gray-600">Groq Cloud</span>
                    </div>
                    <div>
                        <strong class="text-gray-700">Max Tokens:</strong>
                        <span class="text-gray-600">500 per response</span>
                    </div>
                    <div>
                        <strong class="text-gray-700">Temperature:</strong>
                        <span class="text-gray-600">0.7 (balanced)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Chatbot -->
    <?php include __DIR__ . '/includes/chatbot.php'; ?>

    <script>
        // Check if chatbot is loaded
        window.addEventListener('load', function() {
            setTimeout(() => {
                const chatbot = document.getElementById('hcm-chatbot');
                const statusIndicator = document.getElementById('status-indicator');
                
                if (chatbot) {
                    statusIndicator.innerHTML = `
                        <div class="bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        <strong>Chatbot loaded successfully!</strong> 
                                        Look for the blue chat icon in the bottom right corner.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    statusIndicator.innerHTML = `
                        <div class="bg-red-50 border-l-4 border-red-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-times-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Chatbot not found.</strong> 
                                        Please check if includes/chatbot.php exists.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }, 500);
        });
    </script>
</body>
</html>
