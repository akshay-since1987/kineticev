<?php
require_once 'components/layout.php';

$preload_images = [
    "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Delivery Policy", [
    'preload_images' => $preload_images,
    'body_theme' => '#eaeaea',
    'header_config' => [
        'book_btn_text' => 'Book Now',
        'book_btn_link' => '/book-now'
    ]
]);
?>
<div class="delivery-policy">
    <section class="intro">
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <br />
                <br />
                <div class="intro-heading">
                    <p>Delivery</p>
                    <h2>Policy</h2>
                </div>
            </div>
        </div>
    </section>
    <div class="delivery-policy-text container">
        <ul>
            <li>
                <p>
                    Thank you for choosing us. We are committed to providing you with a smooth and transparent delivery
                    experience. Please take a moment to review our Delivery Policy:
                </p>
            </li>
            <li>
                <h2>Pre-Booking & Dispatch Timeline</h2>
                <p>
                    All orders are processed on a pre-booking basis. Once a customer successfully completes the
                    pre-booking, the order enters our production or fulfillment pipeline.
                </p>
                <p>
                    <strong>Delivery Timeline:</strong> Please allow up to 30 days from the date of pre-booking for
                    delivery across Pan India.
                </p>
            </li>
            <li>
                <h2>Shipping Method</h2>
                <p>
                    We partner with reliable courier services to ensure timely and secure delivery to your location.
                    Once your order is dispatched, you will receive a shipping confirmation email/SMS with tracking
                    details.
                </p>
            </li>
            <li>
                <h2>Delivery Coverage</h2>
                <p>
                    We offer delivery to all serviceable pin codes within India. If your pin code is non-serviceable,
                    our team will contact you for alternative arrangements.
                </p>
            </li>
            <li>
                <h2>Delays & Exceptions</h2>
                <p>
                    While we aim to deliver all orders within the specified 30-day timeframe, external factors such as
                    natural calamities, strikes, government restrictions, or logistical challenges may cause delays. In
                    such rare cases, we will keep you informed and work towards a swift resolution.
                </p>
            </li>
            <li>
                <h2>Customer Support</h2>
                <p>
                    For any queries regarding your order status, shipping, or tracking, feel free to reach out to our
                    customer support team at:<br>
                    📧 <a href="mailto:youremail@example.com">info@kineticev.in</a><br>
                    📞 <a href="tel:+918600096800">+91 86000 96800</a>
                </p>
            </li>
        </ul>
    </div>
</div>
<?php endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>