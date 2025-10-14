# KineticEV Load Testing Suite

This folder contains comprehensive load testing tools for your KineticEV website using free, open-source tools.

## 🚀 Quick Start

```bash
# Install dependencies
cd load-testing
npm install

# Run basic load test
npm run test:light

# Run API-focused test
npm run test:api

# Generate performance report
npm run report
```

## 🛠️ Testing Tools Included

### 1. Artillery.js (Primary Tool)
- **Best for**: HTTP/HTTPS load testing, realistic user scenarios
- **Strengths**: Easy configuration, great reporting, realistic user flows
- **Use cases**: Overall website performance, form submissions, API endpoints

### 2. K6 (Alternative)
- **Best for**: Developer-focused load testing, JavaScript-based scenarios
- **Strengths**: Powerful scripting, excellent metrics, CI/CD integration
- **Use cases**: Complex user scenarios, API performance testing

### 3. Autocannon (Quick Tests)
- **Best for**: Fast, simple HTTP benchmarking
- **Strengths**: Very fast execution, minimal setup
- **Use cases**: Quick performance checks, single endpoint testing

### 4. System Monitoring
- **Best for**: Server resource monitoring during tests
- **Strengths**: CPU, memory, disk usage tracking
- **Use cases**: Identifying bottlenecks, resource optimization

## 📋 Test Scenarios

### Light Load Test (`npm run test:light`)
- **Virtual Users**: 10-50
- **Duration**: 2 minutes
- **Purpose**: Basic performance baseline
- **Target**: Main pages (home, products, contact)

### Moderate Load Test (`npm run test:moderate`)
- **Virtual Users**: 50-200
- **Duration**: 5 minutes  
- **Purpose**: Normal traffic simulation
- **Target**: All user journeys

### Heavy Load Test (`npm run test:heavy`)
- **Virtual Users**: 200-500
- **Duration**: 10 minutes
- **Purpose**: Stress testing
- **Target**: Full site functionality

### API-Specific Tests (`npm run test:api`)
- **Focus**: Backend API endpoints
- **Scenarios**: OTP generation/verification, form submissions
- **Purpose**: Database and API performance

### Form Testing (`npm run test:forms`)
- **Focus**: Contact forms, booking forms, test drive requests
- **Purpose**: Form submission performance under load
- **Validation**: Error handling, response times

### Spike Test (`npm run test:spike`)
- **Pattern**: Sudden traffic spikes
- **Purpose**: Traffic surge handling
- **Duration**: Short bursts of high load

## 🎯 Key Endpoints to Test

Based on your site structure:

### Public Pages
- `/` (Homepage)
- `/range-x` (Product page)
- `/contact-us` (Contact form)
- `/book-now` (Booking form)
- `/dealership-finder-pincode` (Dealership finder)

### API Endpoints
- `/api/generate-otp` (OTP generation)
- `/api/verify-otp` (OTP verification)
- `/api/save-contact` (Contact form)
- `/api/submit-test-drive` (Test drive booking)
- `/api/process-payment` (Payment processing)
- `/api/distance-check` (Distance calculation)

### WordPress Blog
- `/blog/` (Blog homepage)
- `/blog/wp-admin/admin-ajax.php` (WordPress AJAX)

## 📊 Monitoring & Reporting

### Metrics Tracked
- **Response Times**: Average, P95, P99
- **Throughput**: Requests per second
- **Error Rates**: 4xx, 5xx errors
- **System Resources**: CPU, Memory, Disk I/O
- **Database Performance**: Query times, connections

### Reports Generated
- HTML performance reports
- JSON data for analysis
- System resource usage graphs
- Error logs and analysis
- Recommendations for optimization

## ⚙️ Configuration

### Environment Variables
Create `.env` file:
```bash
# Target URLs
PRODUCTION_URL=https://www.kineticev.in
DEV_URL=http://dev.kineticev.in
BLOG_URL=https://blog.kineticev.in

# Test Configuration
MAX_VIRTUAL_USERS=500
TEST_DURATION=300
RAMP_UP_TIME=30

# Monitoring
MONITOR_INTERVAL=5000
ALERT_THRESHOLD_MS=2000
ERROR_THRESHOLD_PERCENT=5
```

### Test Customization
Each test file can be customized for:
- Number of virtual users
- Test duration
- Ramp-up patterns
- Target endpoints
- Success criteria

## 🔧 Usage Examples

### Test Specific Functionality
```bash
# Test only OTP system
node artillery-api-test.js --focus=otp

# Test only contact forms  
node artillery-forms-test.js --forms=contact

# Test with custom user load
node artillery-moderate-test.js --users=100 --duration=180
```

### Monitor During Tests
```bash
# Start monitoring (run in separate terminal)
npm run monitor

# Run load test (in another terminal)
npm run test:moderate
```

### Generate Custom Reports
```bash
# After running tests
npm run report

# Generate comparison report
node generate-report.js --compare previous-results.json
```

## 🎯 Expected Performance Targets

### Response Times
- **Homepage**: < 800ms (P95)
- **API Endpoints**: < 500ms (P95)  
- **Form Submissions**: < 1000ms (P95)
- **OTP Generation**: < 2000ms (P95)

### Throughput
- **Concurrent Users**: 200+ without degradation
- **Requests/Second**: 100+ sustained
- **Form Submissions**: 10+/second peak

### Error Rates
- **Overall Error Rate**: < 1%
- **API Errors**: < 0.5%
- **Database Timeouts**: < 0.1%

## 🚨 Troubleshooting

### Common Issues
1. **High Response Times**: Check database queries, server resources
2. **API Errors**: Verify OTP service, database connections
3. **Form Failures**: Check validation logic, file uploads
4. **Memory Issues**: Monitor PHP memory limits, database connections

### Optimization Recommendations
1. **Caching**: Implement Redis/Memcached for sessions
2. **Database**: Optimize queries, add indexes
3. **CDN**: Use CDN for static assets
4. **Load Balancing**: Consider multiple server instances
5. **PHP-FPM**: Tune process limits and timeouts

## 📝 Notes

- **Development Testing**: Use dev.kineticev.in for testing
- **Production Testing**: Use low loads initially on production
- **Database**: Monitor MySQL performance during tests
- **WordPress**: Blog may need separate testing approach
- **Mobile**: Test mobile-specific endpoints separately

## 🔒 Security Considerations

- Don't test with real phone numbers for OTP
- Use test payment credentials only
- Monitor for unusual traffic patterns
- Implement rate limiting based on test results
- Test authentication and session handling

This suite provides comprehensive load testing capabilities without any paid services, giving you detailed insights into your website's performance under various load conditions.