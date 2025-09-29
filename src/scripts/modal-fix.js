// Modal functionality fix
document.addEventListener('DOMContentLoaded', function() {
    // Utility object with showModal function
    const UIUtils = {
        /**
         * Get cookie value by name
         */
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },

        /**
         * Show modal popup
         */
        showModal(modalSelector) {
            console.log("Showing modal:", modalSelector);
            const popup = document.querySelector(modalSelector);
            if (!popup) {
                console.error("Modal not found:", modalSelector);
                return;
            }

            const popupId = popup.getAttribute('id');
            if (!this.getCookie(`popupClosed_${popupId}`)) {
                popup.classList.add("active");
                // Add overlay container class
                document.querySelector('.modal-container')?.classList.remove('hidden-modal');
                console.log("Modal activated:", modalSelector);
            }
        },

        /**
         * Set a cookie with name, value and days to expiration
         */
        setCookie(name, value, days) {
            let expires = "";
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }
    };

    // Make UIUtils globally available
    window.UIUtils = UIUtils;

    // Modal triggers
    const modalTriggers = document.querySelectorAll('.open-modal');
    console.log("Found modal triggers:", modalTriggers.length);
    
    if (modalTriggers.length) {
        modalTriggers.forEach(trigger => {
            trigger.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalId = this.getAttribute("data-modal");
                console.log("Modal trigger clicked:", modalId);
                UIUtils.showModal(`#${modalId}`);
            });
        });
    }

    // Close button handlers for overlays
    document.querySelectorAll(".close-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            var popup = this.closest('.overlay');
            if (!popup) return;
            
            var popupId = popup.getAttribute('id');
            if (popup.dataset.permaClose == "true") {
                UIUtils.setCookie(`popupClosed_${popupId}`, "true", 365);
            }
            popup.classList.remove('active');
            
            // Hide modal container
            document.querySelector('.modal-container')?.classList.add('hidden-modal');
            
            // Pause any videos
            popup.querySelectorAll('.video-wrapper video').forEach(video => {
                video.pause();
                video.currentTime = 0;
            });
        });
    });
});

// Export for ES modules
export const ModalFix = { version: '1.0.0' };
