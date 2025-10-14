/**
 * K6 Load Test Script
 * Alternative load testing tool with JavaScript-based scenarios
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
  stages: [
    { duration: '30s', target: 10 },   // Ramp up to 10 users
    { duration: '1m', target: 50 },    // Stay at 50 users
    { duration: '2m', target: 100 },   // Ramp up to 100 users
    { duration: '1m', target: 100 },   // Stay at 100 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
    errors: ['rate<0.05'],             // Error rate must be less than 5%
  },
};

const BASE_URL = 'https://www.kineticev.in';

// Test data
const testPhones = ['9000000001', '9000000002', '9000000003', '9000000004', '9000000005'];
const testNames = ['K6 Test User 1', 'K6 Test User 2', 'K6 Test User 3'];
const testEmails = ['k6test1@loadtest.com', 'k6test2@loadtest.com', 'k6test3@loadtest.com'];

function getRandomTestData() {
  return {
    phone: testPhones[Math.floor(Math.random() * testPhones.length)],
    name: testNames[Math.floor(Math.random() * testNames.length)],
    email: testEmails[Math.floor(Math.random() * testEmails.length)]
  };
}

export default function () {
  const testData = getRandomTestData();
  
  // Test scenario selection (weighted)
  const scenario = Math.random();
  
  if (scenario < 0.4) {
    // 40% - Homepage and product browsing
    homepageBrowsingScenario();
  } else if (scenario < 0.7) {
    // 30% - API testing (OTP flow)
    apiTestingScenario(testData);
  } else if (scenario < 0.9) {
    // 20% - Contact form flow
    contactFormScenario(testData);
  } else {
    // 10% - Static asset loading
    staticAssetScenario();
  }
  
  sleep(1);
}

function homepageBrowsingScenario() {
  // Load homepage
  let response = http.get(`${BASE_URL}/`);
  check(response, {
    'Homepage loaded': (r) => r.status === 200,
    'Homepage load time OK': (r) => r.timings.duration < 2000,
  });
  
  if (response.status !== 200) {
    errorRate.add(1);
  }
  
  sleep(1);
  
  // Load product page
  response = http.get(`${BASE_URL}/range-x`);
  check(response, {
    'Product page loaded': (r) => r.status === 200,
    'Product page load time OK': (r) => r.timings.duration < 2000,
  });
  
  if (response.status !== 200) {
    errorRate.add(1);
  }
  
  sleep(2);
}

function apiTestingScenario(testData) {
  // Generate OTP
  const otpPayload = {
    phone: testData.phone,
    purpose: 'contact_form'
  };
  
  let response = http.post(`${BASE_URL}/api/generate-otp`, JSON.stringify(otpPayload), {
    headers: { 'Content-Type': 'application/json' },
  });
  
  const otpGenerated = check(response, {
    'OTP generation successful': (r) => r.status === 200,
    'OTP API response time OK': (r) => r.timings.duration < 3000,
    'OTP response is JSON': (r) => {
      try {
        JSON.parse(r.body);
        return true;
      } catch {
        return false;
      }
    },
  });
  
  if (!otpGenerated) {
    errorRate.add(1);
  }
  
  sleep(1);
  
  // Verify OTP (using test OTP)
  const verifyPayload = {
    phone: testData.phone,
    otp: '123456',
    purpose: 'contact_form'
  };
  
  response = http.post(`${BASE_URL}/api/verify-otp`, JSON.stringify(verifyPayload), {
    headers: { 'Content-Type': 'application/json' },
  });
  
  const otpVerified = check(response, {
    'OTP verification processed': (r) => r.status === 200,
    'OTP verify response time OK': (r) => r.timings.duration < 2000,
  });
  
  if (!otpVerified) {
    errorRate.add(1);
  }
}

function contactFormScenario(testData) {
  // Load contact page
  let response = http.get(`${BASE_URL}/contact-us`);
  check(response, {
    'Contact page loaded': (r) => r.status === 200,
  });
  
  sleep(1);
  
  // Submit contact form
  const formPayload = {
    name: testData.name,
    email: testData.email,
    phone: testData.phone,
    message: 'K6 load test message',
    phone_verified: '1'
  };
  
  response = http.post(`${BASE_URL}/api/save-contact`, JSON.stringify(formPayload), {
    headers: { 'Content-Type': 'application/json' },
  });
  
  check(response, {
    'Contact form submitted': (r) => r.status === 200,
    'Contact form response time OK': (r) => r.timings.duration < 2000,
  });
  
  if (response.status !== 200) {
    errorRate.add(1);
  }
}

function staticAssetScenario() {
  // Load CSS
  let response = http.get(`${BASE_URL}/css/main.css`);
  check(response, {
    'CSS loaded': (r) => r.status === 200,
  });
  
  // Load JS
  response = http.get(`${BASE_URL}/js/main.js`);
  check(response, {
    'JS loaded': (r) => r.status === 200,
  });
  
  sleep(0.5);
}