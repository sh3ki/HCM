# Quick Installation Guide - Admin Features

## Prerequisites
- MySQL/MariaDB database server
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Existing HCM system installed

## Installation Steps

### Step 1: Database Setup

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)

2. Run the admin features enhancement script:
```sql
SOURCE /path/to/HCM/database/admin_features_enhancement.sql;
```

3. (Optional) Generate sample data for testing:
```sql
SOURCE /path/to/HCM/database/sample_admin_data.sql;
```

### Step 2: Verify Files

Ensure these new files are in place:
- ✅ `views/admin.php`
- ✅ `api/admin.php`
- ✅ `database/admin_features_enhancement.sql`
- ✅ `database/sample_admin_data.sql`
- ✅ `ADMIN_FEATURES_README.md`

### Step 3: Update Existing Files

The following files have been updated:
- ✅ `views/includes/sidebar.php` - Added admin menu link
- ✅ `api/employees.php` - Added department/position endpoints

### Step 4: Set Admin User

Make sure you have at least one admin user:

```sql
-- Check current users
SELECT id, username, email, role FROM users;

-- Update a user to admin (replace USER_ID with actual ID)
UPDATE users SET role = 'admin' WHERE id = USER_ID;

-- Or create new admin user
INSERT INTO users (username, email, password_hash, role) 
VALUES ('admin', 'admin@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 'admin');
-- Default password: password123 (CHANGE THIS!)
```

### Step 5: Test Access

1. Login to HCM system with admin credentials
2. Look for "Admin Management" in the sidebar (with shield icon)
3. Click to access the admin panel
4. Test each tab:
   - Employee Search & Filter
   - Tax Records
   - Performance Analysis
   - Performance Goals
   - Salary Structures
   - Salary Comparison
   - AI Insights

## Verification Checklist

- [ ] Database tables created (8 new tables, 4 new views)
- [ ] Admin menu link appears in sidebar for admin users
- [ ] Admin page loads without errors
- [ ] All 7 tabs are accessible
- [ ] Sample data appears in tables (if generated)
- [ ] Charts render correctly
- [ ] Modals open and close properly
- [ ] API endpoints respond correctly
- [ ] Search and filter functions work

## Quick Test Commands

### Test Database Tables
```sql
-- Verify tables exist
SHOW TABLES LIKE '%salary_structures%';
SHOW TABLES LIKE '%performance_goals%';
SHOW TABLES LIKE '%tax_records%';
SHOW TABLES LIKE '%ai_%';

-- Check sample data counts
SELECT COUNT(*) FROM performance_evaluations;
SELECT COUNT(*) FROM performance_goals;
SELECT COUNT(*) FROM tax_records;
SELECT COUNT(*) FROM employee_salary_structures;
```

### Test API Endpoints
```bash
# Test employee search (replace with your URL)
curl "http://localhost/HCM/api/admin.php?action=search_employees&search=test"

# Test salary comparison
curl "http://localhost/HCM/api/admin.php?action=get_salary_comparison"
```

## Troubleshooting

### Error: "Tables don't exist"
**Solution**: Run the admin_features_enhancement.sql script

### Error: "Admin Management not in sidebar"
**Solution**: 
1. Check user role in database: `SELECT role FROM users WHERE id = YOUR_ID;`
2. Update to admin: `UPDATE users SET role = 'admin' WHERE id = YOUR_ID;`
3. Clear session and re-login

### Error: "No data showing"
**Solution**: Run sample_admin_data.sql to generate test data

### Error: "Page not found"
**Solution**: Verify admin.php exists in views folder

### Error: "API not responding"
**Solution**: 
1. Check api/admin.php exists
2. Verify file permissions (should be readable by web server)
3. Check PHP error logs for details

## Configuration

No additional configuration needed! The system uses your existing database connection and authentication.

## Security Notes

⚠️ **IMPORTANT**: Change default admin password immediately after installation!

```sql
-- Update admin password
UPDATE users SET password_hash = '$2y$10$YOUR_NEW_HASHED_PASSWORD' WHERE role = 'admin';
```

## Features Available

✅ **Employee Search & Filter** - Advanced search with multiple criteria  
✅ **Tax Records Review** - Comprehensive tax information management  
✅ **Performance Analysis** - Top performers and underperformers identification  
✅ **Performance Goals** - Set and track employee goals  
✅ **Salary Structures** - Assign employees to grade levels  
✅ **Salary Comparison** - Compare compensation across departments  
✅ **AI Insights** - AI-powered recommendations and analysis  

## Next Steps

1. Review the comprehensive documentation in ADMIN_FEATURES_README.md
2. Customize salary structures for your organization
3. Set performance goals for employees
4. Generate tax records for current period
5. Explore AI recommendations

## Support

For detailed documentation, see:
- `ADMIN_FEATURES_README.md` - Complete feature documentation
- `database/admin_features_enhancement.sql` - Database schema
- `database/sample_admin_data.sql` - Sample data script

## Success!

If you can access the Admin Management page and see all 7 tabs with data, installation is complete! 🎉

---

**Installation Time**: ~5 minutes  
**Difficulty**: Easy  
**Version**: 1.0.0
