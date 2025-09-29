// Import debug helper first for early error tracking
import "./debug-helper.js";

// Import modules that are used by other modules
import "./DealershipFinder.js"; // Make DealershipFinder globally available
import "./dealership-map.js"; // Make DealershipMap globally available
import "./contact-map.js"; // Make ContactMap globally available
import "./object360Viewer.js"; // Import 360 viewer
import "./meta-pixel-tracking.js"; // Import Meta Pixel tracking
import "./otp-verification.js"; // Import OTP verification

// Import the main application entry point
import "./index.js";

// Import additional functionality
import "./modal-fix.js"; // Modal specific functionality

// Register that main.js has loaded all scripts
if (window.K2Debug) {
    window.K2Debug.trackScript('main.js');
    console.log('✅ All scripts loaded and bundled successfully');
}