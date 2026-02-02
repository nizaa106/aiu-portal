<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die(json_encode(['success' => false]));
}

$notification_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE NOTIFICATION_ID = ? AND USER_ID = ?");
$stmt->bind_param("is", $notification_id, $user_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>