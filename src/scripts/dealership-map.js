/**
 * =======================
 * Dealership Map Module
 * =======================
 */

// Define the DealershipMap class
class DealershipMap {
    constructor() {
        console.log('DealershipMap constructor called');
        
        // Initialize properties
        this.map = null;
        this.markers = [];
        this.infoWindow = null;
        this.autocomplete = null;
        this.dealershipData = window.dealershipData || [];
        this.mapElement = document.getElementById('dealership-map');
        this.searchInput = null;
        
        // Check if we're on the right page
        if (!this.mapElement) {
            console.log('No dealership map found, skipping initialization');
            return;
        }
        
        // Initialize when ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this.init.bind(this));
        } else {
            this.init();
        }
    }
    
    init() {
        console.log('Initializing dealership map...');
        
        // Find the search input
        this.searchInput = document.getElementById('dealership-suggest-input');
        
        // Initialize location form in visible state - with delay to ensure DOM is ready
        setTimeout(() => {
            this.showLocationForm();
        }, 100);
        
        // Set up Google Maps when API is loaded
        if (window.google && window.google.maps) {
            this.setupMap();
        } else {
            this.waitForGoogleMaps();
        }
    }
    
    waitForGoogleMaps() {
        console.log('Waiting for Google Maps to load...');
        
        // Check if script exists
        const mapsScript = document.querySelector('script[src*="maps.googleapis.com"]');
        if (!mapsScript) {
            console.log('No Google Maps script found, adding one');
            this.loadGoogleMapsScript();
            return;
        }
        
        // Set up polling to wait for Google Maps
        let attempts = 0;
        const checkMaps = setInterval(() => {
            attempts++;
            
            if (window.google && window.google.maps) {
                clearInterval(checkMaps);
                console.log('Google Maps loaded');
                this.setupMap();
                return;
            }
            
            if (attempts > 50) {
                clearInterval(checkMaps);
                console.error('Google Maps failed to load after waiting');
            }
        }, 200);
    }
    
    loadGoogleMapsScript() {
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw&libraries=places,geometry';
        script.onload = () => {
            console.log('Google Maps script loaded');
            this.setupMap();
        };
        document.head.appendChild(script);
    }
    
    setupMap() {
        if (!this.mapElement || !window.google || !window.google.maps) {
            console.error('Cannot setup map - missing element or Google Maps');
            return;
        }
        
        try {
            // Create map
            this.map = new google.maps.Map(this.mapElement, {
                center: { lat: 28.6139, lng: 77.209 }, // New Delhi
                zoom: 10
            });
            
            // Set up autocomplete if search input exists
            if (this.searchInput && window.google.maps.places) {
                this.setupAutocomplete();
            }
            
            // Add markers for dealerships
            this.addDealershipMarkers();
            
            // Set up event listeners
            this.setupEventListeners();
            
            console.log('Map initialized successfully');
        } catch (error) {
            console.error('Error setting up map:', error);
        }
    }
    
    setupAutocomplete() {
        try {
            this.autocomplete = new google.maps.places.Autocomplete(this.searchInput, {
                types: ['geocode'],
                componentRestrictions: { country: 'in' }
            });
            
            this.autocomplete.addListener('place_changed', () => {
                const place = this.autocomplete.getPlace();
                if (place.geometry) {
                    this.map.setCenter(place.geometry.location);
                    this.map.setZoom(12);
                    
                    // Find and show the nearest dealership
                    this.findAndShowNearestDealership(place.geometry.location);
                }
            });
            
            console.log('Autocomplete setup complete');
        } catch (error) {
            console.error('Error setting up autocomplete:', error);
        }
    }
    
    addDealershipMarkers() {
        if (!this.dealershipData || this.dealershipData.length === 0) {
            console.warn('No dealership data available');
            return;
        }
        
        console.log(`Adding ${this.dealershipData.length} dealership markers`);
        
        const bounds = new google.maps.LatLngBounds();
        
        this.dealershipData.forEach(dealership => {
            // Get coordinates
            const lat = dealership.lat || dealership.latitude;
            const lng = dealership.lng || dealership.longitude;
            
            if (!lat || !lng) return;
            
            const position = new google.maps.LatLng(
                parseFloat(lat), 
                parseFloat(lng)
            );
            
            // Create marker
            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: dealership.name
            });
            
            // Add click listener to show info window
            marker.addListener('click', () => {
                this.showDealershipInfo(marker, dealership);
            });
            
            bounds.extend(position);
            this.markers.push(marker);
        });
        
        // Fit map to show all markers
        if (this.markers.length > 0) {
            this.map.fitBounds(bounds);
        }
    }
    
    setupEventListeners() {
        // Set up current location button
        const locationButton = document.getElementById('get-current-location');
        if (locationButton) {
            locationButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.getUserLocation();
            });
        }
        
        // Set up form submission prevention
        const searchForm = document.getElementById('dealership-search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('Form submission prevented');
                this.handleFormSubmit();
            });
        }
        
        // Set up Enter key handling for search input
        if (this.searchInput) {
            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('Enter key pressed, selecting first autocomplete suggestion');
                    this.selectFirstAutocompleteSuggestion();
                }
            });
        }
    }
    
    /**
     * Handle form submission by processing the current input value
     */
    handleFormSubmit() {
        if (this.searchInput && this.searchInput.value.trim()) {
            const inputValue = this.searchInput.value.trim();
            console.log('Processing search for:', inputValue);
            
            // Try to trigger autocomplete selection or search directly
            this.searchLocation(inputValue);
        }
    }
    
    /**
     * Select the first autocomplete suggestion when Enter is pressed
     */
    selectFirstAutocompleteSuggestion() {
        // Try to trigger the first autocomplete prediction
        if (this.autocomplete) {
            // Get the first prediction from Google Places
            const service = new google.maps.places.AutocompleteService();
            const request = {
                input: this.searchInput.value,
                types: ['geocode'],
                componentRestrictions: { country: 'in' }
            };
            
            service.getPlacePredictions(request, (predictions, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK && predictions && predictions.length > 0) {
                    console.log('Found predictions, selecting first:', predictions[0].description);
                    
                    // Create a PlaceService to get details for the first prediction
                    const placesService = new google.maps.places.PlacesService(this.map);
                    
                    placesService.getDetails({
                        placeId: predictions[0].place_id
                    }, (place, status) => {
                        if (status === google.maps.places.PlacesServiceStatus.OK && place.geometry) {
                            console.log('Got place details:', place.name);
                            
                            // Update the input with the selected place
                            this.searchInput.value = place.formatted_address || place.name;
                            
                            // Center map and find nearest dealership
                            this.map.setCenter(place.geometry.location);
                            this.map.setZoom(12);
                            this.findAndShowNearestDealership(place.geometry.location);
                        }
                    });
                } else {
                    // Fallback: try direct geocoding
                    console.log('No predictions found, trying direct geocoding');
                    this.searchLocation(this.searchInput.value);
                }
            });
        } else {
            // Fallback if autocomplete isn't available
            this.searchLocation(this.searchInput.value);
        }
    }
    
    /**
     * Search for a location using Google Geocoder
     */
    searchLocation(query) {
        const geocoder = new google.maps.Geocoder();
        
        geocoder.geocode({
            address: query,
            componentRestrictions: { country: 'IN' }
        }, (results, status) => {
            if (status === google.maps.GeocoderStatus.OK && results && results.length > 0) {
                const location = results[0].geometry.location;
                console.log('Geocoded location:', results[0].formatted_address);
                
                // Update input with formatted address
                this.searchInput.value = results[0].formatted_address;
                
                // Center map and find nearest dealership
                this.map.setCenter(location);
                this.map.setZoom(12);
                this.findAndShowNearestDealership(location);
            } else {
                console.warn('Geocoding failed:', status);
                alert('Location not found. Please try a different search term.');
            }
        });
    }
    
    getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const location = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    this.map.setCenter(location);
                    this.map.setZoom(13);
                    
                    // Add marker for user location
                    new google.maps.Marker({
                        position: location,
                        map: this.map,
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + 
                                 encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#4285F4" stroke="#ffffff" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg>'),
                            scaledSize: new google.maps.Size(16, 16)
                        }
                    });
                },
                (error) => {
                    console.error('Error getting location:', error);
                    alert('Could not get your location. Please try again or enter your location manually.');
                }
            );
        } else {
            alert('Location services not available in your browser.');
        }
    }
    
    /**
     * Find and show the nearest dealership to a given location
     */
    findAndShowNearestDealership(location) {
        if (!this.dealershipData || this.dealershipData.length === 0) {
            console.warn('No dealership data available for nearest search');
            return;
        }
        
        let nearestDealership = null;
        let shortestDistance = Infinity;
        let nearestMarker = null;
        
        // Find the nearest dealership
        this.dealershipData.forEach((dealership, index) => {
            const dealershipLat = parseFloat(dealership.lat || dealership.latitude);
            const dealershipLng = parseFloat(dealership.lng || dealership.longitude);
            
            if (dealershipLat && dealershipLng) {
                const distance = this.calculateDistance(
                    location.lat(), 
                    location.lng(), 
                    dealershipLat, 
                    dealershipLng
                );
                
                if (distance < shortestDistance) {
                    shortestDistance = distance;
                    nearestDealership = dealership;
                    nearestMarker = this.markers[index];
                }
            }
        });
        
        if (nearestDealership && nearestMarker) {
            console.log(`Found nearest dealership: ${nearestDealership.name} at ${shortestDistance.toFixed(2)} km`);
            
            // Center map between selected location and nearest dealership
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(location);
            bounds.extend(nearestMarker.getPosition());
            this.map.fitBounds(bounds);
            
            // Show info window for nearest dealership
            this.showDealershipInfo(nearestMarker, nearestDealership);
        }
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in kilometers
        const dLat = this.toRadians(lat2 - lat1);
        const dLng = this.toRadians(lng2 - lng1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
                  
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
    
    /**
     * Convert degrees to radians
     */
    toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }
    
    /**
     * Show dealership information in info window
     */
    showDealershipInfo(marker, dealership) {
        // Get coordinates for directions link
        const lat = dealership.lat || dealership.latitude;
        const lng = dealership.lng || dealership.longitude;
        const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        
        // Format phone number for links (remove spaces and special characters)
        const phoneNumber = dealership.phone ? dealership.phone.replace(/[\s\-\(\)]/g, '') : '';
        const whatsappUrl = phoneNumber ? `https://wa.me/91${phoneNumber.replace(/^\+?91/, '')}` : '';
        const telUrl = phoneNumber ? `tel:+91${phoneNumber.replace(/^\+?91/, '')}` : '';
        
        const content = `
            <div class="dealership-info-window">
                <h3>${dealership.name}</h3>
                <div class="info-content">
                    <div class="info-item">
                        <span class="label">Address:</span>
                        <span class="value">${dealership.address || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Phone:</span>
                        <span class="value">
                            ${dealership.phone ? `
                                <div class="contact-links">
                                    <span class="phone-number">${dealership.phone}</span>
                                    <div class="contact-actions">
                                        <a href="${telUrl}" class="contact-link tel-link" title="Call ${dealership.phone}">
                                            📞 Call
                                        </a>
                                        <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" class="contact-link whatsapp-link" title="WhatsApp ${dealership.phone}">
                                            💬 WhatsApp
                                        </a>
                                    </div>
                                </div>
                            ` : 'N/A'}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value">${dealership.email ? `<a href="mailto:${dealership.email}" class="email-link">${dealership.email}</a>` : 'N/A'}</span>
                    </div>
                    ${dealership.district ? `
                    <div class="info-item">
                        <span class="label">District:</span>
                        <span class="value">${dealership.district}</span>
                    </div>
                    ` : ''}
                    ${dealership.pincode ? `
                    <div class="info-item">
                        <span class="label">Pincode:</span>
                        <span class="value">${dealership.pincode}</span>
                    </div>
                    ` : ''}
                </div>
                <div class="dealership-actions">
                    <a href="${directionsUrl}" target="_blank" rel="noopener noreferrer" class="get-directions-link">
                        📍 Get Directions
                    </a>
                </div>
            </div>
        `;
        
        if (!this.infoWindow) {
            this.infoWindow = new google.maps.InfoWindow();
            
            // Add event listener for InfoWindow close (X button)
            this.infoWindow.addListener('closeclick', () => {
                console.log('InfoWindow closed via closeclick');
                this.showLocationForm();
            });
            
            // Add event listener for when InfoWindow closes by other means
            this.infoWindow.addListener('close', () => {
                console.log('InfoWindow closed via close event');
                this.showLocationForm();
            });
        }
        
        // Hide location form after 1 second when InfoWindow opens
        console.log('InfoWindow opened, location form will hide in 1 second');
        setTimeout(() => {
            console.log('1 second elapsed, hiding location form');
            this.hideLocationForm();
        }, 1000);
        
        this.infoWindow.setContent(content);
        this.infoWindow.open(this.map, marker);
        
        // Add a one-time map click listener for this InfoWindow instance
        const mapClickListener = this.map.addListener('click', (event) => {
            // Check if the click was on the InfoWindow or map
            console.log('Map clicked, closing InfoWindow');
            this.infoWindow.close();
            google.maps.event.removeListener(mapClickListener);
        });
        
        // Animate the marker
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(() => {
            marker.setAnimation(null);
        }, 2000);
    }
    
    /**
     * Hide the location form with slide-up animation
     */
    hideLocationForm() {
        const locationForm = document.querySelector('.location-form');
        if (locationForm) {
            console.log('Hiding location form');
            locationForm.classList.remove('visible');
            locationForm.classList.add('hidden');
        } else {
            console.warn('Location form element not found for hiding');
        }
    }
    
    /**
     * Show the location form with slide-down animation
     */
    showLocationForm() {
        const locationForm = document.querySelector('.location-form');
        if (locationForm) {
            console.log('Showing location form');
            locationForm.classList.remove('hidden');
            locationForm.classList.add('visible');
        } else {
            console.warn('Location form element not found for showing');
        }
    }
}

// Make available globally
window.DealershipMap = DealershipMap;

// Export for ES modules
export default DealershipMap;
