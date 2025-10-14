/**
 * Artillery.js Light Load Test
 * Basic performance baseline testing
 */

module.exports = {
  config: {
    target: 'https://www.kineticev.in',
    phases: [
      {
        // Warm-up phase
        duration: 30,
        arrivalRate: 2,
        name: 'Warm-up'
      },
      {
        // Light load phase
        duration: 60,
        arrivalRate: 10,
        name: 'Light Load'
      },
      {
        // Sustained load
        duration: 30,
        arrivalRate: 15,
        name: 'Sustained Load'
      }
    ],
    payload: {
      path: './test-data.csv',
      fields: ['phone', 'name', 'email']
    },
    defaults: {
      headers: {
        'User-Agent': 'KineticEV-LoadTest/1.0',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1'
      }
    },
    plugins: {
      'artillery-plugin-metrics-by-endpoint': {
        useOnlyRequestNames: true
      }
    }
  },
  scenarios: [
    {
      name: 'Homepage Visit',
      weight: 30,
      flow: [
        {
          get: {
            url: '/',
            name: 'Homepage'
          }
        },
        {
          think: 2
        }
      ]
    },
    {
      name: 'Product Browse',
      weight: 25,
      flow: [
        {
          get: {
            url: '/',
            name: 'Homepage'
          }
        },
        {
          think: 1
        },
        {
          get: {
            url: '/range-x',
            name: 'Range X Product Page'
          }
        },
        {
          think: 3
        },
        {
          get: {
            url: '/see-comparison',
            name: 'Comparison Page'
          }
        }
      ]
    },
    {
      name: 'Contact Form View',
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
        }
      ]
    },
    {
      name: 'Dealership Finder',
      weight: 15,
      flow: [
        {
          get: {
            url: '/dealership-finder-pincode',
            name: 'Dealership Finder'
          }
        },
        {
          think: 2
        }
      ]
    },
    {
      name: 'Static Assets',
      weight: 10,
      flow: [
        {
          get: {
            url: '/css/main.css',
            name: 'CSS Load'
          }
        },
        {
          get: {
            url: '/js/main.js',
            name: 'JS Load'
          }
        }
      ]
    }
  ]
};