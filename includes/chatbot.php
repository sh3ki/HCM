<!-- HCM System Chatbot -->
<div id="hcm-chatbot" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Toggle Button -->
    <button id="chatbot-toggle" class="bg-primary hover:bg-blue-700 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg transition-all duration-300 hover:scale-110">
        <i class="fas fa-comments text-2xl"></i>
    </button>

    <!-- Chat Window -->
    <div id="chatbot-window" class="hidden absolute bottom-20 right-0 w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col" style="height: 500px;">
        <!-- Header -->
        <div class="bg-primary text-white p-4 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                    <i class="fas fa-robot text-primary text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold">HCM Assistant</h3>
                    <p class="text-xs opacity-90">Here to help you navigate</p>
                </div>
            </div>
            <button id="chatbot-close" class="hover:bg-blue-600 rounded-full p-2 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Chat Messages -->
        <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <div class="bot-message flex items-start space-x-2">
                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-2 shadow-sm max-w-xs">
                    <p class="text-sm text-gray-800">Hi! I'm your HCM Assistant. I can help you navigate the system and answer questions about your HR tasks. How can I help you today?</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div id="chatbot-quick-actions" class="px-4 py-2 border-t border-gray-200 bg-white">
            <p class="text-xs text-gray-500 mb-2">Quick actions:</p>
            <div class="flex flex-wrap gap-2">
                <button class="quick-action-btn text-xs bg-blue-50 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-100 transition-colors" data-message="How do I apply for leave?">
                    <i class="fas fa-calendar-alt mr-1"></i> Apply Leave
                </button>
                <button class="quick-action-btn text-xs bg-green-50 text-green-700 px-3 py-1 rounded-full hover:bg-green-100 transition-colors" data-message="Show me my attendance">
                    <i class="fas fa-clock mr-1"></i> Attendance
                </button>
                <button class="quick-action-btn text-xs bg-purple-50 text-purple-700 px-3 py-1 rounded-full hover:bg-purple-100 transition-colors" data-message="Where can I view my payslip?">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Payslip
                </button>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 border-t border-gray-200 bg-white rounded-b-2xl">
            <div class="flex items-center space-x-2">
                <input 
                    type="text" 
                    id="chatbot-input" 
                    placeholder="Type your question..." 
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                    autocomplete="off"
                >
                <button id="chatbot-send" class="bg-primary hover:bg-blue-700 text-white rounded-full w-10 h-10 flex items-center justify-center transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div id="chatbot-typing" class="hidden mt-2 text-xs text-gray-500 italic">
                <i class="fas fa-circle-notch fa-spin mr-1"></i> Assistant is typing...
            </div>
        </div>
    </div>
</div>

<style>
#chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

#chatbot-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#chatbot-messages::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

#chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.user-message {
    display: flex;
    justify-content: flex-end;
}

.user-message .message-bubble {
    background: linear-gradient(135deg, #1b68ff 0%, #0d4fd1 100%);
    color: white;
    border-radius: 1rem;
    border-top-right-radius: 0.25rem;
    padding: 0.5rem 1rem;
    max-width: 75%;
    box-shadow: 0 2px 4px rgba(27, 104, 255, 0.2);
}

.bot-message {
    display: flex;
    align-items-start;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-enter {
    animation: slideUp 0.3s ease-out;
}
</style>

<script>
class HCMChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }

    init() {
        // Toggle chatbot
        document.getElementById('chatbot-toggle').addEventListener('click', () => {
            this.toggleChat();
        });

        // Close chatbot
        document.getElementById('chatbot-close').addEventListener('click', () => {
            this.closeChat();
        });

        // Send message on button click
        document.getElementById('chatbot-send').addEventListener('click', () => {
            this.sendMessage();
        });

        // Send message on Enter key
        document.getElementById('chatbot-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const message = e.currentTarget.getAttribute('data-message');
                document.getElementById('chatbot-input').value = message;
                this.sendMessage();
            });
        });
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbot-window');
        const toggle = document.getElementById('chatbot-toggle');

        if (this.isOpen) {
            window.classList.remove('hidden');
            toggle.innerHTML = '<i class="fas fa-times text-2xl"></i>';
            document.getElementById('chatbot-input').focus();
        } else {
            window.classList.add('hidden');
            toggle.innerHTML = '<i class="fas fa-comments text-2xl"></i>';
        }
    }

    closeChat() {
        this.isOpen = false;
        document.getElementById('chatbot-window').classList.add('hidden');
        document.getElementById('chatbot-toggle').innerHTML = '<i class="fas fa-comments text-2xl"></i>';
    }

    async sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (!message) return;

        // Add user message to chat
        this.addMessage('user', message);
        input.value = '';

        // Show typing indicator
        this.showTyping(true);

        try {
            // Send to API
            const response = await fetch('/HCM/api/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    context: this.getPageContext()
                })
            });

            const data = await response.json();

            // Hide typing indicator
            this.showTyping(false);

            if (data.success) {
                // Add bot response
                this.addMessage('bot', data.response);
            } else {
                this.addMessage('bot', 'Sorry, I encountered an error. Please try again.');
            }
        } catch (error) {
            this.showTyping(false);
            console.error('Chatbot error:', error);
            this.addMessage('bot', 'Sorry, I\'m having trouble connecting. Please try again later.');
        }
    }

    addMessage(type, text) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `${type}-message message-enter`;

        if (type === 'user') {
            messageDiv.innerHTML = `
                <div class="message-bubble">
                    <p class="text-sm">${this.escapeHtml(text)}</p>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-2 shadow-sm max-w-xs">
                    <p class="text-sm text-gray-800">${this.formatResponse(text)}</p>
                </div>
            `;
        }

        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    showTyping(show) {
        const typing = document.getElementById('chatbot-typing');
        if (show) {
            typing.classList.remove('hidden');
        } else {
            typing.classList.add('hidden');
        }
    }

    getPageContext() {
        const path = window.location.pathname;
        const pageName = path.split('/').pop().replace('.php', '');
        
        return {
            page: pageName,
            path: path,
            userRole: this.getUserRole()
        };
    }

    getUserRole() {
        // Try to detect user role from page structure
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.textContent.includes('Admin Dashboard')) {
            return 'admin';
        }
        return 'employee';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatResponse(text) {
        // Convert markdown-style links to HTML
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-primary hover:underline">$1</a>');
        
        // Convert line breaks
        text = text.replace(/\n/g, '<br>');
        
        // Bold text
        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        return text;
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.hcmChatbot = new HCMChatbot();
});
</script>
