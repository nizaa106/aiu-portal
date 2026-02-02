<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];

// Insert a test notification
$stmt = $conn->prepare("INSERT INTO NOTIFICATIONS (USER_ID, TITLE, MESSAGE, TYPE) VALUES (?, 'Welcome!', 'Welcome to AIU Course Compass. You have 3 new messages.', 'info')");
$stmt->bind_param("s", $user_id);
$stmt->execute();

echo "Test notification created!";
?>