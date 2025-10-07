# API Documentation

## Overview
The KineticEV Admin Portal provides a comprehensive RESTful API for managing various administrative functions. This document details all available endpoints, their usage, and security implementations.

## Authentication Endpoints

### 1. Login
```http
POST /admin/login.php
Content-Type: application/json

{
    "username": "string",
    "password": "string"
}

Response:
{
    "success": boolean,
    "message": "string",
    "redirect": "string"
}
```

### 2. Logout
```http
GET /admin/logout.php

Response:
{
    "success": boolean,
    "message": "string"
}
```

## Dashboard Endpoints

### 1. Get Dashboard Stats
```http
GET /admin/api.php?action=dashboard_stats

Response:
{
    "total_users": number,
    "active_dealerships": number,
    "recent_transactions": number,
    "pending_approvals": number,
    "system_status": {
        "uptime": string,
        "memory_usage": string,
        "cpu_usage": string
    }
}
```

### 2. Get Analytics Data
```http
GET /admin/api.php?action=analytics
Query Parameters:
- start_date: string (YYYY-MM-DD)
- end_date: string (YYYY-MM-DD)
- metrics: string[] (optional)

Response:
{
    "period": {
        "start": string,
        "end": string
    },
    "metrics": {
        "sales": number,
        "users": number,
        "transactions": number,
        // ... other metrics
    }
}
```

## User Management Endpoints

### 1. List Users
```http
GET /admin/api.php?action=users

Response:
{
    "users": [
        {
            "id": number,
            "username": string,
            "email": string,
            "role": string,
            "last_login": string,
            "status": string
        }
    ],
    "total": number
}
```

### 2. Create User
```http
POST /admin/api.php?action=create_user
Content-Type: application/json

{
    "username": string,
    "email": string,
    "password": string,
    "role": string,
    "full_name": string
}

Response:
{
    "success": boolean,
    "message": string,
    "user_id": number
}
```

### 3. Update User
```http
POST /admin/api.php?action=update_user
Content-Type: application/json

{
    "user_id": number,
    "email": string,
    "role": string,
    "full_name": string,
    "is_active": boolean
}

Response:
{
    "success": boolean,
    "message": string
}
```

## Dealership Management Endpoints

### 1. List Dealerships
```http
GET /admin/api.php?action=table_data&table=dealerships
Query Parameters:
- page: number
- per_page: number
- search: string
- sort_by: string
- sort_dir: string

Response:
{
    "data": [
        {
            "id": number,
            "name": string,
            "address": string,
            "city": string,
            "state": string,
            "status": string
        }
    ],
    "total": number,
    "page": number,
    "per_page": number
}
```

### 2. Create/Update Dealership
```http
POST /admin/dealership.php
Content-Type: application/json

{
    "id": number, // Optional, for updates
    "name": string,
    "address": string,
    "city": string,
    "state": string,
    "pincode": string,
    "status": string
}

Response:
{
    "success": boolean,
    "message": string,
    "dealership_id": number
}
```

## System Management Endpoints

### 1. View Logs
```http
GET /admin/api.php?action=logs
Query Parameters:
- log_file: string
- lines: number
- level: string

Response:
{
    "logs": string[],
    "total_lines": number
}
```

### 2. Get Filter Options
```http
GET /admin/api.php?action=filter_options
Query Parameters:
- table: string

Response:
{
    "filters": {
        "field_name": {
            "type": string,
            "options": array
        }
    }
}
```

## Error Handling

### Error Response Format
```json
{
    "success": false,
    "error": {
        "code": string,
        "message": string,
        "details": object
    }
}
```

### Common Error Codes
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## Security Implementation

### 1. Authentication Required
All API endpoints (except login) require:
- Valid session cookie
- CSRF token (for POST requests)

### 2. Rate Limiting
```http
Response Headers:
X-RateLimit-Limit: number
X-RateLimit-Remaining: number
X-RateLimit-Reset: number
```

### 3. Input Validation
- All input parameters are validated
- SQL injection prevention
- XSS prevention
- Type checking

## API Versioning
Current Version: v1
Header: `X-API-Version: v1`

## Testing
All endpoints should be tested for:
1. Authentication
2. Authorization
3. Input validation
4. Error handling
5. Rate limiting
6. CSRF protection

## Usage Examples

### JavaScript Fetch Example
```javascript
async function fetchDashboardStats() {
    try {
        const response = await fetch('/admin/api.php?action=dashboard_stats', {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching dashboard stats:', error);
        throw error;
    }
}
```

### PHP Curl Example
```php
$ch = curl_init('http://your-domain.com/admin/api.php?action=dashboard_stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
```