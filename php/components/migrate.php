<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Migration helper script to refactor PHP files to use components
 * 
 * Usage: php migrate_to_components.php filename.php
 */

if ($argc < 2) {
    echo "Usage: php migrate_to_components.php filename.php\n";
    exit(1);
}

$filename = $argv[1];
$filepath = __DIR__ . '/' . $filename;

if (!file_exists($filepath)) {
    echo "File not found: $filepath\n";
    exit(1);
}

$content = file_get_contents($filepath);

// Extract title from existing file
preg_match('/<title>(.*?)<\/title>/', $content, $titleMatches);
$title = $titleMatches[1] ?? 'Page Title';

// Extract preload images if any
preg_match_all('/<link rel="preload"[^>]*href="([^"]*)"[^>]*>/', $content, $preloadMatches);
$preloadImages = $preloadMatches[1] ?? [];

// Find the start of actual content (after header)
$headerEndPattern = '</header>';
$headerEndPos = strpos($content, $headerEndPattern);

if ($headerEndPos === false) {
    echo "Could not find header end in file\n";
    exit(1);
}

$contentStart = $headerEndPos + strlen($headerEndPattern);

// Find the start of footer
$footerStartPattern = '<footer class="site-footer">';
$footerStartPos = strpos($content, $footerStartPattern);

if ($footerStartPos === false) {
    echo "Could not find footer start in file\n";
    exit(1);
}

// Extract the main content
$mainContent = substr($content, $contentStart, $footerStartPos - $contentStart);
$mainContent = trim($mainContent);

// Generate the new file content
$newContent = "<?php\n";
$newContent .= "require_once 'components/layout.php';\n\n";

if (!empty($preloadImages)) {
    $newContent .= "\$preload_images = [\n";
    foreach ($preloadImages as $image) {
        $newContent .= "    \"$image\",\n";
    }
    $newContent .= "];\n\n";
    
    $newContent .= "startLayout(\"$title\", [\n";
    $newContent .= "    'preload_images' => \$preload_images\n";
    $newContent .= "]);\n";
} else {
    $newContent .= "startLayout(\"$title\");\n";
}

$newContent .= "?>" . $mainContent . "\n";
$newContent .= "<?php endLayout(); ?>";

// Create backup of original file
$backupPath = str_replace('.php', '-old.php', $filepath);
if (!file_exists($backupPath)) {
    copy($filepath, $backupPath);
    echo "Created backup: $backupPath\n";
}

// Write the new content
file_put_contents($filepath, $newContent);

echo "Successfully refactored: $filename\n";
echo "Title: $title\n";
echo "Preload images: " . count($preloadImages) . "\n";

?>
