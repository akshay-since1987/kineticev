<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Common head section for all pages
 * @param string $title - Page title
 * @param array $preload_images - Images to preload (optional)
 * @param string $description - Meta description (optional)
 * @param string $canonical - Canonical URL (optional)
 * @param array $additionalStyles - Additional CSS files to include (optional)
 * @param string $additionalScripts - Additional scripts to include in head (optional)
 */
function renderHead($title = "Kinetic, the OG Gangster!", $preload_images = [], $description = "", $canonical = "", $additionalStyles = [], $additionalScripts = "")
{
    // Handle case where parameters might be passed incorrectly
    if (is_array($title)) {
        // If title is an array, extract the actual title
        $title = isset($title['title']) ? $title['title'] : "Kinetic, the OG Gangster!";
        // If preload_images is also passed in the first parameter
        if (isset($title['preloadImages'])) {
            $preload_images = $title['preloadImages'];
        }
        // If additionalStyles is also passed in the first parameter
        if (isset($title['additionalStyles'])) {
            $additionalStyles = $title['additionalStyles'];
        }
    }

    // Ensure title is a string
    if (!is_string($title)) {
        $title = "Kinetic, the OG Gangster!";
    }

    // Ensure preload_images is an array
    if (!is_array($preload_images)) {
        $preload_images = [];
    }

    // Ensure additionalStyles is an array
    if (!is_array($additionalStyles)) {
        $additionalStyles = [];
    }

    // Default preload images
    if (empty($preload_images)) {
        $preload_images = [
            "/-/images/new/red/000144.png",
            "/-/images/new/black/000044.png"
        ];
    }
    ?>

    <head>
        <meta charset="UTF-8">
        <meta name="robots" content="index, follow">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <!-- Google Tag Manager -->

        <script>
            (function (w, d, s, l, i) {
                w[l] = w[l] || []; w[l].push({
                    'gtm.start':
                        new Date().getTime(), event: 'gtm.js'
                }); var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                        'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', 'GTM-T7Z97DWX');
        </script>

        <!-- End Google Tag Manager -->


        <?php if (!empty($description)): ?>
            <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
        <?php endif; ?>
        <?php if (!empty($canonical)): ?>
            <link rel="canonical" href="<?php echo htmlspecialchars($canonical); ?>" />
        <?php endif; ?>
        <!-- Google site verification -->
        <meta name="google-site-verification" content="abjgfd1I5cLhUIThDLW8Dj55Bzv2bHVf9oh7oACtSHk" />
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-QDCXDSVQJZ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', 'G-QDCXDSVQJZ');
        </script>

        <!-- Meta Pixel Code -->
        <script>
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
                n.queue = []; t = b.createElement(e); t.async = !0;
                t.src = v; s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '2513959039002878');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=2513959039002878&ev=PageView&noscript=1" /></noscript>
        <!-- End Meta Pixel Code -->
        <?php foreach ($preload_images as $image): ?>
            <link rel="preload" as="image" href="<?php echo htmlspecialchars($image); ?>" type="image/png">
        <?php endforeach; ?>
        <link rel="icon" type="image/x-icon" href="/-/images/logo.svg">
        <link rel="stylesheet" href="/css/main.css?v=<?php echo time(); ?>">
        <?php if (!empty($additionalStyles)): ?>
            <?php foreach ($additionalStyles as $styleUrl): ?>
                <link rel="stylesheet" href="<?php echo htmlspecialchars($styleUrl); ?>">
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($additionalScripts)): ?>
            <?php echo $additionalScripts; ?>
        <?php endif; ?>
    </head>
    <?php
}
?>