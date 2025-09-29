<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php';

// Check URL parameters to determine mode
$hasVariant = isset($_GET['variant']) && !empty(trim($_GET['variant']));
$hasColor = isset($_GET['color']) && !empty(trim($_GET['color']));
$showBookingForm = $hasVariant && $hasColor;

if ($showBookingForm) {
    // Auto-ensure email notifications table exists
    require_once __DIR__ . '/EmailNotificationsMigration.php';
    EmailNotificationsMigration::ensureTableExists();

    // Load configuration
    $config = include 'config.php';

    // Handle error messages from process-payment redirect
    $error_message = '';
    if (isset($_GET['error'])) {
        $error_message = urldecode($_GET['error']);
    }

    function generateUniqueId()
    {
        // Get microsecond timestamp for uniqueness
        $timestamp = round(microtime(true) * 1000);

        // Generate random digits to make exactly 16 digits
        $timestampStr = (string) $timestamp;
        $remaining = 16 - strlen($timestampStr);

        if ($remaining > 0) {
            $random = str_pad(mt_rand(0, pow(10, $remaining) - 1), $remaining, '0', STR_PAD_LEFT);
            return $timestampStr . $random;
        }

        // If timestamp is too long, use last 16 digits
        return substr($timestampStr, -16);
    }

    $orderId = generateUniqueId();

    // Get the current domain with protocol
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $protocol . $_SERVER['HTTP_HOST'];

    // Use redirect URL from config or fallback to current domain
    $redirectUrl = $config['phonepe']['redirectUrl'] ?? ($domain . '/api/check-status.php');

    // Get variant from URL (?variant=dx or ?variant=dx-plus), default to 'dx+' if not present or invalid
    $variant = isset($_GET['variant']) ? strtolower(trim($_GET['variant'])) : 'dx+';
    if ($variant !== 'dx' && $variant !== 'dx-plus') {
        $variant = 'dx+';
    }

    function moneyFormatIndia($num)
    {
        $explrestunits = "";
        if (strlen($num) > 3) {
            $lastthree = substr($num, -3);
            $restunits = substr($num, 0, -3); // extracts the last three digits
            $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
            $expunit = str_split($restunits, 2);
            foreach ($expunit as $k => $v) {
                if ($k == 0) {
                    $explrestunits .= (int) $v . ",";
                } else {
                    $explrestunits .= $v . ",";
                }
            }
            $thecash = $explrestunits . $lastthree;
        } else {
            $thecash = $num;
        }
        return $thecash;
    }
    $price = ($variant === 'dx') ? 111499 : 117499;
    $formattedPrice = moneyFormatIndia($price) . '.00';
}

require_once 'components/layout.php';

$preload_images = [
    "/-/images/new/red/000145.png",
    "/-/images/new/black/000145.png",
    "/-/images/new/white/000145.png",
    "/-/images/new/blue/000145.png",
    "/-/images/new/gray/000145.png"
];

if ($showBookingForm) {
    startLayout("Book Your Kinetic EV Scooter Online with Ease", [
        'preload_images' => $preload_images,
        'description' => 'Book your Kinetic EV scooter easily online with a quick secure process and enjoy doorstep delivery with hassle free experience for your perfect ride today',
        'canonical' => 'https://kineticev.in/book-now',
        'body_theme' => '#eaeaea',
        'header_config' => [
            'book_btn_text' => 'Book Now',
            'book_btn_link' => '#'
        ]
    ]);
} else {
    startLayout("Select Your Kinetic EV Scooter Variant Online", [
        'preload_images' => $preload_images,
        'description' => 'Select your favorite Kinetic EV scooter from multiple variants offering attractive designs colors advanced features and dependable performance for riders',
        'canonical' => 'https://kineticev.in/choose-variant',
        'body_theme' => '#eaeaea',
        'header_config' => [
            'book_btn_text' => 'Book Now',
            'book_btn_link' => '/test-ride'
        ]
    ]);
}
?>
<main>
    <?php if ($showBookingForm): ?>
    <!-- BOOKING FORM MODE -->
    <div class="side-screen-2 show-logo">
        <div class="side-page-content">
            <div class="form-container">
                <?php
                // Get the base URL for absolute form action
                // Enhanced HTTPS detection for various server configurations
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                           (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
                
                $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
                
                // For production domains, always use HTTPS
                if ($host === 'kineticev.in' || $host === 'www.kineticev.in') {
                    $protocol = 'https://';
                } else {
                    $protocol = $isHttps ? 'https://' : 'http://';
                }
                
                $base_url = $protocol . $host;
                ?>
                <form class="legend-form" action="<?php echo $base_url; ?>/api/process-payment" method="post">
                    <?php if ($error_message): ?>
                        <div class="error-message"
                            style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <a href="/book-now" class="back-button">←</a>
                    <br>
                    <h4>Book Your</h4>
                    <h2>Kinetic DX</h2>

                    <div class="form-group">
                        <label for="phone">Enter Your Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+91 0000 000 000" required
                            data-validation="required,indian_mobile" data-error-required="This field is required"
                            data-error-pattern="Please enter a valid 10-digit mobile number">
                        <div class="error-message"></div>
                    </div>
                    <div class="user-info">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="firstname" placeholder="Your Full Name" required
                                data-validation="required,alphabets_only,min_length:2"
                                data-error-required="This field is required" data-error-pattern="Enter a valid name"
                                data-error-min-length="Name should be larger than 2 characters">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Your Email" required
                                data-validation="required,email" data-error-required="This field is required"
                                data-error-pattern="Please enter a valid email address">
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="address">Your Address</label>
                            <input type="text" id="address" name="address" placeholder="Address" required
                                data-validation="required,min_length:5" data-error-required="This field is required"
                                data-error-min-length="Address must be at least 5 characters">
                            <div class="error-message"></div>
                        </div>
                        <div class="flex">
                            <div class="form-group">
                                <label for="pincode">Pincode</label>
                                <input type="text" id="pincode" name="pincode" placeholder="Pincode" required
                                    data-validation="required,indian_pincode"
                                    data-error-required="This field is required"
                                    data-error-pattern="Please enter a valid 6-digit pin code">
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" placeholder="City" required
                                    data-validation="required,alphabets_only"
                                    data-error-required="This field is required"
                                    data-error-pattern="Enter a valid city name" readonly>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" placeholder="State" required
                                    data-validation="required,alphabets_only"
                                    data-error-required="This field is required"
                                    data-error-pattern="Enter a valid state name" readonly>
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <h5>Last check before we proceed</h5>
                            <p>Note: Choose how to pay for your Kinetic at the time of purchase. ₹1000 is
                                booking amount.</p>
                        </div>

                        <div class="yesno-banner">
                            <label>
                                <input type="checkbox" name="ownedBefore">
                                <span>Have you or your family ever owned Kinetic DX?</span>
                            </label>
                        </div>

                        <div class="form-group variant-group">
                            <label>Variant</label>
                            <div class="variant-options" data-validation="required"
                                data-error-required="Please select a variant or go back to choose variant">
                                <label for="variant-dx">
                                    DX
                                    <input type="radio" id="variant-dx" name="variant" value="dx" required>
                                </label>
                                <label for="variant-dx-plus">
                                    DX+
                                    <input type="radio" id="variant-dx-plus" name="variant" value="dx-plus">
                                </label>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group color-group">
                            <label>Colour</label>
                            <div class="color-options" data-validation="required"
                                data-error-required="Please select a color or go back to choose variant">
                                <label>
                                    <input type="radio" name="color" value="red">
                                    <span class="color red">Red</span>
                                </label>
                                <label>
                                    <input type="radio" name="color" value="blue">
                                    <span class="color blue">Blue</span>
                                </label>
                                <label>
                                    <input type="radio" name="color" value="white">
                                    <span class="color white">White</span>
                                </label>
                                <label>
                                    <input type="radio" name="color" value="black">
                                    <span class="color black">Black</span>
                                </label>
                                <label>
                                    <input type="radio" name="color" value="grey">
                                    <span class="color grey">Silver</span>
                                </label>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="price-box">
                            <div class="label">
                                Price Starting from <br>
                                <small>(Ex-Showroom Price)</small>
                            </div>
                            <div class="amount">
                                <div><span>₹ <?php echo $formattedPrice; ?>/-</span></div>
                                <small> Booking amount is Fully Refundable</small>
                            </div>

                        </div>

                        <div class="form-group terms">
                            <label for="terms">
                                <input type="checkbox" id="terms" name="terms" required data-validation="required"
                                    data-error-required="You must agree to the terms and conditions">
                                I agree to the Terms &amp; Conditions &amp; Privacy Policy*
                            </label>
                            <div class="error-message"></div>
                        </div>

                        <p class="support">Facing trouble? Reach out to us at <a href="tel:+918600096800">+91 86000
                                96800</a></p>
                        <input type="hidden" name="txnid" value="<?php echo htmlspecialchars($orderId); ?>">
                        <input type="hidden" name="amount" value="1000.00" />
                        <input type="hidden" name="help_type" value="book-now" />
                    </div>
                    <button type="submit" class="submit-button pay-button" disabled>PAY ₹1000/-</button>
                    <p class="support" style="margin-top: 10px;">
                        Limited booking for only 35,000 scooters <br />
                        subject to availability
                    </p>
                </form>
            </div>
        </div>
        <div class="background">
            <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1"
                xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1920 1080">
                <path fill="#e03535" d="M0,716.6h1866.7l-354.5,363.4H0v-363.4h0Z" />
                <path fill="#1666b7" d="M2186.2,716.5H449.6l309.8-305.6h1426.8v305.6Z" />
                <image id="image" isolation="isolate" width="1338" height="1200"
                    transform="translate(47.3 66.5) scale(.8)" href="/-/images/new/white/000145.png"
                    class="background-model-image" />
            </svg>
        </div>
    </div>
    <?php else: ?>
    <!-- VARIANT SELECTION MODE -->
    <?php
    // Get the base URL for absolute form action
    // Enhanced HTTPS detection for various server configurations
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // For production domains, always use HTTPS
    if ($host === 'kineticev.in' || $host === 'www.kineticev.in') {
        $protocol = 'https://';
    } else {
        $protocol = $isHttps ? 'https://' : 'http://';
    }
    
    $base_url = $protocol . $host;
    ?>
    <div class="side-screen-1 show-logo">
        <div class="side-page-content">
            <div class="form-container">
                <form class="legend-form" action="<?php echo $base_url; ?>/book-now" method="get">
                    <div class="legend-wrapper">
                        <div class="form-variant-selector">
                            <div class="heading">
                                <h4>Relive</h4>
                                <h2>The Legend </h2>
                            </div>
                            <div class="model-container">
                                <div class="model-viewer">
                                    <img class="model" src="/-/images/new/red/000179.png" alt="" />
                                </div>
                            </div>
                            <div class="body-content">
                                <div class="form-group">
                                    <label for="variant">Select your variant</label>
                                    <select id="variant" name="variant" required data-validation="required"
                                        data-error-required="This field is required">
                                        <option value="">Choose Variant</option>
                                        <option value="dx-plus">DX+</option>
                                        <option value="dx">DX</option>
                                    </select>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label class="shade-label">Pick your legendary shade</label>
                                    <div class="color-options" data-validation="required"
                                        data-error-required="This field is required">
                                        <label for="color-red">
                                            <input type="radio" id="color-red" name="color" value="red" required />
                                            <span class="color red" title="Red"></span>
                                        </label>
                                        <label for="color-blue">
                                            <input type="radio" id="color-blue" name="color" value="blue" required />
                                            <span class="color blue" title="Blue"></span>
                                        </label>
                                        <label for="color-white">
                                            <input type="radio" id="color-white" name="color" value="white" required />
                                            <span class="color white" title="White"></span>
                                        </label>
                                        <label for="color-black">
                                            <input type="radio" id="color-black" name="color" value="black" required />
                                            <span class="color black" title="Black"></span>
                                        </label>
                                        <label for="color-grey">
                                            <input type="radio" id="color-grey" name="color" value="grey" required />
                                            <span class="color grey" title="Grey"></span>
                                        </label>
                                    </div>
                                    <div class="error-message"></div>
                                </div>
                                <button type="submit" id="book-now-btn" disabled>Book Now</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="background">
            <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1920 1080">
                <path fill="#e03535" d="M0,716.6h1866.7l-354.5,363.4H0v-363.4h0Z"/>
                <path fill="#1666b7" d="M2186.2,716.5H449.6l309.8-305.6h1426.8v305.6Z"/>
                <image id="image" isolation="isolate" width="1450" height="1300" transform="translate(47.3 66.5) scale(.8)" href="/-/images/new/white/000145.png" class="background-model-image"/>
            </svg>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Use the compiled BookingFormHandler from the build system
        document.addEventListener("DOMContentLoaded", function () {
            if (window.BookingFormHandler) {
                <?php if ($showBookingForm): ?>
                // Initialize booking payment form with URL parameters
                window.BookingFormHandler.initBookingPaymentForm();
                <?php else: ?>
                // Initialize choose variant form  
                window.BookingFormHandler.initChooseVariantForm();
                <?php endif; ?>
            } else {
                console.error('BookingFormHandler not found - check if build system compiled correctly');
            }
        });
    </script>

    <style>
        .color-options label {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .color-options label[style*="display: none"] {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .color-options label[style*="display: inline-block"] {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        
        .variant-options {
            label {
                display: none;

                &:has(input:checked) {
                    display: block;
                }
            }
        }
        
        /* Book Now button styling */
        #book-now-btn:disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
    </style>
</main>
<?php endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>
