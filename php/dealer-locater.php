<?php
/**
 * Dealership Map Page
 * 
 * This page displays a map of all dealership locations and allows users
 * to search for dealerships by PIN code or location name.
 */

// Include the layout components
require_once 'components/layout.php';

// Include DealershipFinder class (use absolute path for reliability)
require_once __DIR__ . '/DealershipFinder.php';

// Initialize dealership variables
$dealerships = [];
$dealershipsJson = '[]';
$dealershipCount = 0;
$dealershipsByState = [];
$errorMessage = '';

try {
    // Create a new instance of DealershipFinder
    $dealershipFinder = new DealershipFinder();
    
    // Make sure the dealership table exists
    $dealershipFinder->createDealershipsTable();
    
    // Debug: Count all dealerships directly from the database
    $rawDealerships = $dealershipFinder->getAllDealerships();
    $rawCount = count($rawDealerships);
    
    // Debug: Add a log message
    error_log("Raw dealership count from database: " . $rawCount);
    
    // Get all dealership variables
    $dealershipVars = $dealershipFinder->getDealershipVariables();
    
    // Extract variables
    $dealerships = $dealershipVars['dealerships'];
    $dealershipsJson = $dealershipVars['dealerships_json'];
    $dealershipCount = $dealershipVars['dealerships_count'];
    $dealershipsByState = $dealershipVars['dealerships_by_state'];
    
    // Debug: Add raw count to JavaScript output
    $rawDealershipsJson = json_encode($rawDealerships);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in dealership-map.php: " . $e->getMessage());
    $errorMessage = "There was an issue loading dealership data. Please try again later.";
}

// Start the layout with a title
startLayout("Find a Dealership | Kinetic", [
    'include_test_drive_modal' => true,
    'include_video_modal' => false,
    'page_class' => 'dealership-map-page'
]);

// Include Google Maps API script with necessary libraries - without defer
include_once 'components/google-maps-script.php';
?>

<!-- InfoWindow Styles -->
<style>
    /* Google Maps InfoWindow Styles */
    .marker-info-window {
        padding: 5px;
        max-width: 300px;
        font-family: 'Roboto', Arial, sans-serif;
    }
    
    .marker-info-window h3 {
        margin-top: 0;
        margin-bottom: 8px;
        font-size: 16px;
        color: #192A5A;
        font-weight: 700;
    }
    
    .marker-info-window p {
        margin-bottom: 10px;
        font-size: 13px;
        line-height: 1.4;
        color: #333;
    }
    
    .marker-info-window .view-details-link {
        display: inline-block;
        color: #ED1C24;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        padding: 5px 0;
    }
    
    .marker-info-window .view-details-link:hover {
        text-decoration: underline;
    }
    
    /* Override some Google Maps InfoWindow styles */
    .gm-style .gm-style-iw-c {
        padding: 12px !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15) !important;
    }
</style>

<div class="dealership-page">
    <!-- <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="page-title">Find a Dealership</h1>
                <p class="page-description">Locate your nearest Kinetic dealership using our interactive map or search by pincode.</p>
                
                <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($dealershipCount === 0 && empty($errorMessage)): ?>
                <div class="alert alert-info">
                    No dealerships found. Please check back later.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div> -->
    
    <div class="dealership-map-container">
        <div class="dealership-locations">
            <div class="maps" id="dealership-map"></div>
            <div class="location-form">
                <h2><b>Ready</b> to Ride?</h2>
                <h3>
                    <b>Wherever you are, </b>
                    Your electric journey begins today.
                </h3>
                <form id="dealership-search-form">
                    <div>
                        <input type="text" id="dealership-suggest-input"
                            placeholder="Your 6 digit Pincode or the name of your district" />
                    </div>
                    <button id="get-current-location" type="button">Current Location</button>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
// Add debug information to help diagnose the problem
console.log('Dealership count from PHP: <?php echo $dealershipCount; ?>');
console.log('Dealership JSON from PHP: <?php echo htmlspecialchars($dealershipsJson); ?>');

// Pass PHP dealership data to JavaScript - using our PHP variable
// Add debug info
console.log('Dealership count from PHP: <?php echo $dealershipCount; ?>');
console.log('Raw dealership count from database: <?php echo $rawCount; ?>');
console.log('Dealership JSON from PHP: <?php echo $dealershipsJson; ?>');
console.log('Raw Dealership JSON: <?php echo $rawDealershipsJson; ?>');

// Log the number of dealerships being passed to JavaScript
console.log('PHP is providing <?php echo $dealershipCount; ?> dealerships');
window.dealershipData = <?php echo $dealershipsJson ?: '[]'; ?>;

// Add debug timestamp to identify page load in console
console.log('🕒 Dealership map page loaded at: ' + new Date().toISOString());

// Force console to always show 
if (typeof console._log === 'undefined') {
    console._log = console.log;
    console.log = function() {
        console._log.apply(console, arguments);
        // You could also send logs to a server here
    };
}

// Check if the map script is actually loaded
console.log('DealershipMap in global scope:', typeof window.DealershipMap);

// Add debugging for DOM elements
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for map elements');
    
    // Check if our instance exists after DOM is loaded
    console.log('DealershipMap instance exists:', !!window.dealershipMapInstance);
    
    // Check for map container
    const mapContainer = document.getElementById('dealership-map');
    console.log('Map container found:', mapContainer);
    
    // Check for search input
    const searchInput = document.getElementById('dealership-suggest-input');
    console.log('Search input found:', searchInput);
    
    // Check for current location button
    const locationButton = document.getElementById('get-current-location');
    console.log('Location button found:', locationButton);
    
    // Verify Google Maps script
    const googleScript = document.querySelector('script[src*="maps.googleapis.com"]');
    console.log('Google Maps script found:', googleScript);
    console.log('Google Maps script defer attribute:', googleScript?.getAttribute('defer'));
    
    // Check for Google Maps API
    console.log('Google Maps API available:', typeof google !== 'undefined' && typeof google.maps !== 'undefined');
    console.log('Google Maps Places API available:', typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined');
});

// Add event delegation for InfoWindow "View Details" links
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('view-details-link')) {
        e.preventDefault();
        
        // Get dealership ID from data attribute
        const dealershipId = e.target.getAttribute('data-dealership-id');
        
        // Find dealership by ID in the data array
        if (dealershipId && window.dealershipMapInstance) {
            const dealership = window.dealershipData.find(d => d.id == dealershipId);
            if (dealership) {
                window.dealershipMapInstance.selectDealership(dealership);
            }
        }
    }
});
</script>

<?php endLayout(); ?>
