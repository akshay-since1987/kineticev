<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Common scripts section for all pages
 */
function renderScripts() {
?>
<!-- Main JavaScript bundle with all dependencies included -->
<script src="/js/main.js?v=<?php echo time(); ?>" type="application/javascript"></script>

<?php
}
?>
