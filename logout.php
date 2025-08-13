<?php
session_start();

// Destroy session
session_unset();
session_destroy();

// No-cache headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: login.php");
exit;
?>
