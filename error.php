<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Error Debug</h1>";

// Test basic PHP
echo "PHP is working: " . phpversion() . "<br>";

// Test database connection
include('config.php'); // This will show the actual error!
?>