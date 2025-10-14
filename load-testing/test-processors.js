/**
 * Test Processors for Artillery Load Tests
 * Helper functions for test scenarios
 */

// Generate random think time between min and max seconds
function randomThink(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

// Log request details for debugging
function logRequest(requestParams, response, context, ee, next) {
  if (response.statusCode >= 400) {
    console.log(`❌ ${requestParams.url} - ${response.statusCode}: ${response.body}`);
  } else if (response.timings && response.timings.response > 2000) {
    console.log(`⚠️  Slow response: ${requestParams.url} - ${response.timings.response}ms`);
  }
  return next();
}

// Set custom headers based on scenario
function setCustomHeaders(requestParams, context, ee, next) {
  // Add timestamp header for tracking
  requestParams.headers = requestParams.headers || {};
  requestParams.headers['X-Load-Test-Time'] = Date.now();
  requestParams.headers['X-Load-Test-Session'] = context.vars.$uuid;
  
  return next();
}

// Validate OTP responses
function validateOtpResponse(requestParams, response, context, ee, next) {
  try {
    const data = JSON.parse(response.body);
    
    if (requestParams.url.includes('/api/generate-otp')) {
      if (data.success) {
        ee.emit('counter', 'otp.generate.success', 1);
      } else {
        ee.emit('counter', 'otp.generate.error', 1);
      }
    }
    
    if (requestParams.url.includes('/api/verify-otp')) {
      if (data.success) {
        ee.emit('counter', 'otp.verify.success', 1);
      } else {
        ee.emit('counter', 'otp.verify.error', 1);
      }
    }
  } catch (e) {
    ee.emit('counter', 'json.parse.error', 1);
  }
  
  return next();
}

// Track form submission success
function trackFormSubmission(requestParams, response, context, ee, next) {
  if (requestParams.url.includes('/api/save-contact')) {
    if (response.statusCode === 200) {
      ee.emit('counter', 'form.contact.success', 1);
    } else {
      ee.emit('counter', 'form.contact.error', 1);
    }
  }
  
  if (requestParams.url.includes('/api/submit-test-drive')) {
    if (response.statusCode === 200) {
      ee.emit('counter', 'form.testdrive.success', 1);
    } else {
      ee.emit('counter', 'form.testdrive.error', 1);
    }
  }
  
  return next();
}

// Generate realistic user behavior patterns
function generateUserBehavior(context, events, done) {
  // Random user type
  const userTypes = ['researcher', 'buyer', 'browser', 'support'];
  context.vars.userType = userTypes[Math.floor(Math.random() * userTypes.length)];
  
  // Random session duration preference
  context.vars.sessionDuration = Math.random() > 0.7 ? 'long' : 'short';
  
  // Random device type simulation
  const devices = ['desktop', 'mobile', 'tablet'];
  context.vars.deviceType = devices[Math.floor(Math.random() * devices.length)];
  
  return done();
}

// Clean up test session
function cleanupSession(context, events, done) {
  // Log session completion
  console.log(`Session completed: ${context.vars.$uuid} (${context.vars.userType})`);
  return done();
}

module.exports = {
  randomThink,
  logRequest,
  setCustomHeaders,
  validateOtpResponse,
  trackFormSubmission,
  generateUserBehavior,
  cleanupSession
};