/**
 * =======================
 * Dealership Finder Module
 * =======================
 * Handles dealership finder functionality and Google Maps integration
 */
const DealershipFinder = {
    map: null,
    markers: [],
    infoWindow: null,
    bounds: null,
    
    /**
     * Initialize the dealership finder
     */
    init: function() {
        // Only run on dealership finder page
        if (!document.querySelector('#map-view')) return;
        
        // Set up view toggle buttons
        this.setupViewToggle();
        
        // Initialize map if dealership search was performed and we have dealerships
        if (document.querySelector('.dealership-card')) {
            this.initMap();
        }
    },
    
    /**
     * Set up toggle between list and map views
     */
    setupViewToggle: function() {
        const listViewBtn = document.getElementById('list-view-btn');
        const mapViewBtn = document.getElementById('map-view-btn');
        const listView = document.getElementById('list-view');
        const mapView = document.getElementById('map-view');
        
        if (!listViewBtn || !mapViewBtn || !listView || !mapView) return;
        
        // List view button click
        listViewBtn.addEventListener('click', () => {
            listView.style.display = 'block';
            mapView.style.display = 'none';
            listViewBtn.classList.add('active');
            mapViewBtn.classList.remove('active');
        });
        
        // Map view button click
        mapViewBtn.addEventListener('click', () => {
            listView.style.display = 'none';
            mapView.style.display = 'block';
            mapViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
            
            // Refresh map to fix display issues
            if (this.map) {
                google.maps.event.trigger(this.map, 'resize');
                if (this.bounds) {
                    this.map.fitBounds(this.bounds);
                }
            }
        });
    },
    
    /**
     * Initialize Google Maps
     */
    initMap: function() {
        // Map configuration
        const mapOptions = {
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        };
        
        // Create the map, bounds and info window
        this.map = new google.maps.Map(document.getElementById("map-view"), mapOptions);
        this.bounds = new google.maps.LatLngBounds();
        this.infoWindow = new google.maps.InfoWindow();
        
        // Add markers for dealerships
        this.addDealershipMarkers();
    },
    
    /**
     * Add markers for each dealership
     */
    addDealershipMarkers: function() {
        const dealershipCards = document.querySelectorAll('.dealership-card');
        
        dealershipCards.forEach(card => {
            const lat = parseFloat(card.dataset.lat);
            const lng = parseFloat(card.dataset.lng);
            const name = card.dataset.name;
            
            if (lat && lng) {
                const position = { lat, lng };
                this.bounds.extend(position);
                
                // Create marker
                const marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: name,
                    animation: google.maps.Animation.DROP,
                    icon: {
                        url: '/-/images/icons/dealership-marker.png',
                        scaledSize: new google.maps.Size(40, 40)
                    }
                });
                
                // Add click listener for info window
                marker.addListener('click', () => {
                    const content = card.innerHTML;
                    this.infoWindow.setContent('<div class="map-info-window">' + content + '</div>');
                    this.infoWindow.open(this.map, marker);
                });
                
                this.markers.push(marker);
            }
        });
        
        // Fit map to bounds and adjust zoom level
        if (this.markers.length > 0) {
            this.map.fitBounds(this.bounds);
            
            // Don't zoom in too far on only one marker
            google.maps.event.addListenerOnce(this.map, 'idle', () => {
                if (this.map.getZoom() > 15) {
                    this.map.setZoom(15);
                }
            });
        }
    },
    
    /**
     * Load Google Maps API script
     * This should be called from the page that needs the map
     */
    loadGoogleMapsAPI: function(apiKey) {
        // Only load if not already loaded
        if (window.google && window.google.maps) return;
        
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=DealershipFinder.onGoogleMapsLoaded`;
        script.async = true;
        script.defer = true;
        document.body.appendChild(script);
    },
    
    /**
     * Callback when Google Maps API is loaded
     */
    onGoogleMapsLoaded: function() {
        DealershipFinder.initMap();
    }
};

// Make DealershipFinder available globally
window.DealershipFinder = DealershipFinder;

// Export the module for ES imports
export default DealershipFinder;
