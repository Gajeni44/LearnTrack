<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the physical session file on the server
session_destroy();

// 3. Redirect the user back to the login page
header("Location: index.php");
exit();
?>