/**
 * Artillery.js API Load Test - UAT Environment
 * Focused testing of backend API endpoints on uat.kineticev.in
 */

module.exports = {
  config: {
    target: 'http://uat.kineticev.in',
    phases: [
      {
        duration: 30,
        arrivalRate: 5,
        name: 'UAT API Warm-up'
      },
      {
        duration: 120,
        arrivalRate: 20,
        name: 'UAT API Load Test'
      },
      {
        duration: 60,
        arrivalRate: 30,
        name: 'UAT API Stress Test'
      }
    ],
    defaults: {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': 'KineticEV-UAT-API-LoadTest/1.0'
      }
    },
    processor: './api-test-functions.js'
  },
  scenarios: [
    {
      name: 'UAT OTP Generation Flow',
      weight: 40,
      flow: [
        {
          post: {
            url: '/api/generate-otp',
            name: 'UAT Generate OTP - Contact',
            json: {
              phone: '{{ generateTestPhone() }}',
              purpose: 'contact_form'
            },
            capture: {
              json: '$.success',
              as: 'otpGenerated'
            }
          }
        },
        {
          think: 1
        },
        {
          post: {
            url: '/api/verify-otp',
            name: 'UAT Verify OTP - Contact',
            json: {
              phone: '{{ generateTestPhone() }}',
              otp: '123456',
              purpose: 'contact_form'
            }
          }
        }
      ]
    },
    {
      name: 'UAT Booking OTP Flow',
      weight: 30,
      flow: [
        {
          post: {
            url: '/api/generate-otp',
            name: 'UAT Generate OTP - Booking',
            json: {
              phone: '{{ generateTestPhone() }}',
              purpose: 'booking_form'
            }
          }
        },
        {
          think: 2
        },
        {
          post: {
            url: '/api/verify-otp',
            name: 'UAT Verify OTP - Booking',
            json: {
              phone: '{{ generateTestPhone() }}',
              otp: '123456',
              purpose: 'booking_form'
            }
          }
        }
      ]
    },
    {
      name: 'UAT Contact Form Submission',
      weight: 20,
      flow: [
        {
          post: {
            url: '/api/save-contact',
            name: 'UAT Submit Contact Form',
            json: {
              name: '{{ generateTestName() }}',
              email: '{{ generateTestEmail() }}',
              phone: '{{ generateTestPhone() }}',
              message: 'UAT load test contact form submission',
              phone_verified: '1'
            }
          }
        }
      ]
    },
    {
      name: 'UAT Test Drive Request',
      weight: 10,
      flow: [
        {
          post: {
            url: '/api/submit-test-drive',
            name: 'UAT Submit Test Drive Request',
            json: {
              name: '{{ generateTestName() }}',
              email: '{{ generateTestEmail() }}',
              phone: '{{ generateTestPhone() }}',
              city: 'Mumbai',
              preferred_date: '2025-10-15',
              phone_verified: '1'
            }
          }
        }
      ]
    }
  ]
};