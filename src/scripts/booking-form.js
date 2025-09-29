/**
 * =======================
 * Booking Form Module
 * =======================
 * Handles form validation, pincode checking, and button state management
 * for both choose variant and booking payment forms
 */
const BookingFormHandler = {
    init: function() {
        this.initChooseVariantForm();
        this.initBookingPaymentForm();
    },

    /**
     * Initialize Choose Variant Form (when no variant/color in URL)
     */
    initChooseVariantForm: function() {
        const variantSelect = document.getElementById('variant');
        const colorOptions = document.querySelector('.color-options');
        const bookNowBtn = document.getElementById('book-now-btn');
        
        // Only run if we're on the choose variant screen
        if (!variantSelect || !colorOptions || !bookNowBtn) return;

        const colorLabels = colorOptions.querySelectorAll('label');
        const backgroundModel = document.querySelector('.background-model-image');

        // Function to check if both variant and color are selected
        const updateBookNowButton = () => {
            const selectedVariant = variantSelect.value;
            const selectedColor = document.querySelector('input[name="color"]:checked');
            
            // Enable button only if both variant and color are selected
            bookNowBtn.disabled = !(selectedVariant && selectedColor);
        };

        // Function to show/hide color options based on variant
        const updateColorOptions = (variant) => {
            if (variant === 'dx') {
                // For DX variant, show only gray and black
                colorLabels.forEach(label => {
                    const input = label.querySelector('input[name="color"]');
                    const colorValue = input.value;

                    if (colorValue === 'grey' || colorValue === 'black') {
                        label.style.display = 'inline-block';
                    } else {
                        label.style.display = 'none';
                        // Uncheck hidden colors
                        input.checked = false;
                    }
                });

                // Auto-select black color
                const blackInput = colorOptions.querySelector('input[value="black"]');
                if (blackInput) {
                    blackInput.checked = true;
                    // Update background image to black
                    if (backgroundModel) {
                        backgroundModel.setAttribute('href', '/-/images/new/black/000145.png');
                    }
                }
            } else if (variant === 'dx-plus') {
                // For DX+ variant, show all colors
                colorLabels.forEach(label => {
                    label.style.display = 'inline-block';
                });

                // Auto-select white color for DX+
                const whiteInput = colorOptions.querySelector('input[value="white"]');
                if (whiteInput) {
                    whiteInput.checked = true;
                    // Update background image to white
                    if (backgroundModel) {
                        backgroundModel.setAttribute('href', '/-/images/new/white/000145.png');
                    }
                }
            } else {
                // No variant selected, show all colors but don't auto-select
                colorLabels.forEach(label => {
                    label.style.display = 'inline-block';
                });
            }
            updateBookNowButton();
        };

        // Handle variant selection change
        variantSelect.addEventListener('change', function() {
            updateColorOptions(this.value);
        });

        // Handle color selection change (update background image)
        colorOptions.addEventListener('change', function(event) {
            if (event.target.name === 'color') {
                let selectedColor = event.target.value;

                // Handle 'grey' to 'gray' mapping for image path
                if (selectedColor === 'grey') {
                    selectedColor = 'gray';
                }

                if (backgroundModel) {
                    backgroundModel.setAttribute('href', `/-/images/new/${selectedColor}/000145.png`);
                }
                
                updateBookNowButton();
            }
        });

        // Initialize on page load
        updateColorOptions(variantSelect.value);
    },

    /**
     * Initialize Booking Payment Form (when variant/color in URL)
     */
    initBookingPaymentForm: function() {
        const payButton = document.querySelector('.pay-button');
        const termsCheckbox = document.querySelector('input[name="terms"]');
        const pincodeInput = document.querySelector('input[name="pincode"]');
        const cityInput = document.querySelector('input[name="city"]');
        const stateInput = document.querySelector('input[name="state"]');

        // Only run if we're on the booking payment screen
        if (!payButton || !termsCheckbox || !pincodeInput) return;

        // Set default restricted state for pincode validation immediately
        payButton.setAttribute('data-pincode-restricted', 'true');

        let isPhoneVerified = false;

        // Function to check phone verification status
        const checkPhoneVerification = () => {
            const form = payButton.closest('form');
            if (!form) return false;
            
            const phoneInput = form.querySelector('input[name="phone"]');
            if (!phoneInput) return false;
            
            // Check if form has phone verification attribute
            const verificationStatus = form.getAttribute('data-phone-verified');
            const verifiedPhone = form.getAttribute('data-verified-phone');
            
            // Sanitize both phone numbers for comparison
            const sanitizePhone = (phone) => {
                if (!phone) return '';
                const cleaned = phone.replace(/[^0-9]/g, '');
                if (/^91[6-9]\d{9}$/.test(cleaned)) {
                    return cleaned.substring(2);
                }
                if (/^[6-9]\d{9}$/.test(cleaned)) {
                    return cleaned;
                }
                return cleaned;
            };
            
            const currentPhone = sanitizePhone(phoneInput.value);
            const sanitizedVerifiedPhone = sanitizePhone(verifiedPhone);
            
            return verificationStatus === '1' && currentPhone === sanitizedVerifiedPhone;
        };

        // Function to check if pincode is allowed (from PincodeCityRestriction module)
        const isPincodeAllowed = () => {
            // Check if pincode validation has succeeded (attribute removed means validation passed)
            return !payButton.hasAttribute('data-pincode-restricted');
        };

        // Store the original button text immediately on initialization
        if (!payButton.getAttribute('data-booking-original-text')) {
            payButton.setAttribute('data-booking-original-text', payButton.textContent);
        }

        // Function to update pay button state with all three conditions
        const updatePayButtonState = () => {
            isPhoneVerified = checkPhoneVerification();
            const pincodeAllowed = isPincodeAllowed();
            
            // All three conditions must be met: phone verified, pincode allowed, terms checked
            const allConditionsMet = isPhoneVerified && pincodeAllowed && termsCheckbox.checked;
            payButton.disabled = !allConditionsMet;
            
            // Handle button text restoration when all conditions are met
            if (allConditionsMet) {
                const originalText = payButton.getAttribute('data-booking-original-text');
                if (originalText) {
                    // Restore original button text (PAY ₹1000/-)
                    payButton.textContent = originalText;
                    payButton.removeAttribute('title');
                } else {
                    // Fallback: ensure correct text if no stored original text
                    payButton.textContent = 'PAY ₹1000/-';
                }
            } else {
                // Set appropriate disabled text based on which condition is not met
                let reason = '';
                if (!isPhoneVerified) {
                    reason = 'Phone verification required';
                } else if (!pincodeAllowed) {
                    reason = 'Pincode validation required';
                } else if (!termsCheckbox.checked) {
                    reason = 'Accept terms and conditions';
                }
                
                payButton.textContent = reason;
                payButton.setAttribute('title', reason);
            }
            
            console.log('Pay button state:', {
                isPhoneVerified,
                pincodeAllowed,
                termsChecked: termsCheckbox.checked,
                allConditionsMet,
                buttonDisabled: payButton.disabled,
                buttonText: payButton.textContent
            });
        };

        // Terms checkbox handler
        termsCheckbox.addEventListener('change', updatePayButtonState);

        // Phone input handler - update verification status when phone changes
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', updatePayButtonState);
            phoneInput.addEventListener('change', updatePayButtonState);
        }

        // Listen for OTP verification events that might change phone verification status
        document.addEventListener('phoneVerified', updatePayButtonState);
        document.addEventListener('phoneVerificationChanged', updatePayButtonState);

        // Listen for pincode validation changes from PincodeCityRestriction module
        document.addEventListener('pincodeValidationChanged', updatePayButtonState);

        // Parse URL parameters and set initial values
        const params = new URLSearchParams(window.location.search);
        
        // Set variant radio if present
        const variant = params.get("variant");
        if (variant) {
            const variantInput = document.querySelector(`input[name="variant"][value="${variant}"]`);
            if (variantInput) {
                variantInput.checked = true;
            }
        }

        // Set color radio if present and update background
        let color = params.get("color");
        if (color) {
            const colorInput = document.querySelector(`input[name="color"][value="${color}"]`);
            if (colorInput) {
                colorInput.checked = true;
            }
            
            const backgroundModel = document.querySelector('.background-model-image');
            if (backgroundModel) {
                if (color === 'grey') {
                    color = 'gray'; // Handle 'grey' as 'gray' for image path
                }
                backgroundModel.setAttribute('href', `/-/images/new/${color}/000145.png`);
            }
        }

        // Initial pay button state (should be disabled)
        updatePayButtonState();
    }
};

// Export for use in other modules or direct initialization
export { BookingFormHandler };

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    BookingFormHandler.init();
});
