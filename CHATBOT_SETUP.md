# HCM System Chatbot Setup Guide

## Overview
The HCM System now includes an AI-powered chatbot assistant that helps users navigate the system and answer questions about HR tasks. The chatbot uses **Groq API** with **Llama 3.3 70B** model - completely **FREE and UNLIMITED**!

## Features
✅ **Context-Aware**: Knows which page you're on and your role (admin/employee)
✅ **Navigation Help**: Guides users to specific features and pages
✅ **Quick Actions**: Pre-built buttons for common tasks
✅ **Real-time Responses**: Fast AI-powered answers using Groq
✅ **Modern UI**: Beautiful floating chat widget in bottom right corner
✅ **Responsive**: Works on desktop and mobile devices

## Setup Instructions

### Step 1: Get Your FREE Groq API Key

1. Go to [https://console.groq.com](https://console.groq.com)
2. Sign up for a FREE account (no credit card required)
3. Navigate to **API Keys** section
4. Click **Create API Key**
5. Copy your API key (starts with `gsk_`)

### Step 2: Configure the Chatbot

1. Open `config/groq.php`
2. Replace `gsk_YOUR_API_KEY_HERE` with your actual Groq API key:

```php
define('GROQ_API_KEY', 'gsk_your_actual_api_key_here');
```

3. Save the file

### Step 3: Test the Chatbot

1. Log in to your HCM system
2. You'll see a blue chat icon in the **bottom right corner**
3. Click it to open the chatbot
4. Try asking:
   - "How do I apply for leave?"
   - "Show me my attendance"
   - "Where can I view my payslip?"
   - "How do I clock in?"

## Usage

### Quick Actions
The chatbot provides quick action buttons for:
- 📅 **Apply Leave** - Direct guidance on leave application
- ⏰ **Attendance** - Help with clock in/out and attendance tracking
- 💰 **Payslip** - Navigate to payslip viewing

### Conversation Examples

**Employee Questions:**
- "How do I request time off?"
- "Where can I see my benefits?"
- "How do I update my profile?"
- "What are my leave balances?"

**Admin Questions:**
- "How do I add a new employee?"
- "Where can I approve leave requests?"
- "How do I generate payroll reports?"
- "Where are the system settings?"

## Technical Details

### Files Added/Modified

**New Files:**
- `includes/chatbot.php` - Chatbot UI component
- `api/chatbot.php` - Backend API handler
- `config/groq.php` - Groq API configuration

**Modified Files:**
- `views/includes/scripts.php` - Added chatbot inclusion

### API Configuration

**Model:** Llama 3.3 70B Versatile
**Provider:** Groq Cloud
**Cost:** FREE (no limits on API calls)
**Speed:** Ultra-fast inference (fastest in the market)
**Max Tokens:** 500 per response
**Temperature:** 0.7 (balanced creativity/accuracy)

### System Prompt Features

The chatbot is pre-configured with knowledge about:
- All HCM system pages and features
- Navigation paths for each section
- Common tasks and workflows
- Role-based access (admin vs employee)
- Current page context

## Customization

### Change Chatbot Appearance

Edit `includes/chatbot.php`:

```css
/* Change primary color */
.bg-primary { background-color: #1b68ff; }

/* Change chat window size */
#chatbot-window { width: 400px; height: 600px; }
```

### Modify System Prompt

Edit `api/chatbot.php`, function `buildSystemPrompt()`:

```php
$basePrompt = "Your custom instructions here...";
```

### Add More Quick Actions

Edit `includes/chatbot.php`:

```html
<button class="quick-action-btn" data-message="Your question here">
    <i class="fas fa-icon"></i> Label
</button>
```

## Troubleshooting

### Chatbot says "unable to connect"
- **Solution**: Make sure you've added your Groq API key in `config/groq.php`

### No response from chatbot
- Check browser console for errors (F12)
- Verify `api/chatbot.php` is accessible
- Ensure `curl` is enabled in PHP

### API key invalid
- Generate a new key from Groq console
- Make sure you copied the entire key
- No spaces before/after the key

### Chatbot not appearing
- Clear browser cache (Ctrl+Shift+Delete)
- Check if `includes/chatbot.php` exists
- Verify `scripts.php` includes the chatbot

## Why Groq?

✅ **Completely FREE** - No credit card, no hidden costs
✅ **Unlimited Usage** - No rate limits on free tier
✅ **Super Fast** - Fastest LLM inference in the world
✅ **High Quality** - Using Meta's Llama 3.3 70B model
✅ **No Configuration Hassle** - Just one API key needed

## Alternative Models

You can change the model in `config/groq.php`:

```php
// Faster, smaller model
define('GROQ_MODEL', 'llama-3.1-8b-instant');

// Largest, most capable
define('GROQ_MODEL', 'llama-3.3-70b-versatile');

// Mixtral alternative
define('GROQ_MODEL', 'mixtral-8x7b-32768');
```

## Support

For issues or questions:
1. Check this README first
2. Review the Groq documentation: https://console.groq.com/docs
3. Check browser console for errors
4. Verify API key is correct

## Features Roadmap

Future enhancements:
- [ ] Conversation history persistence
- [ ] File upload support (policy documents)
- [ ] Voice input/output
- [ ] Multi-language support
- [ ] Analytics dashboard
- [ ] Custom training on company policies

## Security Notes

⚠️ **Important:**
- API key is server-side only (not exposed to browser)
- User conversations are not stored by default
- Groq respects data privacy
- No sensitive data should be entered in chat

## Conclusion

You now have a fully functional AI chatbot assistant in your HCM system! Users can get instant help navigating the system, finding features, and understanding HR processes.

**Cost: $0 / Forever Free with Groq**

Enjoy your new AI-powered HR assistant! 🤖✨
