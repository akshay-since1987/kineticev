// @ts-nocheck
/**
 * =======================
 * OTP Verification Module
 * =======================
 * Handles mobile number OTP verification for forms
 */
const OtpVerification = {
    
    /**
     * Initialize OTP verification for all forms
     */
    init: function() {
        this.setupOtpVerification();
        console.log('OTP Verification initialized');
    },

    /**
     * Setup OTP verification for forms with phone fields
     */
    setupOtpVerification: function() {
        // Find all forms with phone fields that need OTP verification
        const formsWithPhone = document.querySelectorAll('form');
        
        formsWithPhone.forEach(form => {
            const phoneField = form.querySelector('input[type="tel"], input[name="phone"], input[name="test_phone"]');
            if (phoneField) {
                this.setupOtpForForm(form, phoneField);
            }
        });
    },

    /**
     * Setup OTP verification for a specific form
     */
    setupOtpForForm: function(form, phoneField) {
        // Determine form purpose
        const purpose = this.getFormPurpose(form);
        if (!purpose) return;

        // Store original submit button (needed for both skip and normal flow)
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

        // Check if OTP verification should be skipped for this form
        if (this.shouldSkipOtpVerification(form, purpose)) {
            console.log('OTP Verification skipped - phone already verified');
            // Mark form as verified and allow submission
            form.setAttribute('data-phone-verified', '1');
            
            // Set verified phone number to current phone value
            const phoneValue = phoneField.value.replace(/[^0-9]/g, '');
            form.setAttribute('data-verified-phone', phoneValue);
            
            // Handle button enabling based on form type
            if (submitButton) {
                if (purpose === 'booking_form') {
                    console.log('Booking form with skipped OTP - dispatching phoneVerified event');
                    // For booking forms, dispatch event instead of directly enabling button
                    document.dispatchEvent(new CustomEvent('phoneVerified', {
                        detail: { form: form, phoneField: phoneField }
                    }));
                } else {
                    // For other forms, directly enable the submit button
                    this.enableSubmitButton(submitButton);
                }
            }
            
            // Add listener to update verified phone if user changes phone number
            phoneField.addEventListener('input', function() {
                const newPhoneValue = phoneField.value.replace(/[^0-9]/g, '');
                form.setAttribute('data-verified-phone', newPhoneValue);
            });
            
            return;
        }

        // For normal OTP flow, check if submit button exists
        if (!submitButton) {
            console.warn('No submit button found in form:', form);
            return;
        }
        
        console.log('Submit button found during setup:', submitButton);
        
        // Create OTP container
        const otpContainer = this.createOtpContainer(phoneField, purpose);
        
        // Insert OTP container after phone field
        const phoneGroup = phoneField.closest('.form-group') || phoneField.parentNode;
        phoneGroup.parentNode.insertBefore(otpContainer, phoneGroup.nextSibling);

        // Add phone field validation
        phoneField.addEventListener('input', (e) => {
            this.handlePhoneInput(e.target, otpContainer, purpose);
        });

        phoneField.addEventListener('blur', (e) => {
            this.handlePhoneBlur(e.target, otpContainer, purpose);
        });

        // Store references
        form.otpContainer = otpContainer;
        form.otpPurpose = purpose;
        form.phoneField = phoneField;
        form.originalSubmitButton = submitButton;
        
        console.log('Stored form.originalSubmitButton:', form.originalSubmitButton);

        // Check if phone is already verified (e.g., from previous session)
        if (this.isPhoneVerified(form)) {
            // Phone already verified, enable submit and make phone readonly
            if (purpose === 'booking_form') {
                // For booking forms, dispatch event instead of directly enabling button
                document.dispatchEvent(new CustomEvent('phoneVerified', {
                    detail: { form: form, phoneField: phoneField }
                }));
            } else {
                // For other forms, directly enable the submit button
                this.enableSubmitButton(submitButton);
            }
            phoneField.setAttribute('readonly', 'readonly');
            phoneField.classList.add('verified-readonly');
            phoneField.style.backgroundColor = '#f8f9fa';
            phoneField.style.borderColor = '#28a745';
            phoneField.title = 'Phone number verified and locked';
            this.hideOtpContainer(otpContainer);
        } else {
            // Disable submit button initially
            this.disableSubmitButton(submitButton, 'Phone verification required');
        }

        // Handle form submission
        form.addEventListener('submit', (e) => {
            if (!this.isPhoneVerified(form)) {
                e.preventDefault();
                this.showError(otpContainer, 'Please verify your phone number before submitting');
                return false;
            }
        });
    },

    /**
     * Get form purpose based on form attributes
     */
    getFormPurpose: function(form) {
        // Check form action or class to determine purpose
        const action = form.getAttribute('action') || '';
        const className = form.className || '';
        
        if (action.includes('test-drive') || action.includes('submit-test-drive') || 
            className.includes('test') || form.id.includes('test')) {
            return 'test_ride';
        } else if (action.includes('process-payment') || action.includes('book') || 
                   className.includes('book') || className.includes('payment')) {
            return 'booking_form';
        } else if (action.includes('contact') || className.includes('contact') || 
                   form.closest('.contact-form')) {
            return 'contact_form';
        }
        
        // Default to contact_form for unknown forms with phone fields
        return 'contact_form';
    },

    /**
     * Check if OTP verification should be skipped for this form
     */
    shouldSkipOtpVerification: function(form, purpose) {
        // Only skip for contact forms
        if (purpose !== 'contact_form') {
            return false;
        }
        
        // Check form attribute for skip flag
        if (form.getAttribute('data-skip-otp') === 'true') {
            console.log('Phone verification skipped - form marked to skip OTP');
            return true;
        }
        
        // Check URL parameters for verified=1
        const urlParams = new URLSearchParams(window.location.search);
        const verified = urlParams.get('verified');
        
        // Skip OTP if phone is already verified from booking process
        if (verified === '1') {
            console.log('Phone verification skipped - already verified from booking process');
            return true;
        }
        
        return false;
    },

    /**
     * Create OTP verification container
     */
    createOtpContainer: function(phoneField, purpose) {
        const container = document.createElement('div');
        container.className = 'otp-verification-container';
        container.style.display = 'none';
        
        const timestamp = Date.now();
        
        container.innerHTML = `
            <div class="form-group otp-form-group">
                <label for="otp-input-${timestamp}-0">Verify your number</label>
                <div class="otp-input-wrapper">
                    <div class="otp-inputs">
                        <input type="text" class="otp-digit" maxlength="1" data-index="0" id="otp-input-${timestamp}-0">
                        <input type="text" class="otp-digit" maxlength="1" data-index="1" id="otp-input-${timestamp}-1">
                        <input type="text" class="otp-digit" maxlength="1" data-index="2" id="otp-input-${timestamp}-2">
                        <input type="text" class="otp-digit" maxlength="1" data-index="3" id="otp-input-${timestamp}-3">
                        <input type="text" class="otp-digit" maxlength="1" data-index="4" id="otp-input-${timestamp}-4">
                        <input type="text" class="otp-digit" maxlength="1" data-index="5" id="otp-input-${timestamp}-5">
                    </div>
                    <button type="button" class="resend-otp-btn" disabled>
                        Resend OTP (<span class="countdown">60</span>s)
                    </button>
                </div>
                <div class="otp-status"></div>
                <div class="error-message"></div>
            </div>
        `;

        // Setup OTP input behavior
        this.setupOtpInputs(container);
        
        // Setup resend button
        this.setupResendButton(container, phoneField, purpose);

        return container;
    },

    /**
     * Setup OTP input field behavior
     */
    setupOtpInputs: function(container) {
        const otpInputs = container.querySelectorAll('.otp-digit');
        
        otpInputs.forEach((input, index) => {
            // Only allow numbers
            input.addEventListener('input', (e) => {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                
                // Auto-focus next input
                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                // Auto-verify when all digits are filled
                if (this.isOtpComplete(container)) {
                    console.log("Auto-verifying complete OTP");
                    // Small delay to prevent rapid duplicate calls
                    setTimeout(() => {
                        this.verifyOtp(container);
                    }, 100);
                }
            });

            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Handle paste - enhanced to fill entire OTP
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                
                // If pasted data is 6 digits, fill all fields from the beginning
                if (pastedData.length === 6) {
                    otpInputs.forEach((otpInput, otpIndex) => {
                        otpInput.value = pastedData[otpIndex] || '';
                    });
                    
                    // Focus the last field and verify
                    otpInputs[5].focus();
                    if (this.isOtpComplete(container)) {
                        // Delay to prevent duplicate calls
                        setTimeout(() => {
                            this.verifyOtp(container);
                        }, 150);
                    }
                } else {
                    // For partial paste, fill from current position
                    for (let i = 0; i < Math.min(pastedData.length, otpInputs.length - index); i++) {
                        if (otpInputs[index + i]) {
                            otpInputs[index + i].value = pastedData[i];
                        }
                    }
                    
                    // Focus the next empty input or verify if complete
                    const nextEmptyIndex = index + pastedData.length;
                    if (nextEmptyIndex < otpInputs.length) {
                        otpInputs[nextEmptyIndex].focus();
                    } else if (this.isOtpComplete(container)) {
                        // Delay to prevent duplicate calls
                        setTimeout(() => {
                            this.verifyOtp(container);
                        }, 150);
                    }
                }
            });
        });
    },

    /**
     * Setup resend OTP button
     */
    setupResendButton: function(container, phoneField, purpose) {
        const resendBtn = container.querySelector('.resend-otp-btn');
        
        resendBtn.addEventListener('click', () => {
            const cleanPhone = phoneField.value.replace(/[^0-9]/g, '');
            this.generateOtp(phoneField.value, purpose, container, true); // Force new OTP
            // Update tracking to ensure we know an OTP was sent for this number
            phoneField.setAttribute('data-last-phone', cleanPhone);
            container.setAttribute('data-otp-phone', cleanPhone);
        });
    },

    /**
     * Handle phone input
     */
    handlePhoneInput: function(phoneField, otpContainer, purpose) {
        const form = phoneField.closest('form');
        const phone = phoneField.value.trim();
        const cleanPhone = phone.replace(/[^0-9]/g, '');
        
        // Check if current phone matches previously verified phone
        const verifiedPhone = form.getAttribute('data-verified-phone');
        const isCurrentPhoneVerified = verifiedPhone && verifiedPhone === cleanPhone;
        
        if (isCurrentPhoneVerified) {
            // Phone matches previously verified number - restore verification state
            form.setAttribute('data-phone-verified', '1');
            this.hideOtpContainer(otpContainer);
            
            // Check if this is a booking form
            const formPurpose = this.getFormPurpose(form);
            if (formPurpose === 'booking_form') {
                // For booking forms, dispatch event instead of directly enabling button
                document.dispatchEvent(new CustomEvent('phoneVerified', {
                    detail: { form: form, phoneField: phoneField }
                }));
            } else {
                // For other forms, directly enable the submit button
                this.enableSubmitButton(form.originalSubmitButton);
            }
            
            // Make phone field readonly again
            phoneField.setAttribute('readonly', 'readonly');
            phoneField.classList.add('verified-readonly');
            phoneField.style.backgroundColor = '#f8f9fa';
            phoneField.style.borderColor = '#28a745';
            phoneField.title = 'Phone number verified and locked';
            return;
        }
        
        // Phone is different from verified number - reset verification state
        if (form.getAttribute('data-phone-verified') === '1') {
            form.setAttribute('data-phone-verified', '0');
            this.disableSubmitButton(form.originalSubmitButton, 'Phone verification required');
            
            // Make phone field editable again
            phoneField.removeAttribute('readonly');
            phoneField.classList.remove('verified-readonly');
            phoneField.style.backgroundColor = '';
            phoneField.style.borderColor = '';
            phoneField.title = '';
        }
        
        // Hide OTP container if phone is not valid
        if (cleanPhone.length < 10) {
            this.hideOtpContainer(otpContainer);
            return;
        }
        
        // Show OTP container and generate OTP for valid phone numbers
        if (cleanPhone.length >= 10) {
            const lastPhone = phoneField.getAttribute('data-last-phone');
            const lastOtpPhone = otpContainer.getAttribute('data-otp-phone');
            
            this.showOtpContainer(otpContainer);
            
            // Only generate OTP if:
            // 1. Container is not already visible, OR
            // 2. Phone number has actually changed from the last one we generated OTP for
            if (!otpContainer.classList.contains('otp-container-visible') || 
                (lastOtpPhone !== cleanPhone)) {
                this.generateOtp(phone, purpose, otpContainer);
                phoneField.setAttribute('data-last-phone', cleanPhone);
                otpContainer.setAttribute('data-otp-phone', cleanPhone);
            }
        }
        
        // Clear previous validation state
        this.clearOtpValidation(otpContainer);
    },

    /**
     * Handle phone field blur
     */
    handlePhoneBlur: function(phoneField, otpContainer, purpose) {
        const form = phoneField.closest('form');
        
        // If phone is currently verified (matches verified phone), don't show OTP section
        if (this.isPhoneVerified(form)) {
            this.hideOtpContainer(otpContainer);
            return;
        }
        
        const phone = phoneField.value.trim();
        const cleanPhone = phone.replace(/[^0-9]/g, '');
        
        // Show OTP container if phone is valid (but don't regenerate OTP if already shown)
        if (cleanPhone.length >= 10) {
            if (!otpContainer.classList.contains('otp-container-visible')) {
                this.showOtpContainer(otpContainer);
                this.generateOtp(phone, purpose, otpContainer);
                phoneField.setAttribute('data-last-phone', cleanPhone);
            }
        } else {
            this.hideOtpContainer(otpContainer);
        }
    },

    /**
     * Generate OTP
     */
    generateOtp: function(phone, purpose, container, forceNew) {
        // Handle default parameter
        if (typeof forceNew === 'undefined') {
            forceNew = false;
        }
        
        const form = container.closest('form');
        this.showStatus(container, 'Sending OTP...', 'info');
        this.disableOtpInputs(container, true);
        
        const requestBody = {
            phone: phone,
            purpose: purpose
        };
        
        // Add force_new parameter when resending
        if (forceNew) {
            requestBody.force_new = true;
        }
        
        fetch('/api/generate-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showStatus(container, data.message, 'success');
                this.disableOtpInputs(container, false);
                this.startResendCountdown(container);
                this.focusFirstOtpInput(container);
                
                // Handle development mode (only if explicitly enabled)
                if (data.development_mode && data.development_otp) {
                    console.log('DEVELOPMENT OTP:', data.development_otp);
                    this.showStatus(container, 'Development Mode: OTP is ' + data.development_otp, 'info');
                } else if (data.development_mode) {
                    console.log('Development mode enabled - check server logs for OTP details');
                }
                // In production mode, just show the standard success message (no development info)
            } else {
                this.showError(container, data.error);
                this.disableOtpInputs(container, true);
                
                if (data.rate_limited) {
                    this.hideOtpContainer(container);
                }
            }
        })
        .catch(error => {
            console.error('OTP generation error:', error);
            this.showError(container, 'Failed to send OTP. Please try again.');
            this.disableOtpInputs(container, true);
        });
    },

    /**
     * Verify OTP
     */
    verifyOtp: function(otpContainer) {
        // Prevent duplicate verification calls
        if (otpContainer.isVerifying) {
            console.log('🔒 OTP verification already in progress, skipping duplicate call');
            return;
        }
        
        const form = otpContainer.closest('form');
        const phone = form.phoneField.value.trim();
        const purpose = form.otpPurpose;
        const otp = this.getOtpValue(otpContainer);

        // Mark as verifying to prevent duplicates
        otpContainer.isVerifying = true;
        
        this.showStatus(otpContainer, 'Verifying OTP...', 'info');
        this.disableOtpInputs(otpContainer, true);
        
        fetch('/api/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                phone: phone,
                otp: otp,
                purpose: purpose
            })
        })
        .then(response => {
            console.log('📡 Raw response status:', response.status);
            console.log('📡 Raw response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text(); // Get as text first to debug
        })
        .then(responseText => {
            console.log('📡 Raw response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response from server');
            }
            
            // Debug logging to see what the server actually returned
            console.log('OTP Verification Response:', data);
            console.log('data.success:', data.success, '(type:', typeof data.success, ')');
            console.log('data.error:', data.error);
            console.log('data.verified:', data.verified);
            
            // Clear verifying flag
            otpContainer.isVerifying = false;
            
            if (data.success) {
                console.log('Taking SUCCESS path');
                
                try {
                    console.log('Calling showStatus...');
                    this.showStatus(otpContainer, '✓ Phone number verified successfully', 'success');
                    
                    console.log('Calling markPhoneAsVerified...');
                    this.markPhoneAsVerified(form);
                    
                    console.log('Handling submit button after verification...');
                    // Check if this is a booking form
                    const formPurpose = this.getFormPurpose(form);
                    if (formPurpose === 'booking_form') {
                        console.log('Booking form detected - dispatching phoneVerified event');
                        // For booking forms, dispatch event instead of directly enabling button
                        document.dispatchEvent(new CustomEvent('phoneVerified', {
                            detail: { form: form, phoneField: form.phoneField }
                        }));
                    } else {
                        console.log('Non-booking form detected - directly enabling submit button');
                        // Ensure submit button is available, try to find it if not stored
                        let submitButton = form.originalSubmitButton;
                        if (!submitButton) {
                            console.warn('form.originalSubmitButton not found, searching for submit button...');
                            submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (submitButton) {
                                console.log('Found submit button via querySelector:', submitButton);
                                form.originalSubmitButton = submitButton; // Store it for future use
                            }
                        }
                        this.enableSubmitButton(submitButton);
                    }
                    
                    console.log('Calling disableOtpInputs...');
                    this.disableOtpInputs(otpContainer, true);
                    
                    console.log('Setting up hideVerifiedOtpContainer timeout...');
                    // Hide OTP section after successful verification
                    setTimeout(() => {
                        this.hideVerifiedOtpContainer(otpContainer);
                    }, 2000); // Wait 2 seconds to show success message, then hide
                    
                    console.log('SUCCESS path completed successfully');
                } catch (successError) {
                    console.error('Error in SUCCESS path:', successError);
                    throw successError; // Re-throw to trigger catch block
                }
            } else {
                console.log('Taking ERROR path');
                // Enhanced error handling with better user feedback
                let errorMessage = data.error || 'Unknown error occurred';
                
                // Provide more helpful error messages
                if (data.invalid_otp && !data.max_attempts_exceeded) {
                    errorMessage = 'Invalid OTP. Please check your SMS and try again.';
                    
                    // Check if OTP might be expired (common user issue)
                    if (errorMessage.includes('expired')) {
                        errorMessage = 'OTP has expired. Please click "Resend OTP" to get a new code.';
                        this.enableResendButton(otpContainer);
                    }
                }
                
                this.showError(otpContainer, errorMessage);
                this.disableOtpInputs(otpContainer, false);
                this.clearOtpInputs(otpContainer);
                this.focusFirstOtpInput(otpContainer);

                if (data.max_attempts_exceeded) {
                    this.disableOtpInputs(otpContainer, true);
                    this.enableResendButton(otpContainer);
                    this.showError(otpContainer, 'Too many incorrect attempts. Please request a new OTP.');
                }
            }
        })
        .catch(error => {
            // Clear verifying flag on error
            otpContainer.isVerifying = false;
            
            console.error('OTP verification catch block triggered');
            console.error('Error type:', typeof error);
            console.error('Error message:', error.message);
            console.error('Full error object:', error);
            console.error('Error stack:', error.stack);
            
            // Check if this is a network error vs JavaScript error
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                console.error('Network error detected - likely CORS or server not responding');
                this.showError(otpContainer, 'Network error. Please check your connection and try again.');
            } else if (error.message.includes('JSON')) {
                console.error('JSON parsing error - server returned invalid JSON');
                this.showError(otpContainer, 'Server response error. Please try again.');
            } else if (error.message.includes('HTTP')) {
                console.error('HTTP error - server returned error status');
                this.showError(otpContainer, 'Server error. Please try again.');
            } else {
                console.error('Unknown error type');
                this.showError(otpContainer, 'Failed to verify OTP. Please try again.');
            }
            
            this.disableOtpInputs(otpContainer, false);
            this.clearOtpInputs(otpContainer);
        });
    },

    /**
     * Show/Hide OTP container
     */
    showOtpContainer: function(container) {
        container.style.display = 'block';
        container.classList.add('otp-container-visible');
    },

    hideOtpContainer: function(container) {
        container.style.display = 'none';
        container.classList.remove('otp-container-visible');
        this.clearOtpValidation(container);
        
        // Clear phone tracking when hiding container
        const form = container.closest('form');
        if (form && form.phoneField) {
            form.phoneField.removeAttribute('data-last-phone');
        }
        container.removeAttribute('data-otp-phone');
    },

    /**
     * Hide OTP container after successful verification (keeps verification status)
     */
    hideVerifiedOtpContainer: function(container) {
        container.style.display = 'none';
        container.classList.remove('otp-container-visible');
        
        // Don't clear verification status - keep the form verified
        // Don't clear phone tracking - verification is complete
    },

    /**
     * OTP input management
     */
    isOtpComplete: function(container) {
        console.log("###########################")
        const inputs = container.querySelectorAll('.otp-digit');
        return Array.from(inputs).every(input => input.value.trim() !== '');
    },

    getOtpValue: function(container) {
        const inputs = container.querySelectorAll('.otp-digit');
        return Array.from(inputs).map(input => input.value).join('');
    },

    clearOtpInputs: function(container) {
        const inputs = container.querySelectorAll('.otp-digit');
        inputs.forEach(input => input.value = '');
    },

    /**
     * Fill OTP inputs with a 6-digit code (useful for testing)
     */
    fillOtpInputs: function(container, otp) {
        const inputs = container.querySelectorAll('.otp-digit');
        const otpString = otp.toString().padStart(6, '0');
        
        inputs.forEach((input, index) => {
            if (index < otpString.length) {
                input.value = otpString[index];
            }
        });
        
        // Focus the last input
        if (inputs.length > 0) {
            inputs[inputs.length - 1].focus();
        }
        
        // Auto-verify if complete
        if (this.isOtpComplete(container)) {
            setTimeout(() => {
                this.verifyOtp(container);
            }, 100);
        }
    },

    disableOtpInputs: function(container, disabled) {
        const inputs = container.querySelectorAll('.otp-digit');
        inputs.forEach(input => input.disabled = disabled);
    },

    focusFirstOtpInput: function(container) {
        const firstInput = container.querySelector('.otp-digit');
        if (firstInput && !firstInput.disabled) {
            firstInput.focus();
        }
    },

    /**
     * Status and error management
     */
    showStatus: function(container, message, type) {
        // Handle default parameter
        if (typeof type === 'undefined') {
            type = 'info';
        }
        
        console.log('showStatus called:', { message, type });
        
        const statusDiv = container.querySelector('.otp-status');
        if (!statusDiv) {
            console.error('.otp-status element not found in container');
            return;
        }
        
        statusDiv.textContent = message;
        statusDiv.className = 'otp-status ' + type;
        statusDiv.style.display = 'block'; // Ensure it's visible
        
        console.log('Status set:', statusDiv.textContent, 'class:', statusDiv.className);
        
        // Clear error message
        this.clearError(container);
    },

    showError: function(container, message) {
        console.log('showError called:', message);
        
        const errorDiv = container.querySelector('.error-message');
        if (!errorDiv) {
            console.error('.error-message element not found in container');
            return;
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        console.log('Error set:', errorDiv.textContent);
        
        // Clear status
        const statusDiv = container.querySelector('.otp-status');
        if (statusDiv) {
            statusDiv.textContent = '';
            statusDiv.className = 'otp-status';
            statusDiv.style.display = 'none';
        }
    },

    clearError: function(container) {
        console.log('🧹 clearError called');
        const errorDiv = container.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
        }
    },

    clearOtpValidation: function(container) {
        console.log('🧹 clearOtpValidation called');
        this.clearError(container);
        const statusDiv = container.querySelector('.otp-status');
        if (statusDiv) {
            statusDiv.textContent = '';
            statusDiv.className = 'otp-status';
            statusDiv.style.display = 'none';
        }
        this.clearOtpInputs(container);
    },

    /**
     * Resend countdown
     */
    startResendCountdown: function(container) {
        const resendBtn = container.querySelector('.resend-otp-btn');
        const countdownSpan = resendBtn.querySelector('.countdown');
        let countdown = 60;
        
        resendBtn.disabled = true;
        
        const interval = setInterval(() => {
            countdown--;
            countdownSpan.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                this.enableResendButton(container);
            }
        }, 1000);
    },

    enableResendButton: function(container) {
        const resendBtn = container.querySelector('.resend-otp-btn');
        resendBtn.disabled = false;
        resendBtn.innerHTML = 'Resend OTP';
    },

    /**
     * Form state management
     */
    markPhoneAsVerified: function(form) {
        const phoneField = form.phoneField || form.querySelector('input[name="phone"], input[name="mobile"]');
        const cleanPhone = phoneField ? phoneField.value.replace(/[^0-9]/g, '') : '';
        
        console.log('[' + new Date().toLocaleTimeString() + '] markPhoneAsVerified called:', {
            cleanPhone: cleanPhone,
            formId: form.id || 'no-id',
            formClass: form.className
        });
        
        form.setAttribute('data-phone-verified', '1');
        form.setAttribute('data-verified-phone', cleanPhone);
        
        // Make phone field readonly after verification
        if (phoneField) {
            phoneField.setAttribute('readonly', 'readonly');
            phoneField.classList.add('verified-readonly');
            
            // Add a visual indicator that the phone is verified
            phoneField.style.backgroundColor = '#f8f9fa';
            phoneField.style.borderColor = '#28a745';
            phoneField.title = 'Phone number verified and locked';
        }
        
        // Add hidden input to track verification in form submission
        let verifiedInput = form.querySelector('input[name="phone_verified"]');
        if (!verifiedInput) {
            verifiedInput = document.createElement('input');
            verifiedInput.type = 'hidden';
            verifiedInput.name = 'phone_verified';
            form.appendChild(verifiedInput);
        }
        verifiedInput.value = '1';
        
        // Update any existing pincode restriction messages with verified status
        this.updatePincodeRestrictionLinks(form);
    },
    
    /**
     * Update existing pincode restriction messages with current verification status
     */
    updatePincodeRestrictionLinks: function(form) {
        const restrictionMessages = form.parentNode.querySelectorAll('.pincode-validation-error');
        restrictionMessages.forEach(message => {
            const links = message.querySelectorAll('a[href*="contact-us"]');
            links.forEach(link => {
                const currentHref = link.href;
                // Replace &verified=0 with &verified=1
                if (currentHref.includes('&verified=0')) {
                    link.href = currentHref.replace('&verified=0', '&verified=1');
                    console.log('[' + new Date().toLocaleTimeString() + '] Updated restriction link to verified=1');
                }
            });
        });
    },

    isPhoneVerified: function(form) {
        const phoneField = form.phoneField || form.querySelector('input[name="phone"], input[name="mobile"]');
        const currentPhone = phoneField ? phoneField.value.replace(/[^0-9]/g, '') : '';
        const verifiedPhone = form.getAttribute('data-verified-phone');
        
        return form.getAttribute('data-phone-verified') === '1' && verifiedPhone === currentPhone;
    },

    disableSubmitButton: function(button, reason) {
        if (!button) {
            console.warn('disableSubmitButton called with undefined button');
            return;
        }
        
        button.disabled = true;
        button.setAttribute('data-original-text', button.textContent || button.value);
        
        if (button.tagName === 'BUTTON') {
            button.textContent = reason;
        } else {
            button.value = reason;
        }
        
        button.setAttribute('title', reason);
    },

    enableSubmitButton: function(button) {
        if (!button) {
            console.warn('enableSubmitButton called with undefined button');
            return;
        }
        
        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        
        if (originalText) {
            if (button.tagName === 'BUTTON') {
                button.textContent = originalText;
            } else {
                button.value = originalText;
            }
        }
        
        button.removeAttribute('title');
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => OtpVerification.init());
} else {
    OtpVerification.init();
}

// Export for manual usage
window.OtpVerification = OtpVerification;
