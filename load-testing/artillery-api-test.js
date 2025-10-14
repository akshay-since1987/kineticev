/**
 * Artillery.js API Load Test
 * Focused testing of backend API endpoints
 */

module.exports = {
  config: {
    target: 'https://www.kineticev.in',
    phases: [
      {
        duration: 30,
        arrivalRate: 5,
        name: 'API Warm-up'
      },
      {
        duration: 120,
        arrivalRate: 20,
        name: 'API Load Test'
      },
      {
        duration: 60,
        arrivalRate: 30,
        name: 'API Stress Test'
      }
    ],
    defaults: {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': 'KineticEV-API-LoadTest/1.0'
      }
    },
    processor: './api-test-functions.js'
  },
  scenarios: [
    {
      name: 'OTP Generation Flow',
      weight: 40,
      flow: [
        {
          post: {
            url: '/api/generate-otp',
            name: 'Generate OTP - Contact',
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
            name: 'Verify OTP - Contact',
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
      name: 'Booking OTP Flow',
      weight: 30,
      flow: [
        {
          post: {
            url: '/api/generate-otp',
            name: 'Generate OTP - Booking',
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
            name: 'Verify OTP - Booking',
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
      name: 'Contact Form Submission',
      weight: 20,
      flow: [
        {
          post: {
            url: '/api/save-contact',
            name: 'Submit Contact Form',
            json: {
              name: '{{ generateTestName() }}',
              email: '{{ generateTestEmail() }}',
              phone: '{{ generateTestPhone() }}',
              message: 'Load test contact form submission',
              phone_verified: '1'
            }
          }
        }
      ]
    },
    {
      name: 'Test Drive Request',
      weight: 10,
      flow: [
        {
          post: {
            url: '/api/submit-test-drive',
            name: 'Submit Test Drive Request',
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