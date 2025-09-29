<?php
// Redirect choose-variant to the merged book-now page
// This page has been merged with book-now.php for a streamlined booking experience
header('HTTP/1.1 301 Moved Permanently');
header('Location: /book-now');
exit();
?>
