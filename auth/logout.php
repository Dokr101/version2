<?php
require_once '../includes/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location:/version2/homepage.php");
exit();
?>