<?php
// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}
?>
<!-- Load Google Maps API script synchronously to ensure it's available before our script runs -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw&libraries=places,geometry"></script>
<script>
// Verify Google Maps API loaded
console.log('Google Maps API status: ' + (typeof google !== 'undefined' && typeof google.maps !== 'undefined' ? 'LOADED' : 'FAILED'));
if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
    console.log('Available Google Maps libraries:', {
        places: typeof google.maps.places !== 'undefined',
        geometry: typeof google.maps.geometry !== 'undefined',
        drawing: typeof google.maps.drawing !== 'undefined'
    });
}
</script>
