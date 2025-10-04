<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

// Include config file for environment detection
require_once __DIR__ . '/../config.php';

/**
 * Common header section for all pages
 */
function renderHeader()
{
    ?>
    <header class="main-header">
        <div class="header-wrapper">
            <div class="logo">
                <a href="/"><img src="/-/images/logo-large.png" alt="Kinetic" /></a>
            </div>
            <div>
                <a href="#" class="btn-outline open-modal" data-modal="popup-test-drive">Test Ride</a>
                <a href="/book-now" class="btn-primary book-btn">Book Now</a>
            </div>

            <!-- Hidden checkbox -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle" />

            <!-- Hamburger -->
            <label for="menu-toggle" class="hamburger">
                <span></span><span></span><span></span>
            </label>

            <!-- Navigation -->
            <nav class="nav-links">
                <a href="/about-us">About</a>
                <a href="/see-comparison">Explore DX</a>
                <a href="/range-x">Range X</a>
                <a href="/contact-us">Contact</a>
                <a href="<?php 
                    $env = determineEnvironment();
                    $subdomain = match($env) {
                        'production' => 'www',
                        'test' => 'test',
                        'uat' => 'uat',
                        'development', 'dev' => 'dev',
                        default => 'local'
                    };
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    echo "{$protocol}://{$subdomain}.blog.kineticev.in/";
                ?>">Blog</a>
                <a href="#" class="btn-outline open-modal" data-modal="popup-test-drive">Test Ride</a>
                <a href="/book-now" class="btn-primary book-btn">Book Now</a>
            </nav>
        </div>
    </header>
    <div class="preloader">
        <div id="svg-preloader">
            <svg viewBox="0 0 100 100">
                <path id="preloader-path" fill="none" stroke="#d92128" stroke-dasharray="600" stroke-dashoffset="1200"
                    stroke-miterlimit="2" stroke-width="2"
                    d="M70,80.6c-2.2-1.5-5.1-3.6-7.9-5.9-7.3-5.7-15.2-12.5-15.2-13.9s32.6-26.6,52.3-41.3h-34.1c-12.1,11.2-38.4,31.9-41.4,33.3-2.4,1.1-9.4,2.2-12.5,1.1-1.3-.4-1.5-1.1-1.8-3.1-2.9,3.5-5.9,6.8-8.7,10.4,5.3-.4,11.7-2.6,16.3-.9,2,.8,16.9,13.3,24.4,20.4h28.7-.2Z" />
            </svg>
        </div>
        <div class="loading-progress">
            <div class="progress-bar"></div>
            <div class="progress-text">0%</div>
        </div>
    </div>
    <?php
}
?>