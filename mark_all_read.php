<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false]));
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE NOTIFICATIONS SET IS_READ = 1 WHERE USER_ID = ?");
$stmt->bind_param("s", $user_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>