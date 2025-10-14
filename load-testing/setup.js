#!/usr/bin/env node
/**
 * Quick Setup and Demo
 * Sets up load testing environment and runs a demo
 */

const fs = require('fs');

console.log('\n🚀 KineticEV Load Testing Setup');
console.log('==================================================');

// Check if all required files exist
const requiredFiles = [
  'package.json',
  'artillery-light-test.js', 
  'autocannon-quick-test.js',
  'monitor-server.js',
  'test-data.csv'
];

console.log('\n📋 Checking setup...');

let allFilesExist = true;
requiredFiles.forEach(file => {
  if (fs.existsSync(file)) {
    console.log(`✅ ${file}`);
  } else {
    console.log(`❌ ${file} - MISSING`);
    allFilesExist = false;
  }
});

if (!allFilesExist) {
  console.log('\n❌ Setup incomplete - some files are missing');
  process.exit(1);
}

console.log('\n✅ All files present!');

// Check if node_modules exists
if (fs.existsSync('node_modules')) {
  console.log('✅ Dependencies installed');
} else {
  console.log('⚠️  Dependencies not installed. Run: npm install');
}

console.log('\n🎯 Quick Start Guide:');
console.log('1. Run interactive test menu:');
console.log('   node run-tests.js');
console.log();
console.log('2. Run specific tests:');
console.log('   npm run test:light     # Light load test');
console.log('   npm run test:api       # API-focused test');
console.log('   node autocannon-quick-test.js  # Quick benchmark');
console.log();
console.log('3. Monitor system resources:');
console.log('   npm run monitor        # Real-time monitoring');
console.log();

console.log('🔧 Available Test Types:');
console.log('• Quick Test (30 seconds)    - Fast benchmark of main endpoints');
console.log('• Light Load (2 minutes)     - 10-50 virtual users');
console.log('• Moderate Load (7 minutes)  - 50-200 virtual users');
console.log('• API Test (3 minutes)       - Backend API focused');
console.log('• System Monitoring          - Real-time resource tracking');
console.log();

console.log('🎯 Recommended Testing Strategy:');
console.log('1. Start with Quick Test to verify basic functionality');
console.log('2. Run Light Load test to establish baseline');
console.log('3. Use System Monitoring during tests');
console.log('4. Gradually increase load with Moderate test');
console.log('5. Focus on API performance with API test');
console.log();

console.log('📊 What Gets Tested:');
console.log('✅ Homepage and product pages');
console.log('✅ Contact and booking forms');
console.log('✅ OTP generation and verification APIs');
console.log('✅ Form submission endpoints');
console.log('✅ Static asset loading (CSS, JS)');
console.log('✅ Database performance under load');
console.log('✅ Error rates and response times');
console.log();

console.log('⚠️  Important Notes:');
console.log('• Tests use fake phone numbers (safe for OTP testing)');
console.log('• Start with development site before testing production');
console.log('• Monitor server resources during tests');
console.log('• Production tests should use lower loads initially');
console.log();

console.log('🚀 Ready to start testing!');
console.log('Run: node run-tests.js for interactive menu');