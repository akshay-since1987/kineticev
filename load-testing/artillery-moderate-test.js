/**
 * Artillery Moderate Load Test
 * Normal traffic simulation with realistic user patterns
 */

module.exports = {
  config: {
    target: 'https://www.kineticev.in',
    phases: [
      {
        duration: 60,
        arrivalRate: 5,
        name: 'Warm-up Phase'
      },
      {
        duration: 120,
        arrivalRate: 25,
        name: 'Load Phase'
      },
      {
        duration: 180,
        arrivalRate: 50,
        name: 'Sustained Load'
      },
      {
        duration: 60,
        arrivalRate: 75,
        name: 'Peak Load'
      },
      {
        duration: 60,
        arrivalRate: 25,
        name: 'Cool Down'
      }
    ],
    payload: {
      path: './test-data.csv',
      fields: ['phone', 'name', 'email']
    },
    defaults: {
      headers: {
        'User-Agent': 'KineticEV-LoadTest-Moderate/1.0',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate',
        'Connection': 'keep-alive',
        'Cache-Control': 'no-cache'
      }
    },
    plugins: {
      'artillery-plugin-metrics-by-endpoint': {
        useOnlyRequestNames: true
      }
    },
    processor: './test-processors.js'
  },
  scenarios: [
    {
      name: 'Complete User Journey',
      weight: 40,
      flow: [
        {
          get: {
            url: '/',
            name: 'Homepage'
          }
        },
        {
          think: '{{ randomThink(1, 3) }}'
        },
        {
          get: {
            url: '/range-x',
            name: 'Product Page'
          }
        },
        {
          think: '{{ randomThink(2, 5) }}'
        },
        {
          get: {
            url: '/see-comparison',
            name: 'Comparison'
          }
        },
        {
          think: '{{ randomThink(1, 3) }}'
        },
        {
          get: {
            url: '/contact-us',
            name: 'Contact Page'
          }
        }
      ]
    },
    {
      name: 'Quick Browse and Leave',
      weight: 30,
      flow: [
        {
          get: {
            url: '/',
            name: 'Homepage'
          }
        },
        {
          think: '{{ randomThink(1, 2) }}'
        },
        {
          get: {
            url: '/range-x',
            name: 'Product Page'
          }
        }
      ]
    },
    {
      name: 'Contact Form Flow',
      weight: 20,
      flow: [
        {
          get: {
            url: '/contact-us',
            name: 'Contact Page'
          }
        },
        {
          think: 2
        },
        {
          post: {
            url: '/api/generate-otp',
            name: 'Generate OTP',
            json: {
              phone: '{{ phone }}',
              purpose: 'contact_form'
            }
          }
        },
        {
          think: 1
        },
        {
          post: {
            url: '/api/verify-otp',
            name: 'Verify OTP',
            json: {
              phone: '{{ phone }}',
              otp: '123456',
              purpose: 'contact_form'
            }
          }
        },
        {
          think: 1
        },
        {
          post: {
            url: '/api/save-contact',
            name: 'Submit Contact',
            json: {
              name: '{{ name }}',
              email: '{{ email }}',
              phone: '{{ phone }}',
              message: 'Load test contact submission',
              phone_verified: '1'
            }
          }
        }
      ]
    },
    {
      name: 'Dealership Finder',
      weight: 10,
      flow: [
        {
          get: {
            url: '/dealership-finder-pincode',
            name: 'Dealership Finder'
          }
        },
        {
          think: '{{ randomThink(2, 4) }}'
        },
        {
          get: {
            url: '/api/distance-check?pincode=400001',
            name: 'Distance Check API'
          }
        }
      ]
    }
  ]
};