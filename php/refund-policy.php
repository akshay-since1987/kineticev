<?php
require_once 'components/layout.php';

$preload_images = [
     "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Refund Policy", [
    'preload_images' => $preload_images,
    'body_theme' => '#eaeaea',
    'header_config' => [
        'book_btn_text' => 'Book Now',
        'book_btn_link' => '/book-now'
    ]
]);
?>
<div class="refund-policy">
    <section class="intro">
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <br/>
                <br/>
                <div class="intro-heading">
                    <p>Refund</p>
                    <h2>Policy</h2>
                </div>
            </div>
        </div>
    </section>
    <div class="refund-policy-text container">
        <ul>
            <li>
                <ul>
                    <li>If you have booked a vehicle without opting for charging equipment installation, the booking
                        amount is
                        fully
                        refundable until you fully pay for the vehicle.</li>
                    <li>If you opted for installation of charging equipment, the booking amount is refundable only until
                        the point
                        that
                        installation begins. Once charging equipment has been installed, no refund is provided.</li>
                </ul>
            </li>
            <li>
                <h2>Cancellation Terms</h2>
                <ul>
                    <li>You may cancel your booking prior to full payment or prior to equipment installation (if
                        applicable).</li>
                    <li>After full payment is made, cancellations or refunds are not accepted.</li>
                    <li>In case of cancellation after installation of charging equipment, Kinetic reserves the right to
                        uninstall
                        and
                        retrieve the unit, and adjust any refund amount against associated costs.</li>
                </ul>
            </li>
            <li>
                <h2>Cancellation Timeframe</h2>
                <ul>
                    <li>You may cancel within 15 days from making the payment.</li>
                </ul>
            </li>
            <li>
                <h2>Refund Processing Timeline</h2>
                <p>All eligible refunds will be processed and credited within 15 working days from the date your cancellation request
                    is
                    received
                    or from the date we notify you of cancellation.</p>
            </li>
            <li>
                <h2>Scope of Refund</h2>
                <ul>
                    <li>Only the original booking amount is eligible for refund.</li>
                    <li>Any taxes, fees or charges collected beyond the booking fee are nonrefundable once full payment
                        has been
                        made.</li>
                </ul>
            </li>
        </ul>
    </div>
</div>
<?php endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>
