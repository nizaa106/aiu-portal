<?php
require_once 'config.php';

// Clear remember me token from database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM REMEMBER_TOKENS WHERE USER_ID = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
}

// Clear remember me cookie
setcookie('aiu_remember', '', time() - 3600, '/');

// Clear session
session_unset();
session_destroy();

header("Location: index.php");
exit();
?>