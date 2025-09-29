<?php
require_once 'components/layout.php';

$preload_images = [
    "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Terms and Conditions", [
    'preload_images' => $preload_images,
    'body_theme' => '#eaeaea',
    'header_config' => [
        'book_btn_text' => 'Book Now',
        'book_btn_link' => '/book-now'
    ]
]);
?>
<div class="terms">
    <section class="intro">
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <br />
                <br />
                <div class="intro-heading">
                    <p>Terms and </p>
                    <h2>Conditions</h2>
                </div>
            </div>
        </div>
    </section>
    <div class="terms-text container">
        <div>
            <section>
                <h3>Introduction and Acceptance</h3>
                <p>These Terms & Conditions ("Terms") govern your access and use of the website(s), forum(s), mobile
                    application(s)
                    and services offered by Kinetic Watts & Volts Ltd. (hereinafter referred to as "Kinetic", "We", "Us"
                    or
                    "Company"). By using any of our platforms, registering an account, or engaging in transactions, you
                    ("You" or
                    "User") agree to be legally bound by these Terms and by our linked policies.</p>
            </section>
            <section>
                <h3>Eligibility</h3>
                <p>Our services are available only to individuals who can form legally binding contracts under
                    applicable
                    law.
                    Persons under 18 years of age or otherwise legally incompetent may not use or register for an
                    account;
                    services
                    may only be accessed by their legal guardian.</p>
            </section>
            <section>
                <h3>Account Registration & Security</h3>
                <p>To access certain features, you must register and create an account using accurate and current
                    information. You
                    are responsible for maintaining the confidentiality of your account credentials, and liable for any
                    actions
                    taken using your account. You agree to notify us immediately if your credentials are compromised.
                </p>
            </section>
            <section>
                <h3>Privacy and Data Protection</h3>
                <p>Your personal data is collected, used, stored, and disclosed according to the Privacy Policy, which
                    is
                    incorporated herein by reference. Please review it carefully before proceeding.</p>
            </section>
            <section>
                <h3>Fees and Charges</h3>
                <p>Browsing the Kinetic website or forum is free. Fees may apply for certain services or subscriptions,
                    and
                    any
                    changes in fee policy will be posted online and become effective immediately. All fees are quoted in
                    Indian
                    Rupees unless otherwise specified.</p>
            </section>
            <section>
                <h3>Permitted Use & Intellectual Property</h3>
                <p>We grant you a limited, nonexclusive, personal license to access and use our services for your own
                    non-commercial
                    purposes. You may not copy, reproduce, distribute, modify, or extract data, content or intellectual
                    property for
                    commercial use or resale. Automated extraction tools (e.g., bots, scrapers) are prohibited.</p>
            </section>
            <section>
                <h3>Acceptable Use</h3>
                <p>You agree not to use our services in any manner that disrupts, damages, or impairs them, or infringes
                    upon the
                    rights of others. All activities must comply with applicable laws. You must not introduce malware,
                    abuse
                    user
                    privacy, or attempt unauthorized access.</p>
            </section>
            <section>
                <h3>Termination & Suspension</h3>
                <p>Kinetic reserves the right to suspend or terminate your access to the services at any time, with or
                    without cause
                    or notice, including if you breach these Terms.</p>
            </section>
            <section>
                <h3>Disclaimers & Limitation of Liability</h3>
                <p>You agree that our services are provided "as is" and "as available". We expressively disclaim all
                    warranties,
                    whether express or implied, including merchantability or fitness for a particular purpose. Our total
                    liability
                    shall not exceed ₹1000 (booking amount or similar), and we are not liable for any indirect,
                    incidental
                    or
                    consequential damages.</p>
            </section>
            <section>
                <h3>Governing Law & Jurisdiction</h3>
                <p>These Terms are governed by the laws of India. All disputes shall be subject to the exclusive
                    jurisdiction of
                    courts located in Bengaluru, Karnataka unless otherwise agreed in writing.</p>
            </section>
        </div>
    </div>
</div>
<?php endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>