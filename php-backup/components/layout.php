<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Main layout wrapper that includes all common components
 * @param string $title - Page title
 * @param array $preload_images - Images to preload
 * @param string $book_btn_text - Text for book button
 * @param string $book_btn_link - Link for book button
 * @param string $body_theme - CSS custom property for body theme
 * @param bool $include_test_drive_modal - Include test drive modal
 * @param bool $include_video_modal - Include video playlist modal
 */

// Include all component files
require_once __DIR__ . '/head.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';
require_once __DIR__ . '/modals.php';
require_once __DIR__ . '/scripts.php';

function startLayout($title = "Kinetic, the OG Gangster!", $options = [])
{
    $defaults = [
        'preload_images' => [],
        'description' => '',
        'canonical' => '',
        'additionalStyles' => [],
        'book_btn_text' => 'Book Now',
        'book_btn_link' => '/book-now',
        'header_config' => [],
        'body_theme' => '#eaeaea',
        'include_test_drive_modal' => true,
        'include_video_modal' => true
    ];

    $options = array_merge($defaults, $options);

    // Handle header configuration
    $book_btn_text = $options['book_btn_text'];
    $book_btn_link = $options['book_btn_link'];

    // Override with header_config if provided
    if (!empty($options['header_config'])) {
        if (isset($options['header_config']['book_btn_text'])) {
            $book_btn_text = $options['header_config']['book_btn_text'];
        }
        if (isset($options['header_config']['book_btn_link'])) {
            $book_btn_link = $options['header_config']['book_btn_link'];
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <?php renderHead($title, $options['preload_images'], $options['description'], $options['canonical'], $options['additionalStyles']); ?>

    <body style="--body-theme: <?php echo htmlspecialchars($options['body_theme']); ?>">
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T7Z97DWX" height="0" width="0"
                style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16865651815"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', 'AW-16865651815');
        </script>
        <main>
            <?php renderHeader(); ?>
            <?php
}

function endLayout($options = [])
{
    $defaults = [
        'include_test_drive_modal' => true,
        'include_video_modal' => true
    ];

    $options = array_merge($defaults, $options);
    ?>
            <?php renderFooter(); ?>
        </main>

        <?php renderModals($options['include_test_drive_modal'], $options['include_video_modal']); ?>
        <?php renderScripts(); ?>
    </body>

    </html>
    <?php
}
?>