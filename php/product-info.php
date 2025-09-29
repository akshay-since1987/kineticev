<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php';
require_once 'components/layout.php';

$preload_images = [
     "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Product Info", [
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
                <a href="#" class="back-arrow">←</a>
                <div class="intro-heading">
                    <p>Product</p>
                    <h2>Information</h2>
                </div>
            </div>
        </div>
    </section>
    <div class="delivery-policy-text container">
        <ul>
            <li>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <img src="/-/images/blue bike 1.png" alt="Kinetic EV" />
                    <div class="product-info">
                        <h3>Kinetic EV</h3>
                        <p>
                            India's first gearless scooter that redefined urban mobility.
                        </p>
                        <p>
                            Equipped with electric start and a smooth automatic transmission
                        </p>
                        <p>
                            A pioneer in fuel efficiency and user-friendly design.
                        </p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>
<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php'; endLayout(['include_test_drive_modal' => true, 'include_video_modal' => true]); ?>
