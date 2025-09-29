<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Common footer section for all pages
 */
function renderFooter() {
?>
<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-column">
            <h4>Home</h4>
            <ul>
                <li><a href="/book-now">Book Kinetic DX</a></li>
                <li><a href="/see-comparison">Compare Variants</a></li>
                <li><a href="#" class="open-modal" data-modal="popup-test-drive">Book Test Ride</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Ownership</h4>
            <ul>
                <li><a href="/range-x">Range X</a></li>
                <li><a href="/-/kinetic-ev-brochure.pdf" download="kinetic-ev-brochure.pdf">Download Brochure</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Our Story</h4>
            <ul>
                <li><a href="/about-us">About</a></li>
                <li><a href="https://iamkinetic.in/" target="_blank">Legacy</a></li>
                <li><a href="https://kineticindia.com/" target="_blank">Kinetic Group</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Quick Link</h4>
            <ul>
                <li><a href="/contact-us">Careers</a></li>
                <li><a href="/support">Support</a></li>
                <li><a href="/contact-us">Contact</a></li>
            </ul>
            <div class="footer-contact">
                <p>Toll Free no: <strong><a href="tel:8600096800">86000 96800</a></strong></p>
                <p>Mail us to: <a href="mailto:info@kineticev.in"><strong>info@kineticev.in</strong></a></p>
            </div>
            <div class="footer-social">
            <a href="https://www.facebook.com/kineticev.in" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.linkedin.com/company/kinetic-ev/" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://www.youtube.com/@kineticev_in" target="_blank" rel="noopener"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/kineticev.in/" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p class="footer-links">
            <a href="/privacy-policy">Privacy Policy</a> -
            <a href="/terms">Terms & Conditions</a> -
            2025 &copy;Copyright Kinetic. All Rights Reserved.
        </p>
        <p class="footer-disclaimer">
            Disclaimer: Specifications, features, and colors are subject to change without prior notice. Images are for
            illustration only. Actual range and product may vary. Please check with the dealership for the latest
            details.
        </p>
    </div>
</footer>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


<?php
}
?>
