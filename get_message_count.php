<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['count' => 0]));
}

$user_id = $_SESSION['user_id'];

// Count unread messages (adjust query based on your messages table structure)
// If you have a MESSAGE table with IS_READ field:
$count = $conn->query("SELECT COUNT(*) as count FROM MESSAGE 
                      WHERE RECEIVER_ID = '$user_id' 
                      AND IS_READ = 0")->fetch_assoc()['count'];

// OR if you don't have read status yet, count all messages:
// $count = $conn->query("SELECT COUNT(*) as count FROM MESSAGE 
//                       WHERE RECEIVER_ID = '$user_id'")->fetch_assoc()['count'];

echo json_encode(['count' => $count]);
?>