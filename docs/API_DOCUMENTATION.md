# API Documentation

## Overview
This document provides comprehensive documentation for the Kinetic EV Platform's API endpoints.

## Available API Endpoints

1. Test Drive Management
   - `submit-test-drive.php`: Process test drive requests
   - `check-status.php`: Check test drive request status
   
2. Location Services
   - `distance-check.php`: Calculate distances between locations
   - `get-allowed-cities.php`: Get list of serviceable cities

3. OTP Verification
   - `generate-otp.php`: Generate OTP for mobile verification
   - `verify-otp.php`: Verify submitted OTP

4. Contact & Payment
   - `save-contact.php`: Save contact form submissions
   - `process-payment.php`: Handle payment processing

## API Routing
All API endpoints are automatically routed through Apache's mod_rewrite rules in `.htaccess`:
```apache
RewriteRule ^api/(.+)$ api/$1.php [L]
```

## Security Implementation
All API endpoints implement:
- Security headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection)
- Input validation
- Error handling
- Logging

## Authentication
The public website API endpoints do not require authentication as they serve public-facing forms and features. The admin portal uses session-based authentication with proper security measures.
## API Endpoints

### Form Submissions

#### Submit Contact Form
```http
POST /api/submit-contact
Content-Type: application/x-www-form-urlencoded

full_name=John Doe
&email=john@example.com
&phone=1234567890
&help_type=General
&message=Test message
```

#### Submit Test Drive Request
```http
POST /api/submit-test-drive
Content-Type: application/x-www-form-urlencoded

name=John Doe
&email=john@example.com
&phone=1234567890
&preferred_date=2025-10-10
&preferred_time=10:00
&location=Mumbai
```

### OTP Verification

#### Verify OTP
```http
POST /api/verify-otp
Content-Type: application/json

{
    "phone": "1234567890",
    "otp": "123456"
}
```

### Error Handling
All API endpoints include proper error handling:
- Input validation
- Error response formatting
- Logging of errors
- Security headers

### Admin Portal API Endpoints

Note: All admin endpoints require session-based authentication.

#### Dashboard Analytics
```http
GET /api/admin/dashboard/analytics
Content-Type: application/json

Response:
{
    "total_bookings": 150,
    "recent_activities": [...],
    "dealer_stats": [...],
    "monthly_metrics": [...]
}
```

#### User Management
```http
GET /api/admin/users
POST /api/admin/users
PUT /api/admin/users/{id}
DELETE /api/admin/users/{id}
```

#### Dealership Management
```http
GET /api/admin/dealerships
POST /api/admin/dealerships
PUT /api/admin/dealerships/{id}
DELETE /api/admin/dealerships/{id}
```

#### Get User Profile
```php
GET /api/v1/users/{user_id}
Authorization: Bearer {token}
```

Response:
```json
{
    "id": 123,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "student",
    "profile": {
        "avatar": "https://example.com/avatars/123.jpg",
        "bio": "Student of mathematics",
        "preferences": {
            "notifications": true,
            "language": "en"
        }
    }
}
```

### Response Formats

#### Success Response
```json
{
    "status": "success",
    "data": {
        // Response data here
    }
}
```
```

#### Create Course
```php
POST /api/v1/courses
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Advanced Physics",
    "description": "University level physics course",
    "instructor_id": 456,
    "duration": "16 weeks",
    "start_date": "2025-11-01T00:00:00Z",
    "capacity": 30
}
```

### Dashboard Analytics Response Example
```json
{
    "status": "success",
    "data": {
        "total_bookings": 150,
        "recent_activities": [
            {
                "type": "booking",
                "timestamp": "2025-10-08T10:30:00Z",
                "details": "New test drive booking from Mumbai"
            }
        ],
        "dealer_stats": [
            {
                "dealer_id": 1,
                "name": "Mumbai Central",
                "total_bookings": 45
            }
        ]
    }
}
```

### Assessment System

#### Create Assessment
```php
POST /api/v1/assessments
Authorization: Bearer {token}
Content-Type: application/json

{
    "course_id": 1,
    "title": "Midterm Exam",
    "duration": 7200,
    "questions": [
        {
            "type": "multiple_choice",
            "text": "What is 2+2?",
            "options": ["3", "4", "5", "6"],
            "correct_answer": 1
        }
    ]
}
```

#### Submit Assessment
```php
POST /api/v1/assessments/{assessment_id}/submit
Authorization: Bearer {token}
Content-Type: application/json

{
    "answers": [
        {
            "question_id": 1,
            "answer": 1
        }
    ]
}
```

### Analytics

#### Get Course Analytics
```php
GET /api/v1/analytics/courses/{course_id}
Authorization: Bearer {token}
```

Response:
```json
{
    "course_id": 1,
    "total_students": 25,
    "completion_rate": 85.5,
    "average_score": 78.3,
    "engagement_metrics": {
        "video_completion_rate": 92.1,
        "assignment_submission_rate": 88.7
    }
}
```

## Error Handling

### Error Response Format
```json
{
    "error": {
        "code": "validation_error",
        "message": "The given data was invalid",
        "details": {
            "email": ["The email field is required"]
        }
    }
}
```

### Common Error Codes
- `validation_error`: 400
- `unauthorized`: 401
- `forbidden`: 403
- `not_found`: 404
- `rate_limit_exceeded`: 429
- `server_error`: 500

## Rate Limiting

The API implements rate limiting per user:
- 1000 requests per hour for authenticated users
- 60 requests per hour for unauthenticated users

Rate limit headers:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1633694500
```

## Versioning

The API uses URI versioning:
- Current version: `v1`
- Base URL: `https://api.kineticeducation.com/v1`

## Best Practices

1. Authentication
   - Always use HTTPS
   - Keep tokens secure
   - Implement refresh token rotation

2. Request Format
   - Use proper Content-Type headers
   - Follow REST conventions
   - Include API version

3. Response Handling
   - Check status codes
   - Handle errors gracefully
   - Parse response carefully

## Support

### API Support
For API support:
1. Email: api-support@kineticeducation.com
2. Developer Portal: https://developers.kineticeducation.com
3. Status Page: https://status.kineticeducation.com

### Issue Reporting
Include:
1. Endpoint URL
2. Request method
3. Request payload
4. Error response
5. Timestamp