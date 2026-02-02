<?php
// InfinityFree Database Configuration
define('DB_HOST', 'sqlxxx.infinityfree.com');  // ← Your InfinityFree hostname
define('DB_USERNAME', 'if0_36482345_admin');   // ← Your InfinityFree MySQL username
define('DB_PASSWORD', 'AiUPortal2024');   // ← Your MySQL password (NOT empty!)
define('DB_NAME', 'if0_36482345_aiu_course_compass'); // ← Your database name with prefix

// Create database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . 
        "<br><br>Check your InfinityFree database credentials:<br>" .
        "Host: " . DB_HOST . "<br>" .
        "Username: " . DB_USERNAME . "<br>" .
        "Database: " . DB_NAME);
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

// Session configuration for InfinityFree (ADD THIS!)
ini_set('session.save_path', '/tmp');
ini_set('session.gc_maxlifetime', 3600);

// Start session for all pages - only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to sanitize input - only declare if not already exists
if (!function_exists('sanitize')) {
    function sanitize($conn, $data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $conn->real_escape_string($data);
    }
}

// Debug message (remove after testing)
// echo "✅ Connected to InfinityFree database successfully!<br>";
?>