# HCM System API - Postman Testing Guide

## Overview
This guide will help you test the HCM System API using Postman. The API provides endpoints for authentication, employee management, and other HR operations.

## Setup Instructions

### 1. Import the Collection
1. Open Postman
2. Click "Import" button
3. Select the `HCM_API_Collection.json` file
4. The collection will be imported with all endpoints and examples

### 2. Environment Variables
Create a new environment in Postman with these variables:
- `base_url`: `http://localhost/HCM/api`
- `access_token`: (will be set automatically after login)
- `refresh_token`: (will be set automatically after login)

### 3. Database Setup
Make sure you have:
1. Imported the database schema (`database/hcm_system.sql`)
2. Added sample data (`database/sample_data.sql`)
3. XAMPP running with Apache and MySQL

## Testing Workflow

### Step 1: Test API Info
- **Endpoint**: `GET /api/`
- **Purpose**: Verify API is working
- **Expected**: 200 status with API information

### Step 2: Authentication

#### Login
- **Endpoint**: `POST /api/auth/login`
- **Body**:
```json
{
  "username": "admin",
  "password": "admin123",
  "remember_me": true
}
```
- **Expected**: 200 status with access_token and refresh_token
- **Note**: Tokens are automatically saved to environment variables

#### Get User Profile
- **Endpoint**: `GET /api/auth/me`
- **Headers**: `Authorization: Bearer {{access_token}}`
- **Expected**: 200 status with user profile data

#### Validate Token
- **Endpoint**: `GET /api/auth/validate`
- **Headers**: `Authorization: Bearer {{access_token}}`
- **Expected**: 200 status confirming token is valid

### Step 3: Employee Management

#### Get All Employees
- **Endpoint**: `GET /api/employees`
- **Headers**: `Authorization: Bearer {{access_token}}`
- **Query Parameters**:
  - `page`: Page number (default: 1)
  - `limit`: Records per page (default: 20)
  - `department_id`: Filter by department
  - `status`: Filter by employment status
  - `search`: Search by name, email, or employee number

#### Get Specific Employee
- **Endpoint**: `GET /api/employees/{id}`
- **Headers**: `Authorization: Bearer {{access_token}}`
- **Expected**: 200 status with detailed employee information

#### Create New Employee
- **Endpoint**: `POST /api/employees`
- **Headers**:
  - `Authorization: Bearer {{access_token}}`
  - `Content-Type: application/json`
- **Body**: See example in collection

#### Update Employee
- **Endpoint**: `PUT /api/employees/{id}`
- **Headers**:
  - `Authorization: Bearer {{access_token}}`
  - `Content-Type: application/json`
- **Body**: Fields to update

## Default Test Credentials

### Admin User
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: `admin`
- **Permissions**: Full access to all endpoints

### Sample Employees
From the sample data, you can also test with:
- **Username**: `maria.santos` (HR Manager)
- **Username**: `robert.garcia` (IT Manager)
- **Password**: `admin123` (same for all sample users)

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "timestamp": "2023-09-15T10:30:00+00:00",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "timestamp": "2023-09-15T10:30:00+00:00",
  "errors": {
    // Validation errors (if any)
  }
}
```

## Common HTTP Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request (validation error)
- `401`: Unauthorized (invalid/missing token)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `422`: Validation Error
- `429`: Rate Limit Exceeded
- `500`: Server Error

## Security Features

### JWT Authentication
- Access tokens expire in 24 hours
- Refresh tokens expire in 7 days
- Tokens include user role and permissions

### Rate Limiting
- 100 requests per hour per IP address
- Configurable in `config/auth.php`

### CORS Support
- Configurable allowed origins
- Supports preflight requests

### Security Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Content-Security-Policy: default-src 'self'

## Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check XAMPP MySQL is running
   - Verify database credentials in `config/database.php`

2. **"Token has expired"**
   - Use the refresh token endpoint to get a new access token
   - Or login again

3. **"Access denied"**
   - Check if user has proper permissions for the endpoint
   - Verify token is included in Authorization header

4. **"Rate limit exceeded"**
   - Wait for the rate limit window to reset
   - Or adjust limits in `config/auth.php`

### Debug Mode
To enable debug mode, add this to your PHP error reporting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Benefits API Testing

### New Collection: Benefits API
Import the `Benefits_API_Collection.json` file to test the complete benefits management system:

#### Available Endpoints:
1. **Benefits Overview** - `GET /benefits.php`
   - Returns statistics, recent enrollments, and active plans
2. **Benefit Plans** - `GET/POST/PUT/DELETE /benefits.php/plans`
   - Manage insurance plans and coverage details
3. **Insurance Providers** - `GET/POST /benefits.php/providers`
   - Manage insurance provider information
4. **Employee Enrollments** - `GET/POST /benefits.php/enrollments`
   - Handle employee benefit enrollments

#### Testing Workflow:
1. **Authentication** - Login to get access token
2. **Get Overview** - View benefits statistics and recent activity
3. **List Plans** - View all available benefit plans
4. **Create/Update Plans** - Test plan management
5. **Manage Enrollments** - Test employee enrollment process
6. **Error Handling** - Test unauthorized access and invalid data

#### Sample Test Data:
- Insurance Providers: Maxicare, Medicard, PhilamLife
- Sample Plans: Maxicare Prime, Medicard Gold, PhilamLife Group Term
- Test Enrollments: Multiple employees with different plans

## Next Steps

After testing the authentication, employee, and benefits endpoints, you can:

1. Implement additional endpoints (departments, payroll, attendance)
2. Add more complex filtering and sorting
3. Implement file upload for employee photos
4. Add email notifications
5. Create reporting endpoints
6. Add benefits dependency management for family plans

## Support

For issues or questions:
1. Check the API logs in your server error logs
2. Verify database connections and data
3. Test endpoints individually to isolate issues