/**
 * Artillery Test Helper Functions
 * Generates test data for API load testing
 */

// Test phone numbers (Indian format, not real numbers)
const testPhones = [
  '9000000001', '9000000002', '9000000003', '9000000004', '9000000005',
  '8000000001', '8000000002', '8000000003', '8000000004', '8000000005',
  '7000000001', '7000000002', '7000000003', '7000000004', '7000000005'
];

// Test names
const testNames = [
  'Test User 1', 'Test User 2', 'Test User 3', 'Test User 4', 'Test User 5',
  'Load Test User', 'Performance Test', 'API Tester', 'Form Tester', 'OTP Tester'
];

// Test email domains
const testDomains = ['loadtest.com', 'apitest.com', 'performance.test', 'kineticev.test'];

// Generate random test phone number
function generateTestPhone() {
  return testPhones[Math.floor(Math.random() * testPhones.length)];
}

// Generate random test name
function generateTestName() {
  return testNames[Math.floor(Math.random() * testNames.length)];
}

// Generate random test email
function generateTestEmail() {
  const names = ['test', 'loadtest', 'performance', 'api', 'user'];
  const name = names[Math.floor(Math.random() * names.length)];
  const number = Math.floor(Math.random() * 1000);
  const domain = testDomains[Math.floor(Math.random() * testDomains.length)];
  return `${name}${number}@${domain}`;
}

// Validate API response
function validateApiResponse(requestParams, response, context, ee, next) {
  if (response.statusCode >= 400) {
    console.log(`API Error: ${response.statusCode} - ${response.body}`);
    ee.emit('counter', 'api.errors', 1);
  } else {
    ee.emit('counter', 'api.success', 1);
  }
  
  // Check response time
  if (response.timings && response.timings.response > 2000) {
    ee.emit('counter', 'api.slow_responses', 1);
  }
  
  return next();
}

// Setup test context
function setupTestContext(context, events, done) {
  context.vars.testSessionId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
  console.log(`Starting test session: ${context.vars.testSessionId}`);
  return done();
}

// Cleanup test context
function cleanupTestContext(context, events, done) {
  console.log(`Completed test session: ${context.vars.testSessionId}`);
  return done();
}

// Export functions
module.exports = {
  generateTestPhone,
  generateTestName,
  generateTestEmail,
  validateApiResponse,
  setupTestContext,
  cleanupTestContext
};