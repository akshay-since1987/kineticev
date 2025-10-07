# API Documentation

## Overview
This document provides comprehensive documentation for the Kinetic Education Platform's API endpoints.

## Authentication

### OAuth2 Authentication
```php
POST /api/v1/auth/token
Content-Type: application/json

{
    "grant_type": "password",
    "username": "user@example.com",
    "password": "your_password",
    "client_id": "your_client_id",
    "client_secret": "your_client_secret"
}
```

Response:
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "def50200641f136..."
}
```

## API Endpoints

### User Management

#### Create User
```php
POST /api/v1/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "secure_password",
    "name": "John Doe",
    "role": "student"
}
```

Response:
```json
{
    "id": 123,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "student",
    "created_at": "2025-10-08T12:00:00Z"
}
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

### Course Management

#### List Courses
```php
GET /api/v1/courses
Authorization: Bearer {token}
```

Response:
```json
{
    "data": [
        {
            "id": 1,
            "title": "Introduction to Mathematics",
            "description": "Basic mathematics course",
            "instructor": {
                "id": 456,
                "name": "Dr. Smith"
            },
            "duration": "12 weeks",
            "start_date": "2025-11-01T00:00:00Z"
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 10,
        "current_page": 1
    }
}
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

### Content Management

#### Upload Content
```php
POST /api/v1/content
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "title": "Lecture 1",
    "description": "Introduction to the course",
    "course_id": 1,
    "file": [binary_data],
    "type": "video"
}
```

Response:
```json
{
    "id": 789,
    "title": "Lecture 1",
    "url": "https://content.kineticeducation.com/videos/789.mp4",
    "type": "video",
    "duration": "01:30:00",
    "size": 256000000
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