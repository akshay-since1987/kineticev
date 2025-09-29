<?php
require_once 'components/layout.php';

// --- POPUP PROMO FORM LOGIC ---
$promo_message = '';
$promo_class = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['popup_promo_submit'])) {
    $promo_fields = [];
    foreach ($_POST as $k => $v) {
        if ($k === 'popup_promo_submit')
            continue;
        $promo_fields[$k] = htmlspecialchars(trim($v));
    }
    $promo_fields['submitted_at'] = date('Y-m-d H:i:s');
    $promo_fields['form_page'] = 'see-comparison.php:popup-promo';
    // Simple validation
    if (empty($promo_fields['popup_name']) || empty($promo_fields['popup_phone']) || empty($promo_fields['popup_variant']) || empty($promo_fields['popup_code'])) {
        $promo_message = 'Please fill all fields.';
        $promo_class = 'error';
    } else {
        $table = 'seecomparison_popup_promo_submissions';
        $mysqli = new mysqli('localhost', 'root', '', 'kineticev');
        if ($mysqli->connect_errno) {
            $promo_message = 'DB connection failed: ' . $mysqli->connect_error;
            $promo_class = 'error';
        } else {
            $mysqli->query("CREATE TABLE IF NOT EXISTS $table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                popup_name VARCHAR(255),
                popup_phone VARCHAR(50),
                popup_variant VARCHAR(50),
                popup_code VARCHAR(50),
                submitted_at DATETIME
            )");
            $stmt = $mysqli->prepare("INSERT INTO $table (popup_name, popup_phone, popup_variant, popup_code, submitted_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $promo_fields['popup_name'], $promo_fields['popup_phone'], $promo_fields['popup_variant'], $promo_fields['popup_code'], $promo_fields['submitted_at']);
            if ($stmt->execute()) {
                $promo_message = 'Thank you! Your details have been submitted.';
                $promo_class = 'success';
            } else {
                $promo_message = 'Error saving your details. Please try again.';
                $promo_class = 'error';
            }
            $stmt->close();
            $mysqli->close();
        }
    }
}
// --- END POPUP PROMO FORM LOGIC ---

// --- POPUP TEST DRIVE FORM LOGIC ---
$popup_test_message = '';
$popup_test_class = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['popup_test_submit'])) {
    $test_fields = [];
    foreach ($_POST as $k => $v) {
        if ($k === 'popup_test_submit')
            continue;
        $test_fields[$k] = htmlspecialchars(trim($v));
    }
    $test_fields['submitted_at'] = date('Y-m-d H:i:s');
    $test_fields['form_page'] = 'see-comparison.php:popup-test-drive';
    if (empty($test_fields['test_name']) || empty($test_fields['test_phone']) || empty($test_fields['test_date']) || empty($test_fields['test_code'])) {
        $popup_test_message = 'Please fill all fields.';
        $popup_test_class = 'error';
    } else {
        $table = 'seecomparison_popup_test_drive_submissions';
        $mysqli = new mysqli('localhost', 'root', '', 'kineticev');
        if ($mysqli->connect_errno) {
            $popup_test_message = 'DB connection failed: ' . $mysqli->connect_error;
            $popup_test_class = 'error';
        } else {
            $mysqli->query("CREATE TABLE IF NOT EXISTS $table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                test_name VARCHAR(255),
                test_phone VARCHAR(50),
                test_date VARCHAR(50),
                test_code VARCHAR(50),
                submitted_at DATETIME
            )");
            $stmt = $mysqli->prepare("INSERT INTO $table (test_name, test_phone, test_date, test_code, submitted_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $test_fields['test_name'], $test_fields['test_phone'], $test_fields['test_date'], $test_fields['test_code'], $test_fields['submitted_at']);
            if ($stmt->execute()) {
                $popup_test_message = 'Thank you! Your details have been submitted.';
                $popup_test_class = 'success';
            } else {
                $popup_test_message = 'Error saving your details. Please try again.';
                $popup_test_class = 'error';
            }
            $stmt->close();
            $mysqli->close();
        }
    }
}
// --- END POPUP TEST DRIVE FORM LOGIC ---

startLayout("Compare Kinetic EV Scooter Models for Best Choice", [
    'description' => 'Compare Kinetic EV scooters easily view specifications performance features and prices side by side to find the best electric scooter for your lifestyle',
    'canonical' => 'https://kineticev.in/see-comparison',
    'body_theme' => '#eaeaea',
    'preload_images' => [
        "/-/images/new/red/000144.png",
        "/-/images/new/black/000044.png"
    ],
    'additionalStyles' => [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'
    ]
]);
?>

<section class="model-compare">
    <div class="top-bar">
        <div class="model-images">
            <div class="model-intro">
                <br />
                <br />
                <div class="title-section">
                    <p>Know Your</p>
                    <h2>GANGSTER</h2>
                    <div class="toggle-button">
                        <label class="toggle">
                            <span>Key Difference</span>
                            <input type="checkbox" id="key-diff-toggle" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="features-section">
        <h3>Features</h3>
        <div class="features-table" id="features-table">
            <div class="feature-header">
                <div data-header><span>Feature</span></div>
                <div data-header>
                    <svg width="50" viewBox="0 0 94 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M72.147 11.5605V6.78613H58.8098C58.8098 6.78613 58.5678 6.78613 58.4602 6.78613H55.1259V11.5605H59.3476L54.4806 14.7435L49.6135 11.5605H53.3512V6.78613H40.0139C40.0139 6.78613 39.772 6.78613 39.6644 6.78613H36.3301V11.5605H40.0677L49.8824 17.3047L40.0677 22.5267H40.0139C40.0139 22.5267 39.772 22.5267 39.6644 22.5267H36.3301V27.3011H53.3781V22.5267H49.6404L54.5074 19.866L59.3744 22.5267H58.8636C58.8636 22.5267 58.6216 22.5267 58.514 22.5267H55.1797V27.3011H72.2277V22.5267H68.9472L59.1324 17.3047L68.9472 11.5605H72.2277H72.147Z"
                            fill="black" />
                        <path
                            d="M33.8809 17.2798C33.8809 17.2798 33.8809 17.1058 33.8809 17.0312C33.8809 16.9317 33.8809 16.4841 33.8809 16.1608C33.6926 12.779 31.4877 10.0934 27.4811 8.40243C27.4005 8.3527 27.2929 8.32783 27.1853 8.2781L26.4594 8.00456H26.3787C23.4746 6.98503 20.4899 6.81096 19.3605 6.76123C19.2798 6.76123 19.1992 6.76123 19.1454 6.76123H0V12.953H1.10244C1.10244 12.953 3.19985 12.953 4.06031 12.953V14.5694V16.5836V17.8518V21.0596C3.46874 21.0596 1.61337 21.0596 1.12936 21.0596H0.0268748V27.2514H19.1454C19.1454 27.2514 19.3067 27.2514 19.3874 27.2514C20.5436 27.2265 23.5284 27.0524 26.4324 26.0329L27.2123 25.7594H27.2391C27.2391 25.7594 27.4274 25.6599 27.508 25.635C31.5146 23.9441 33.7195 21.2585 33.9077 17.8766C33.9077 17.7523 33.9077 17.5534 33.9077 17.255M26.2711 17.056C26.2711 17.255 26.2174 17.6528 26.2174 17.7523C26.0829 18.8216 25.3569 19.6173 23.9855 20.1892C23.8511 20.2638 23.6897 20.3136 23.5015 20.3882C21.5385 21.0596 19.3874 21.0844 19.3605 21.0844H11.6432C11.6432 19.12 11.6432 14.9424 11.6432 12.9779H19.3605C19.3605 12.9779 21.5654 12.9779 23.5015 13.6742C23.6897 13.7488 23.8511 13.7985 23.9855 13.8731C25.3569 14.4699 26.0829 15.2656 26.2174 16.3349C26.2174 16.3846 26.2174 16.5836 26.2174 16.683C26.2174 16.7825 26.2174 16.882 26.2174 16.9566C26.2174 16.9814 26.2174 17.0312 26.2174 17.056"
                            fill="black" />
                        <path
                            d="M93.3078 6.81091L93.2808 11.6102H86.6391V17.802H81.4494V11.6102H74.7539V6.81091H81.4494V0.594238L86.6391 0.619105V6.81091H93.3078Z"
                            fill="black" />
                    </svg>
                </div>
                <div data-header>
                    <svg width="50" viewBox="0 0 94 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M72.147 11.5605V6.78613H58.8098C58.8098 6.78613 58.5678 6.78613 58.4602 6.78613H55.1259V11.5605H59.3476L54.4806 14.7435L49.6135 11.5605H53.3512V6.78613H40.0139C40.0139 6.78613 39.772 6.78613 39.6644 6.78613H36.3301V11.5605H40.0677L49.8824 17.3047L40.0677 22.5267H40.0139C40.0139 22.5267 39.772 22.5267 39.6644 22.5267H36.3301V27.3011H53.3781V22.5267H49.6404L54.5074 19.866L59.3744 22.5267H58.8636C58.8636 22.5267 58.6216 22.5267 58.514 22.5267H55.1797V27.3011H72.2277V22.5267H68.9472L59.1324 17.3047L68.9472 11.5605H72.2277H72.147Z"
                            fill="black" />
                        <path
                            d="M33.8809 17.2798C33.8809 17.2798 33.8809 17.1058 33.8809 17.0312C33.8809 16.9317 33.8809 16.4841 33.8809 16.1608C33.6926 12.779 31.4877 10.0934 27.4811 8.40243C27.4005 8.3527 27.2929 8.32783 27.1853 8.2781L26.4594 8.00456H26.3787C23.4746 6.98503 20.4899 6.81096 19.3605 6.76123C19.2798 6.76123 19.1992 6.76123 19.1454 6.76123H0V12.953H1.10244C1.10244 12.953 3.19985 12.953 4.06031 12.953V14.5694V16.5836V17.8518V21.0596C3.46874 21.0596 1.61337 21.0596 1.12936 21.0596H0.0268748V27.2514H19.1454C19.1454 27.2514 19.3067 27.2514 19.3874 27.2514C20.5436 27.2265 23.5284 27.0524 26.4324 26.0329L27.2123 25.7594H27.2391C27.2391 25.7594 27.4274 25.6599 27.508 25.635C31.5146 23.9441 33.7195 21.2585 33.9077 17.8766C33.9077 17.7523 33.9077 17.5534 33.9077 17.255M26.2711 17.056C26.2711 17.255 26.2174 17.6528 26.2174 17.7523C26.0829 18.8216 25.3569 19.6173 23.9855 20.1892C23.8511 20.2638 23.6897 20.3136 23.5015 20.3882C21.5385 21.0596 19.3874 21.0844 19.3605 21.0844H11.6432C11.6432 19.12 11.6432 14.9424 11.6432 12.9779H19.3605C19.3605 12.9779 21.5654 12.9779 23.5015 13.6742C23.6897 13.7488 23.8511 13.7985 23.9855 13.8731C25.3569 14.4699 26.0829 15.2656 26.2174 16.3349C26.2174 16.3846 26.2174 16.5836 26.2174 16.683C26.2174 16.7825 26.2174 16.882 26.2174 16.9566C26.2174 16.9814 26.2174 17.0312 26.2174 17.056"
                            fill="black" />
                        <path
                            d="M93.3078 6.81091L93.2808 11.6102H86.6391V17.802H81.4494V11.6102H74.7539V6.81091H81.4494V0.594238L86.6391 0.619105V6.81091H93.3078Z"
                            fill="none" />
                    </svg>

                </div>
            </div>
            <div class="feature-row highlightable">
                <div data-header="Feature"><span>Charger Type</span></div>
                <div data-header="DX+"><span>Easy Charge™</span></div>
                <div data-header="DX"><span>Off-Board Charger</span></div>
            </div>
            <div class="feature-row highlightable">
                <div data-header="Feature"><span>Estimated Range per charge</span></div>
                <div data-header="DX+"><span>116 km</span></div>
                <div data-header="DX"><span>102 km</span></div>
            </div>
            <div class="feature-row highlightable">
                <div data-header="Feature"><span>Peak Power</span></div>
                <div data-header="DX+"><span>4.8kw</span></div>
                <div data-header="DX"><span>4.7kw</span></div>
            </div>
            <div class="feature-row highlightable">
                <div data-header="Feature"><span>Telekinetic Feature</span></div>
                <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
            </div>
            <div class="feature-row highlightable">
                <div data-header="Feature"><span>Top Speed</span></div>
                <div data-header="DX+"><span>90km/hr</span></div>
                <div data-header="DX"><span>80km/hr</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Body</span></div>
                <div data-header="DX+"><span>Metal Body</span></div>
                <div data-header="DX"><span>Metal Body</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Headlight</span></div>
                <div data-header="DX+"><span>LED with Integrated Indicators</span></div>
                <div data-header="DX"><span>LED with Integrated Indicators</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>DRL</span></div>
                <div data-header="DX+"><span>Crystal LED Monogram</span></div>
                <div data-header="DX"><span>Crystal LED Monogram</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Illuminated Branding*</span></div>
                <div data-header="DX+"><span>LED Visor & Tail Lamp</span></div>
                <div data-header="DX"><span>LED Visor & Tail Lamp</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Cluster</span></div>
                <div data-header="DX+"><span>8.8-inch Iconic</span></div>
                <div data-header="DX"><span>8.8-inch Iconic</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Hazard Switch</span></div>
                <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                <div data-header="DX"><span><i class="fa fa-check"></i></span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Long Seat (Length)</span></div>
                <div data-header="DX+"><span>714mm</span></div>
                <div data-header="DX"><span>714mm</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Underseat storage</span></div>
                <div data-header="DX+"><span>37 ltr</span></div>
                <div data-header="DX"><span>37 ltr</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Easy Key*</span></div>
                <div data-header="DX+"><span>Truly Keyless</span></div>
                <div data-header="DX"><span>Truly Keyless</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Easy Flip (Footrest)</span></div>
                <div data-header="DX+"><span>Auto Opening</span></div>
                <div data-header="DX"><span>Auto Opening</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Quick Charge 0-80%</span></div>
                <div data-header="DX+"><span>3 Hrs</span></div>
                <div data-header="DX"><span>3 Hrs</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Battery</span></div>
                <div data-header="DX+"><span>LFP</span></div>
                <div data-header="DX"><span>LFP</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Battery Capacity (Installed)</span></div>
                <div data-header="DX+"><span>2.5 kwh</span></div>
                <div data-header="DX"><span>2.5 kwh</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Battery Capacity (Maximum)</span></div>
                <div data-header="DX+"><span>2.6 kwh</span></div>
                <div data-header="DX"><span>2.6 kwh</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>System Efficiency</span></div>
                <div data-header="DX+"><span>60V</span></div>
                <div data-header="DX"><span>60V</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Water & Dust protection</span></div>
                <div data-header="DX+"><span>IP 67</span></div>
                <div data-header="DX"><span>IP 67</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Motor</span></div>
                <div data-header="DX+"><span>BLDC Hub</span></div>
                <div data-header="DX"><span>BLDC Hub</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Riding modes</span></div>
                <div data-header="DX+"><span>Range | Power | Turbo</span></div>
                <div data-header="DX"><span>Range | Power | Turbo</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Cruise Control</span></div>
                <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                <div data-header="DX"><span><i class="fa fa-check"></i></span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Power Regen</span></div>
                <div data-header="DX+"><span>K-Coast Tech</span></div>
                <div data-header="DX"><span>K-Coast Tech</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Park Assist</span></div>
                <div data-header="DX+"><span>Reverse</span></div>
                <div data-header="DX"><span>Reverse</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Hill Hold</span></div>
                <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                <div data-header="DX"><span><i class="fa fa-check"></i></span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Front Suspension</span></div>
                <div data-header="DX+"><span>Telescopic</span></div>
                <div data-header="DX"><span>Telescopic</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Rear Suspension</span></div>
                <div data-header="DX+"><span>Adjustable Twin</span></div>
                <div data-header="DX"><span>Adjustable Twin</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Front Brake</span></div>
                <div data-header="DX+"><span>220mm Disc</span></div>
                <div data-header="DX"><span>220mm Disc</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Rear Brake</span></div>
                <div data-header="DX+"><span>130mm Drum – Combi</span></div>
                <div data-header="DX"><span>130mm Drum – Combi</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Front Tyre (Tubeless)</span></div>
                <div data-header="DX+"><span>100/80 – 12</span></div>
                <div data-header="DX"><span>100/80 – 12</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Rear Tyre (Tubeless)</span></div>
                <div data-header="DX+"><span>100/80 – 12</span></div>
                <div data-header="DX"><span>100/80 – 12</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Wheelbase</span></div>
                <div data-header="DX+"><span>1314mm</span></div>
                <div data-header="DX"><span>1314mm</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Ground Clearance</span></div>
                <div data-header="DX+"><span>165mm</span></div>
                <div data-header="DX"><span>165mm</span></div>
            </div>
            <div class="feature-row">
                <div data-header="Feature"><span>Bluetooth Connectivity</span></div>
                <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                <div data-header="DX"><span><i class="fa fa-check"></i></span></div>
            </div>
            <div class="feature-row">
                <div class="features-table">
                    <div class="feature-header highlightable">
                        <div data-header="Feature"><span>Telekinetic Features</span></div>
                        <div data-header><span></span></div>
                        <div data-header><span></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Find My Kinetic</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Track My Kinetic</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Anti Theft Alert</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Guide Me Home</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Seat & Charger Opening</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Geo Fencing Alert</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>Data Analytics</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                    <div class="feature-row highlightable">
                        <div data-header="Feature"><span>And Many More</span></div>
                        <div data-header="DX+"><span><i class="fa fa-check"></i></span></div>
                        <div data-header="DX"><span><i class="fa fa-close"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="downloads">
        <a href="/-/kinetic-ev-brochure.pdf" download="" class="btn-download">
            Download Brochure
            <i class="fa fa-download"></i>
        </a>
    </div>
    <div class="disclaimer">(*) marked features are subject to homologation approval.</div>
</section>

<script>
    document.getElementById('key-diff-toggle').addEventListener('change', function () {
        const highlightRows = document.querySelectorAll('.highlightable');
        const telekineticSection = document.getElementById('telekinetic-features');
        if (this.checked) {
            highlightRows.forEach(row => row.classList.add('highlight'));
        } else {
            highlightRows.forEach(row => row.classList.remove('highlight'));
        }
    });
</script>

<?php
endLayout([
    'modals' => [
        'promo' => [
            'title' => 'Are you interested in <strong>The Original Gangster</strong> ?',
            'form' => [
                'action' => '',
                'method' => 'post',
                'class' => 'popup-form' . (!empty($promo_class) ? ' ' . $promo_class : ''),
                'message' => !empty($promo_message) ? '<div class="form-message ' . $promo_class . '">' . $promo_message . '</div>' : '',
                'fields' => [
                    ['type' => 'text', 'name' => 'popup_name', 'placeholder' => 'Full Name', 'required' => true],
                    ['type' => 'tel', 'name' => 'popup_phone', 'placeholder' => '+91 0000 00 0000', 'required' => true],
                    [
                        'type' => 'select',
                        'name' => 'popup_variant',
                        'required' => true,
                        'options' => [
                            ['value' => '', 'text' => 'Select Variant', 'disabled' => true, 'selected' => true],
                            ['value' => 'variant1', 'text' => 'Variant 1'],
                            ['value' => 'variant2', 'text' => 'Variant 2']
                        ]
                    ],
                    ['type' => 'text', 'name' => 'popup_code', 'placeholder' => '000 000', 'required' => true]
                ],
                'submit' => ['name' => 'popup_promo_submit', 'value' => '1', 'text' => 'Get A Call Back']
            ],
            'data-perma-close' => 'true'
        ],
        'test-drive' => [
            'title' => 'Book your <strong>Kinetic Test Ride</strong>',
            'form' => [
                'action' => '',
                'method' => 'post',
                'class' => 'popup-form' . (!empty($popup_test_class) ? ' ' . $popup_test_class : ''),
                'message' => !empty($popup_test_message) ? '<div class="form-message ' . $popup_test_class . '">' . $popup_test_message . '</div>' : '',
                'fields' => [
                    ['type' => 'text', 'name' => 'test_name', 'placeholder' => 'Full Name', 'required' => true],
                    ['type' => 'tel', 'name' => 'test_phone', 'placeholder' => '+91 0000 00 0000', 'required' => true],
                    ['type' => 'text', 'name' => 'test_date', 'placeholder' => 'DD-MM-YYYY', 'onfocus' => "(this.type='date')", 'onblur' => "if(!this.value)this.type='text';", 'required' => true],
                    ['type' => 'text', 'name' => 'test_code', 'placeholder' => '000 000', 'required' => true]
                ],
                'submit' => ['name' => 'popup_test_submit', 'value' => '1', 'text' => "Let's Go"]
            ]
        ],
        'video-playlist' => [
            'type' => 'video-playlist',
            'videos' => [
                'easy-charge' => '/-/videos/easy-charge.mp4',
                'easy-flip' => '/-/videos/easy-flip.mp4',
                'storage-and-charging' => '/-/videos/storage-and-charging.mp4',
                'easy-key' => '/-/videos/easy-key.mp4',
                'my-kiney' => '/-/videos/my-kiney.mp4',
                'battery' => '/-/videos/battery.mp4'
            ]
        ]
    ]
]);
?>