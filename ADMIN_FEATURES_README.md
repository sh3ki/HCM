# Admin Management Features - HCM System

## Overview
The Admin Management module provides comprehensive tools for administrators to manage employees, review performance, analyze tax records, and leverage AI-powered insights for better decision-making.

## Features Implemented

### 1. Employee Search & Filter ✅
**Location:** Admin Management → Employee Search & Filter tab

**Capabilities:**
- Advanced search by name, email, or employee ID
- Filter by department, position, employment status
- Filter by salary range (₱0-25K, ₱25K-50K, ₱50K-75K, ₱75K-100K, ₱100K+)
- Filter by hire date and gender
- Real-time search results display
- View employee details directly from results

**Usage:**
1. Navigate to Admin Management page
2. Use search bar and dropdown filters
3. Click "Search" to display matching employees
4. Click "View" to see detailed employee information

---

### 2. Tax Records Review ✅
**Location:** Admin Management → Tax Records tab

**Capabilities:**
- Review tax records by year and period (monthly/quarterly/annual)
- View summary of total tax withheld, taxable income, and contributions
- Display SSS, PhilHealth, and Pag-IBIG contributions
- Detailed tax records table with all deductions
- Export-ready format for BIR compliance

**Key Metrics Displayed:**
- Total Tax Withheld
- Total Taxable Income
- SSS Contributions
- PhilHealth + Pag-IBIG Contributions

**Usage:**
1. Select tax year from dropdown
2. Select period (optional)
3. Click "Load" to fetch records
4. Review summary cards and detailed table
5. Click "Details" to view individual tax records

---

### 3. Performance Analysis ✅
**Location:** Admin Management → Performance Analysis tab

**Capabilities:**
- Identify top performers (rating ≥ 4.0)
- Identify underperformers (rating < 3.0)
- Department performance overview with charts
- Detailed performance table with all employees
- Goals achievement tracking
- Evaluation count and status indicators

**Features:**
- **Top Performers Panel**: Lists employees with excellent ratings
- **Needs Attention Panel**: Lists employees requiring support
- **Department Chart**: Visual representation of department performance
- **Detailed Table**: Comprehensive view of all performance data

**Usage:**
1. View automatically loaded performance data
2. Review top performers and underperformers
3. Analyze department performance chart
4. Sort and filter detailed performance table
5. Click "View" to see individual performance details

---

### 4. Performance Goals Management ✅
**Location:** Admin Management → Performance Goals tab

**Capabilities:**
- Set new performance goals for employees
- Track goal progress with percentage indicators
- Filter goals by employee, status, and priority
- View goals by category (productivity, quality, innovation, etc.)
- Edit and delete goals
- Monitor completion rates

**Goal Categories:**
- Productivity
- Quality
- Innovation
- Leadership
- Collaboration
- Other

**Priority Levels:**
- Low
- Medium
- High
- Critical

**Status Types:**
- Not Started
- In Progress
- Completed
- On Hold
- Cancelled

**Usage:**
1. Click "Set New Goal" button
2. Fill in goal details:
   - Select employee
   - Enter goal title and description
   - Choose type, category, and priority
   - Set target value
   - Define start and target dates
3. Click "Set Goal" to save
4. Filter goals using dropdowns
5. Edit or delete goals as needed

---

### 5. Salary Structure Assignment ✅
**Location:** Admin Management → Salary Structures tab

**Capabilities:**
- View all available salary structures (7 grade levels)
- Assign employees to specific salary structures
- Track assigned vs unassigned employees
- View salary ranges for each structure
- Monitor employee compliance with structure minimums
- Reassign structures as needed

**Salary Structures:**
- **Grade 1 (Entry Level)**: ₱15,000 - ₱25,000
- **Grade 2 (Junior Level)**: ₱25,000 - ₱40,000
- **Grade 3 (Mid Level)**: ₱40,000 - ₱65,000
- **Grade 4 (Senior Level)**: ₱65,000 - ₱95,000
- **Grade 5 (Lead Level)**: ₱95,000 - ₱130,000
- **Grade 6 (Management)**: ₱130,000 - ₱180,000
- **Grade 7 (Senior Management)**: ₱180,000 - ₱300,000

**Usage:**
1. View available structures and current assignments
2. Click "Assign Structure" button
3. Select employee and salary structure
4. Set effective date
5. Add notes (optional)
6. Click "Assign" to save
7. Use "Reassign" to update employee structure

---

### 6. Salary Comparison Across Departments ✅
**Location:** Admin Management → Salary Comparison tab

**Capabilities:**
- Compare salaries across all departments
- View average, minimum, and maximum salaries
- Calculate total salary cost per department
- Analyze annual compensation costs
- Interactive chart with multiple views (avg/min/max/total)
- Detailed comparison table

**Metrics Displayed:**
- Employee count per department
- Average salary
- Minimum salary
- Maximum salary
- Total salary cost
- Average annual cost

**Usage:**
1. View automatically loaded comparison data
2. Click chart buttons to switch views:
   - Average
   - Minimum
   - Maximum
   - Total
3. Review detailed comparison table
4. Use for budget planning and equity analysis

---

### 7. AI Integration ✅
**Location:** Admin Management → AI Insights tab

**Capabilities:**
- **AI Query Assistant**: Ask questions about workforce data
- **Salary Recommendations**: AI-powered salary adjustment suggestions
- **Performance Recommendations**: Identify training and development needs
- **Retention Risk Analysis**: Predict and prevent employee turnover
- **Training Needs Assessment**: Recommend skill development programs
- **Interaction History**: Track all AI queries and responses

**AI Features:**

#### Ask AI Assistant
- Natural language queries
- Context-aware responses
- Confidence scoring
- Execution time tracking
- Feedback system (helpful/not helpful)

**Example Queries:**
- "What is the average salary in our organization?"
- "Show me top performers"
- "What is our turnover rate?"
- "Which department has the highest performance?"
- "Analyze retention risks"

#### AI Recommendations
Four types of recommendations:

1. **Salary Recommendations**
   - Identifies employees below structure minimum
   - Suggests market-competitive adjustments
   - Highlights equity concerns

2. **Performance Recommendations**
   - Identifies employees needing reviews
   - Suggests performance improvement plans
   - Recommends recognition programs

3. **Retention Risks**
   - Identifies high-performing, underpaid employees
   - Predicts flight risk factors
   - Suggests retention strategies

4. **Training Needs**
   - Identifies skill gaps
   - Recommends training programs
   - Suggests mentorship opportunities

**Usage:**
1. Type question in AI Query textbox
2. Click "Ask AI" to get response
3. Rate response (helpful/not helpful)
4. Generate specific recommendations by clicking "Generate" buttons
5. Review and act on AI recommendations
6. View interaction history for audit trail

---

## Database Schema

### New Tables Created

1. **salary_structures**
   - Defines salary grade levels and ranges
   - 7 default structures (G1-G7)

2. **employee_salary_structures**
   - Links employees to salary structures
   - Tracks assignment history

3. **performance_goals**
   - Stores employee performance goals
   - Tracks progress and completion

4. **performance_evaluations**
   - Enhanced evaluation records
   - Multiple rating dimensions

5. **tax_records**
   - Comprehensive tax information
   - BIR compliance ready

6. **ai_interaction_logs**
   - Records all AI queries
   - Tracks user feedback

7. **ai_recommendations**
   - Stores AI-generated insights
   - Tracks recommendation status

### Views Created

1. **employee_performance_summary**
   - Aggregated performance metrics
   - Goal completion rates

2. **department_salary_comparison**
   - Department-level salary analytics
   - Cost analysis

3. **top_performers**
   - Employees with rating ≥ 4.0
   - Sorted by performance

4. **underperformers**
   - Employees with rating < 3.0
   - Needs attention list

---

## Installation & Setup

### Step 1: Run Database Scripts
```sql
-- Run these scripts in order:
1. database/admin_features_enhancement.sql
2. database/sample_admin_data.sql (optional, for testing)
```

### Step 2: Verify Files
Ensure these files exist:
- `views/admin.php` - Main admin page
- `api/admin.php` - API endpoints
- `database/admin_features_enhancement.sql` - Table definitions
- `database/sample_admin_data.sql` - Sample data

### Step 3: Access Admin Panel
1. Login as admin user
2. Navigate to Admin Management from sidebar
3. All features should be accessible

---

## API Endpoints

### GET Endpoints

```
GET /api/admin.php?action=search_employees&search=john&department=IT
GET /api/admin.php?action=get_tax_records&year=2026&period=monthly
GET /api/admin.php?action=get_performance_data
GET /api/admin.php?action=get_goals&employee_id=5&status=in_progress
GET /api/admin.php?action=get_salary_structures
GET /api/admin.php?action=get_salary_comparison
GET /api/admin.php?action=get_ai_history
```

### POST Endpoints

```
POST /api/admin.php
{
  "action": "set_goal",
  "employee_id": 5,
  "goal_title": "Improve Sales",
  "priority": "high",
  ...
}

POST /api/admin.php
{
  "action": "assign_salary_structure",
  "employee_id": 5,
  "salary_structure_id": 3,
  ...
}

POST /api/admin.php
{
  "action": "ai_query",
  "query": "What is the average salary?"
}

POST /api/admin.php
{
  "action": "generate_ai_recommendations",
  "type": "salary|performance|retention|training"
}
```

---

## User Interface

### Styling
- Consistent with existing HCM design
- Uses Tailwind CSS framework
- Responsive layout for all screen sizes
- Professional color scheme
- Intuitive tab navigation

### Components
- Tab-based navigation for feature organization
- Interactive charts using Chart.js
- Modal dialogs for data entry
- Real-time search and filtering
- Summary cards for key metrics
- Detailed data tables with sorting

---

## Security

### Access Control
- Admin role required for all features
- Session-based authentication
- Role verification on each request
- API endpoint protection

### Data Protection
- SQL injection prevention using prepared statements
- Input validation and sanitization
- XSS protection with htmlspecialchars()
- CSRF token support ready

---

## Performance Optimization

### Database
- Indexed key columns for fast queries
- Views for complex aggregations
- Efficient JOIN operations
- Query result caching ready

### Frontend
- Lazy loading of tab content
- Asynchronous API calls
- Minimal page reloads
- Optimized chart rendering

---

## Future Enhancements

### Planned Features
1. Export to Excel/PDF for all reports
2. Advanced AI natural language processing
3. Predictive analytics dashboard
4. Automated email notifications
5. Bulk operations for goals and structures
6. Mobile-optimized views
7. Real-time collaboration features
8. Integration with external HR systems

### AI Improvements
1. Machine learning model training
2. Sentiment analysis
3. Predictive turnover modeling
4. Automated goal suggestions
5. Smart compensation benchmarking

---

## Troubleshooting

### Common Issues

**Issue**: Admin page not accessible
- **Solution**: Verify user role is 'admin' in database
- **Check**: Session variables ($_SESSION['role'])

**Issue**: No data showing in tables
- **Solution**: Run sample_admin_data.sql script
- **Check**: Database tables exist and have data

**Issue**: AI queries not working
- **Solution**: Check ai_interaction_logs table exists
- **Verify**: API endpoint is accessible

**Issue**: Charts not rendering
- **Solution**: Ensure Chart.js is loaded
- **Check**: Browser console for JavaScript errors

---

## Support & Maintenance

### Regular Tasks
1. Review AI recommendations weekly
2. Update salary structures annually
3. Archive old tax records yearly
4. Backup database regularly
5. Monitor API performance

### Data Cleanup
```sql
-- Archive old AI logs (older than 6 months)
DELETE FROM ai_interaction_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- Archive completed goals (older than 1 year)
UPDATE performance_goals SET status = 'cancelled' 
WHERE status = 'completed' AND completion_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

## Credits
- **Developer**: AI Assistant
- **Framework**: Tailwind CSS, Chart.js
- **Database**: MySQL 5.7+
- **PHP Version**: 7.4+

---

## Version History

### v1.0.0 (2026-02-04)
- Initial release
- All 7 core features implemented
- Sample data generation
- Complete API documentation
- Comprehensive UI/UX

---

## Contact & Feedback
For issues, suggestions, or feature requests, please contact your system administrator.

---

**Note**: This admin management system is designed to be scalable, secure, and user-friendly. All features maintain consistency with the existing HCM system design and functionality.
