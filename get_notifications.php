<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['notifications' => []]));
}

$user_id = $_SESSION['user_id'];
$notifications = $conn->query("SELECT * FROM NOTIFICATIONS WHERE USER_ID = '$user_id' ORDER BY CREATED_AT DESC LIMIT 10");

$result = [];
while($row = $notifications->fetch_assoc()) {
    $result[] = [
        'id' => $row['NOTIFICATION_ID'],
        'title' => $row['TITLE'],
        'message' => $row['MESSAGE'],
        'is_read' => $row['IS_READ'],
        'time' => date('M j, g:i A', strtotime($row['CREATED_AT']))
    ];
}

echo json_encode(['notifications' => $result]);
?>