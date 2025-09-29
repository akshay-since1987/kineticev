/**
 * =======================
 * Meta Pixel Tracking Module
 * =======================
 * Handles Meta Pixel event tracking for form submissions and conversions
 */
const MetaPixelTracking = {
    
    /**
     * Initialize Meta Pixel tracking
     */
    init: function() {
        console.log('Meta Pixel Tracking initialized');
        this.setupFormTracking();
        this.setupPageSpecificTracking();
    },

    /**
     * Setup form tracking for all forms on the page
     */
    setupFormTracking: function() {
        // Book Now Form tracking
        this.setupBookNowTracking();
        
        // Test Ride Form tracking  
        this.setupTestRideTracking();
        
        // Contact Form tracking
        this.setupContactFormTracking();
        
        // Popup forms tracking
        this.setupPopupFormTracking();
    },

    /**
     * Setup page-specific tracking
     */
    setupPageSpecificTracking: function() {
        // Thank you page tracking
        if (window.location.pathname.includes('thank-you')) {
            this.trackPurchaseConversion();
        }
    },

    /**
     * Setup Book Now form tracking
     */
    setupBookNowTracking: function() {
        const bookNowForms = document.querySelectorAll('form[action*="process-payment"], .book-now-form, form.payment-form');
        
        bookNowForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.trackLead('BookNow', {
                    content_name: 'Book Now Form',
                    content_category: 'Vehicle Booking'
                });
                
                console.log('Meta Pixel: Book Now form submission tracked');
            });
        });
    },

    /**
     * Setup Test Ride form tracking
     */
    setupTestRideTracking: function() {
        // Main test ride forms
        const testRideForms = document.querySelectorAll('form[action*="submit-test-drive"], .test-ride-form, form.popup-form');
        
        testRideForms.forEach(form => {
            // Check if this is actually a test ride form by looking for test ride specific fields
            const hasTestRideFields = form.querySelector('input[name*="test"], input[name*="ride"]') || 
                                    form.closest('.test-drive-popup') ||
                                    form.querySelector('input[name="popup_test_submit"]');
            
            if (hasTestRideFields) {
                form.addEventListener('submit', (e) => {
                    this.trackLead('TestRide', {
                        content_name: 'Test Ride Request',
                        content_category: 'Test Drive'
                    });
                    
                    console.log('Meta Pixel: Test Ride form submission tracked');
                });
            }
        });

        // AJAX test ride form submissions
        this.setupAjaxTestRideTracking();
    },

    /**
     * Setup Contact form tracking
     */
    setupContactFormTracking: function() {
        const contactForms = document.querySelectorAll('form[action*="contact"], .contact-form, form#contact-form');
        
        contactForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.trackContact({
                    content_name: 'Contact Form',
                    content_category: 'Customer Support'
                });
                
                console.log('Meta Pixel: Contact form submission tracked');
            });
        });
    },

    /**
     * Setup popup form tracking
     */
    setupPopupFormTracking: function() {
        // Listen for dynamically created popup forms
        document.addEventListener('submit', (e) => {
            const form = e.target;
            
            // Check if it's a popup form
            if (form.classList.contains('popup-form') || form.closest('.popup')) {
                
                // Determine form type by checking submit button or form content
                const submitButton = form.querySelector('input[type="submit"], button[type="submit"]');
                const buttonText = submitButton ? submitButton.value || submitButton.textContent : '';
                
                if (buttonText.toLowerCase().includes('test') || buttonText.toLowerCase().includes('ride')) {
                    this.trackLead('TestRide', {
                        content_name: 'Popup Test Ride Form',
                        content_category: 'Test Drive'
                    });
                    console.log('Meta Pixel: Popup Test Ride form tracked');
                } else if (buttonText.toLowerCase().includes('call') || buttonText.toLowerCase().includes('contact')) {
                    this.trackLead('CallBack', {
                        content_name: 'Popup Callback Form', 
                        content_category: 'Lead Generation'
                    });
                    console.log('Meta Pixel: Popup Callback form tracked');
                }
            }
        });
    },

    /**
     * Setup AJAX test ride tracking
     */
    setupAjaxTestRideTracking: function() {
        // Override XMLHttpRequest to catch AJAX submissions
        const originalSend = XMLHttpRequest.prototype.send;
        const self = this;
        
        XMLHttpRequest.prototype.send = function(data) {
            this.addEventListener('load', function() {
                // Check if this was a test ride submission
                if (this.responseURL && this.responseURL.includes('submit-test-drive')) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Check if response includes Meta Pixel tracking data
                            if (response.meta_pixel_tracking) {
                                const trackingData = response.meta_pixel_tracking;
                                if (trackingData.event === 'Lead') {
                                    self.trackLead(trackingData.data.lead_type, trackingData.data);
                                    console.log('Meta Pixel: AJAX Test Ride submission tracked with server data');
                                }
                            } else {
                                // Fallback to basic tracking
                                self.trackLead('TestRide', {
                                    content_name: 'AJAX Test Ride Form',
                                    content_category: 'Test Drive'
                                });
                                console.log('Meta Pixel: AJAX Test Ride submission tracked (fallback)');
                            }
                        }
                    } catch (e) {
                        // Non-JSON response, still track if URL suggests success
                        if (this.status === 200) {
                            self.trackLead('TestRide', {
                                content_name: 'AJAX Test Ride Form',
                                content_category: 'Test Drive'
                            });
                        }
                    }
                }
            });
            
            originalSend.call(this, data);
        };
        
        // Also override fetch API for modern applications
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                return originalFetch.apply(this, args).then(response => {
                    if (response.url && response.url.includes('submit-test-drive') && response.ok) {
                        response.clone().json().then(data => {
                            if (data.success && data.meta_pixel_tracking) {
                                const trackingData = data.meta_pixel_tracking;
                                if (trackingData.event === 'Lead') {
                                    self.trackLead(trackingData.data.lead_type, trackingData.data);
                                    console.log('Meta Pixel: Fetch Test Ride submission tracked');
                                }
                            }
                        }).catch(() => {
                            // Ignore JSON parsing errors for non-JSON responses
                        });
                    }
                    return response;
                });
            };
        }
    },

    /**
     * Track Lead event
     */
    trackLead: function(leadType, customData = {}) {
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Lead', {
                content_name: customData.content_name || 'Lead Form',
                content_category: customData.content_category || 'Lead Generation',
                lead_type: leadType,
                source: 'website'
            });
        } else {
            console.warn('Meta Pixel: fbq not available for Lead tracking');
        }
    },

    /**
     * Track Contact event
     */
    trackContact: function(customData = {}) {
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Contact', {
                content_name: customData.content_name || 'Contact Form',
                content_category: customData.content_category || 'Customer Support',
                source: 'website'
            });
        } else {
            console.warn('Meta Pixel: fbq not available for Contact tracking');
        }
    },

    /**
     * Track Purchase/Conversion on thank you page
     */
    trackPurchaseConversion: function() {
        // Get transaction details from URL or page data
        const urlParams = new URLSearchParams(window.location.search);
        const txnId = urlParams.get('txnid');
        
        if (typeof fbq !== 'undefined') {
            const purchaseData = {
                content_name: 'Vehicle Booking',
                content_category: 'Vehicle Purchase',
                content_type: 'product',
                currency: 'INR'
            };
            
            // Add transaction ID if available
            if (txnId) {
                purchaseData.transaction_id = txnId;
            }
            
            // Try to get booking amount from page content
            const amountElement = document.querySelector('[data-booking-amount], .booking-amount');
            if (amountElement) {
                const amount = parseFloat(amountElement.textContent.replace(/[^\d.]/g, ''));
                if (!isNaN(amount)) {
                    purchaseData.value = amount;
                }
            }
            
            fbq('track', 'Purchase', purchaseData);
            
            // Also track as CompleteRegistration for booking completion
            fbq('track', 'CompleteRegistration', {
                content_name: 'Vehicle Booking Complete',
                status: 'completed'
            });
            
            console.log('Meta Pixel: Purchase conversion tracked', purchaseData);
        } else {
            console.warn('Meta Pixel: fbq not available for Purchase tracking');
        }
    },

    /**
     * Track custom events
     */
    trackCustomEvent: function(eventName, data = {}) {
        if (typeof fbq !== 'undefined') {
            fbq('trackCustom', eventName, data);
            console.log(`Meta Pixel: Custom event '${eventName}' tracked`, data);
        } else {
            console.warn(`Meta Pixel: fbq not available for custom event '${eventName}'`);
        }
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => MetaPixelTracking.init());
} else {
    MetaPixelTracking.init();
}

// Export for manual usage
window.MetaPixelTracking = MetaPixelTracking;
