<?php
require_once 'components/layout.php';

$preload_images = [
    "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("The Legend Is Reborn | Kinetic Electric Scooter", [
    'preload_images' => $preload_images,
    'description' => 'Kinetic EV scooters deliver eco friendly rides with advanced features superior performance and smart technology for smooth daily commuting in India',
    'canonical' => 'https://kineticev.in/'
]);

?>

<div class="legend-reborn">
    <div class="image-container">
        <picture>
            <source srcset="/-/images/slide-001.jpg" media="(min-width: 768px)">
            <source srcset="/-/images/legend-reborn-m.jpg" media="(max-width: 767px)">
            <img src="/-/images/legend-reborn-m.jpg" alt="Kinetic Reborn" class="kinetic-image">
        </picture>
    </div>
</div>


<div class="og-gangster-returns">
    <div class="og-gangster-wrapper">
        <div class="section-area-scroll">
            <div class="scroll-area-title">
                <picture>
                    <source srcset="/-/images/tog-text-d.jpg" media="(min-width: 768px)">
                    <source srcset="/-/images/tog-text-m.jpg" media="(max-width: 767px)">
                    <img src="/-/images/tog-text-m.jpg" alt="The Original Gangster" class="kinetic-image">
                </picture>
            </div>
        </div>
        <div class="screen section-area-scroll">
            <div class="image-container">
                <picture>
                    <source srcset="/-/images/og-ganster-returns-d.jpg" media="(min-width: 768px)">
                    <source srcset="/-/images/og-ganster-returns-m.jpg" media="(max-width: 767px)">
                    <img src="/-/images/og-ganster-returns-m.jpg" alt="OG Gangster Returns" class="kinetic-image">
                </picture>
            </div>
        </div>
    </div>
</div>


<div class="stacked-360">
    <div class="stacked-360-wrapper">
        <div class="section-area-scroll stacked-af">
            <div class="scroll-area-title">
                <!-- Add .shrink-title-img to enable the width-shrink animation -->
                <img src="/-/images/stacked-af.jpg" alt="The Original Gangster" class="kinetic-image">
            </div>
        </div>
        <div class="screen section-area-scroll">
            <div class="feature-columns">
                <ul class="feature-left">
                    <li>Legendary Kinetic Reliability</li>
                    <li>Effortless Cruise Control</li>
                    <li>8.8" Iconic Cluster</li>
                    <li>60 Volt Efficiency</li>
                    <li>Built-in Speaker</li>
                    <li>Illuminated Branding</li>
                    <li>Super Strong Metal Body</li>
                    <li></li>
                    <li></li>
                </ul>
                <div class="model-viewer">
                    <img class="model" src="/-/images/new/red/000179.png" alt="" />
                </div>
                <ul class="feature-right">
                    <li>One Click Storage Access</li>
                    <li>My Kiney Companion</li>
                    <li>Extended Long Seat</li>
                    <li>Hill Hold</li>
                    <li>3 Riding Modes</li>
                    <li>24X7 Kinetic assist</li>
                    <li>Bluetooth Voice Navigation</li>
                    <li></li>
                    <li></li>
                </ul>
                <div class="color-picker">
                    <div>
                        <span>
                            <label style="background: #e00000">
                                <input name="color" type="radio" value="red" id="red" checked>
                            </label>
                        </span>
                        <span>
                            <label style="background: #12589F">
                                <input type="radio" name="color" value="blue" id="blue">
                            </label>
                        </span>
                        <span>
                            <label style="background: #fff">
                                <input type="radio" name="color" value="white" id="white">
                            </label>
                        </span>
                        <span>
                            <label style="background: #000">
                                <input type="radio" name="color" value="black" id="black">
                            </label>
                        </span>
                        <span>
                            <label style="background: #888">
                                <input type="radio" name="color" value="gray" id="gray">
                            </label>
                        </span>
                    </div>
                </div>
                <div class="reveal-features">
                    <label class="show-hide">
                        <i class="fa fa-chevron-down down"></i>
                        <i class="fa fa-chevron-up up"></i>
                        <span>Awesome Features!</span>
                        <input type="checkbox" style="display: none;">
                    </label>
                    <div class="feature-icons">
                        <div class="feature-icons-wrapper">
                            <a href="javascript:void(0)" data-item="easy-charge" data-modal="popup-video-playlist"
                                class="open-modal">
                                <img src="/-/images/features/features-1.jpg" alt="Feature 1">
                                <span>Easy Charge</span>
                            </a>
                            <a href="javascript:void(0)" data-item="easy-flip" data-modal="popup-video-playlist"
                                class="open-modal">
                                <img src="/-/images/features/features-2.jpg" alt="Feature 2">
                                <span>Easy Flip</span>
                            </a>
                            <a href="javascript:void(0)" data-item="storage-and-charging"
                                data-modal="popup-video-playlist" class="open-modal"><img
                                    src="/-/images/features/features-3.jpg" alt="Feature 3">
                                <span>Under Seat Storage</span>
                            </a>
                            <a href="javascript:void(0)" data-item="easy-key" data-modal="popup-video-playlist"
                                class="open-modal"><img src="/-/images/features/features-5.jpg" alt="Feature 5">
                                <span>Easy Key</span>
                            </a>
                            <a href="javascript:void(0)" data-item="my-kiney" data-modal="popup-video-playlist"
                                class="open-modal"><img src="/-/images/features/features-4.jpg" alt="Feature 4">
                                <span>Telekinetics</span>
                            </a>
                            <a href="javascript:void(0)" data-item="battery" data-modal="popup-video-playlist"
                                class="open-modal"><img src="/-/images/features/features-6.jpg" alt="Feature 6">
                                <span>LFP Battery</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="section-compare" id="compare">
    <div class="compare-wrapper">
        <div class="slider-wrapper">
            <div class="slider">
                <div class="slide">
                    <img src="/-/images/slider/red-000144.png">
                </div>
                <div class="slide">
                    <img src="/-/images/slider/black-000044.png">
                </div>
            </div>
            <div class="custom-dots"></div>
        </div>
        <div class="specs-slider">
            <div class="slide">

                <div class="specs">
                    <div>
                        <span>Est. IDC RANGE</span><strong>116 km</strong>
                    </div>
                    <div><span>Top Speed</span><strong>90 km/h</strong></div>
                    <div><span>Charger Type</span><strong>Easy Charge™</strong></div>
                    <div><span>Tech</span><strong>Telekinetics</strong></div>
                    <div class="color-picker">
                        <span>Colours</span>
                        <div>
                            <span>
                                <label style="background: #e00000">
                                    <input type="radio" name="spec-color" value="red" id="">
                                </label>
                            </span>
                            <span>
                                <label style="background: #12589F">
                                    <input type="radio" name="spec-color" value="blue" id="">
                                </label>
                            </span>
                            <span>
                                <label style="background: #fff">
                                    <input type="radio" name="spec-color" value="white" id="">
                                </label>
                            </span>
                            <span>
                                <label style="background: #000">
                                    <input type="radio" name="spec-color" value="black" id="">
                                </label>
                            </span>
                            <span>
                                <label style="background: #888">
                                    <input type="radio" name="spec-color" value="gray" id="">
                                </label>
                            </span>
                        </div>
                    </div>
                    <div class="price">Ex. Showroom Price<br /><strong>₹1,17,499</strong></div>
                </div>
            </div>
            <div class="slide">
                <div class="specs">
                    <div>
                        <span>Est. IDC RANGE</span><strong>102 km</strong>
                    </div>
                    <div><span>Top Speed</span><strong>80 km/h</strong></div>
                    <div><span>Charger Type</span><strong>Off Board</strong></div>
                    <div></div>
                    <div class="color-picker">
                        <span>Colours</span>
                        <div>
                            <span>
                                <label style="background: #000">
                                    <input type="radio" name="spec-color" value="black" id="">
                                </label>
                            </span>
                            <span>
                                <label style="background: #888">
                                    <input type="radio" name="spec-color" value="gray" id="">
                                </label>
                            </span>
                        </div>
                    </div>
                    <div class="price">Ex. Showroom Price<br /><strong>₹1,11,499</strong></div>
                </div>
            </div>
        </div>
        <div class="specs-container">
            <div class="price-book">
                <a href="/book-now">Book Now</a>
                <a href="/see-comparison" class="see-comparison">See Comparison</a>
            </div>
        </div>
    </div>
</div>


<div class="telekinetics">
    <div class="telekinetics-header">
        <img src="/-/images/telekinetics-header.jpg" alt="Telekinetics Header" loading="lazy">
    </div>
    <div class="masonry-grid">
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/find-my-kiney.png" alt="Find My Kiney" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/guide-me-home.png" alt="Guide Me Home" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/on-off-ignition.png" alt="On/Off Ignition" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/fota-updates.png" alt="FOTA Updates" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/seat-charger-opening.png" alt="Seat & Charger Opening" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/trip-analysis.png" alt="Trip Analysis" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/geo-fencing.png" alt="Geo Fencing" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/track-my-kinetic.png" alt="Track My Kinetic" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/data-analytics.png" alt="Data Analytics" loading="lazy">
        </div>
        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/anti-theft-alert.png" alt="Anti-Theft Alert" loading="lazy">
        </div>

        <div class="card">
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <div class="mouse-tracker"></div>
            <img src="/-/images/telekinetics/new/bluetooth.png" alt="Bluetooth" loading="lazy">
        </div>
    </div>
</div>

<div class="model-banner">
    <img src="/-/images/bikes-5.jpg" alt="">
</div>


<div class="my-kiney-section">
    <div class="video-container">
        <div class="video-wrapper">
            <video class="mykiney-video" muted preload="auto" crossorigin="anonymous" playsinline webkit-playsinline
                poster="/-/images/my-kiney-video-poster.png" controls>
                <source src="/-/videos/my-kiney.mp4" type="video/mp4; codecs='avc1.640029'">
                <source src="/-/videos/my-kiney.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
    <section class="reliability-section">
        <div class="reliability-title">
            <picture>
                <source media="(min-width: 768px)" srcset="/-/images/legacy-backed-reliability-d.jpg">
                <source media="(max-width: 767px)" srcset="/-/images/legacy-backed-reliability-d.jpg">
                <img src="/-/images/legacy-backed-reliability-d.jpg" alt="My Kiney Title">
            </picture>
        </div>
        <div class="features">
            <div class="feature">
                <div class="icon">
                    <img src="/-/images/call-support.svg" alt="Assist Icon">
                </div>
                <div class="content">
                    <h3>Kinetic Assist is just a click away</h3>
                    <p>Need assistance? One button connects you instantly.</p>
                    <p>Support, always by your side.</p>
                </div>
            </div>
            <div class="feature">
                <div class="icon">
                    <img src="/-/images/robust-warranty.svg" alt="Warranty Icon">
                </div>
                <div class="content">
                    <h3>Robust Warranty</h3>
                    <p>Trust that travels with you. Enjoy 3 years/50,000KM &</p>
                    <p>up to 9 years/1,00,000KM extended
                        warranty coverage and RSA support.*</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// Include modals before the footer
require_once 'components/modals.php';
renderModals();
?>

<?php endLayout(); ?>