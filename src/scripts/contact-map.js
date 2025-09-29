/**
 * Contact Page Map Module
 * Renders Google Maps with company location and info window
 */

class ContactMap {
    constructor() {
        this.map = null;
        this.marker = null;
        this.mapElement = document.querySelector('.map');

        if (!this.mapElement) {
            console.log('No contact map container found');
            return;
        }

        // Company location coordinates for "MIDC, Chinchwad, Pimpri-Chinchwad, Maharashtra 411019"
        this.companyLocation = {
            lat: 18.6298,
            lng: 73.7997
        };

        this.init();
    }

    init() {
        // Wait for Google Maps to be available
        if (window.google && window.google.maps) {
            this.setupMap();
        } else {
            this.waitForGoogleMaps();
        }
    }

    waitForGoogleMaps() {
        let attempts = 0;
        const checkMaps = setInterval(() => {
            attempts++;

            if (window.google && window.google.maps) {
                clearInterval(checkMaps);
                console.log('Google Maps loaded for contact page');
                this.setupMap();
                return;
            }

            if (attempts > 50) {
                clearInterval(checkMaps);
                console.error('Google Maps failed to load for contact map');
            }
        }, 200);
    }

    setupMap() {
        try {
            // Create map centered on company location
            this.map = new google.maps.Map(this.mapElement, {
                center: this.companyLocation,
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Create marker with company info
            this.marker = new google.maps.Marker({
                position: this.companyLocation,
                map: this.map,
                title: 'Kinetic Watts and Volts Ltd.',
                animation: google.maps.Animation.DROP
            });

            // Create info window with company details
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 10px; max-width: 250px;">
                        <h3 style="margin: 0 0 8px 0; color: #333; font-size: 16px;">Kinetic Watts and Volts Ltd.</h3>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 14px; line-height: 1.4;">
                            D1 Block, Plot No 18/2,<br>
                            MIDC, Chinchwad,<br>
                            Pimpri-Chinchwad, Maharashtra 411019<br>
                            <a href="tel:+918600096800">8600096800</a><br>
                            <a class="view-map" href="https://www.google.com/maps?q=18.6298,73.7997" target="_blank">View on Google Maps</a>
                        </p>
                    </div>
                `
            });

            // Show info window on marker click
            this.marker.addListener('click', () => {
                infoWindow.open(this.map, this.marker);
            });

            // Show info window by default
            infoWindow.open(this.map, this.marker);

            console.log('Contact map initialized successfully');

        } catch (error) {
            console.error('Error setting up contact map:', error);
        }
    }
}

// Make ContactMap available globally
window.ContactMap = ContactMap;
