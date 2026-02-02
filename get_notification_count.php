<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['count' => 0]));
}

$conn->query("CREATE TABLE IF NOT EXISTS NOTIFICATIONS (
    NOTIFICATION_ID INT AUTO_INCREMENT PRIMARY KEY,
    USER_ID VARCHAR(20) NOT NULL,
    TITLE VARCHAR(100) NOT NULL,
    MESSAGE TEXT NOT NULL,
    TYPE ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    IS_READ BOOLEAN DEFAULT FALSE,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$count = $conn->query("SELECT COUNT(*) as count FROM NOTIFICATIONS 
                      WHERE USER_ID = '$user_id' 
                      AND IS_READ = 0")->fetch_assoc()['count'];

$user_id = $_SESSION['user_id'];
$count = $conn->query("SELECT COUNT(*) as count FROM NOTIFICATIONS WHERE USER_ID = '$user_id' AND IS_READ = 0")->fetch_assoc()['count'];

echo json_encode(['count' => $count]);
?>