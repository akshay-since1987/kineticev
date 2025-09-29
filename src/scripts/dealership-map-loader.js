// Load the script file dynamically after the Google Maps API is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Create a script element to load our dealership map script
  const script = document.createElement('script');
  script.src = '/src/dist/js/dealership-map.js';
  script.async = true;
  
  // Append the script to the document
  document.body.appendChild(script);
});
