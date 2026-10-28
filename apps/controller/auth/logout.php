<?php
session_name('chiller_app');
session_start(); // Start the session if it's not already started

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page or homepage
header("Location: ../../index.php"); // Adjust this path as needed to redirect to your main page or login
exit;
