<?php
require_once 'config.php';

function sendClashNotification($clash_details) {
    global $conn;
    
    // Send to all admins
    $admins = $conn->query("SELECT USER_ID FROM USER_ROLE WHERE ROLE_ID = 3"); // Assuming ROLE_ID 3 = Admin
    
    while($admin = $admins->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO NOTIFICATIONS (USER_ID, TITLE, MESSAGE, TYPE, IS_READ) 
                               VALUES (?, 'Schedule Conflict Detected', ?, 'warning', 0)");
        $stmt->bind_param("ss", $admin['USER_ID'], $clash_details);
        $stmt->execute();
    }
}

function sendEventCreatedNotification($event_id, $title, $start_time, $location) {
    global $conn;
    
    // Notify lecturer of the course
    $lecturer = $conn->prepare("SELECT u.USER_ID FROM USER u 
                               JOIN COURSE c ON u.USER_ID = c.LECTURER_ID 
                               WHERE c.COURSE_ID = ?");
    $lecturer->bind_param("s", $course_id);
    $lecturer->execute();
    $result = $lecturer->get_result();
    
    if($lecturer_data = $result->fetch_assoc()) {
        $message = "New event created: $title at $location on " . date('F j, Y', strtotime($start_time));
        
        $stmt = $conn->prepare("INSERT INTO NOTIFICATIONS (USER_ID, TITLE, MESSAGE, TYPE, IS_READ) 
                               VALUES (?, 'Event Scheduled', ?, 'info', 0)");
        $stmt->bind_param("ss", $lecturer_data['USER_ID'], $message);
        $stmt->execute();
    }
}
?>