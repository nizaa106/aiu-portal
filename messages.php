<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch forums for user's enrolled courses
$forums_query = "SELECT f.*, c.COURSE_NAME 
                 FROM FORUM f 
                 JOIN COURSE c ON f.COURSE_ID = c.COURSE_ID 
                 JOIN ENROLLMENT e ON c.COURSE_ID = e.COURSE_ID 
                 WHERE e.USER_ID = ? AND f.IS_ACTIVE = TRUE";
$stmt = $conn->prepare($forums_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$forums = $stmt->get_result();

// Fetch messages from first forum (or specific forum if selected)
$forum_id = isset($_GET['forum_id']) ? intval($_GET['forum_id']) : 1;

$messages_query = "SELECT m.*, u.USER_ID as SENDER_ID, p.FIRST_NAME, p.LAST_NAME 
                   FROM MESSAGE m 
                   JOIN USER u ON m.SENDER_ID = u.USER_ID 
                   JOIN PROFILE p ON u.USER_ID = p.USER_ID 
                   WHERE m.COURSE_ID IN (SELECT COURSE_ID FROM FORUM WHERE FORUM_ID = ?)
                   ORDER BY m.MESSAGE_TIMESTAMP DESC LIMIT 20";
$stmt2 = $conn->prepare($messages_query);
$stmt2->bind_param("i", $forum_id);
$stmt2->execute();
$messages = $stmt2->get_result();

// Fetch contacts (other users in same courses)
$contacts_query = "SELECT DISTINCT u.USER_ID, p.FIRST_NAME, p.LAST_NAME 
                   FROM ENROLLMENT e1 
                   JOIN ENROLLMENT e2 ON e1.COURSE_ID = e2.COURSE_ID 
                   JOIN USER u ON e2.USER_ID = u.USER_ID 
                   JOIN PROFILE p ON u.USER_ID = p.USER_ID 
                   WHERE e1.USER_ID = ? AND e2.USER_ID != ?";
$stmt3 = $conn->prepare($contacts_query);
$stmt3->bind_param("ss", $user_id, $user_id);
$stmt3->execute();
$contacts = $stmt3->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="brand">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <h2>AIU e-Learning</h2>
                <span>- 2026 -</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item" onclick="navigate('dashboard.php')">
                        <i class="fas fa-book-open"></i><span>My Courses</span>
                    </li>
                    <li class="nav-item" onclick="navigate('schedule.php')">
                        <i class="fas fa-calendar-alt"></i><span>Schedule</span>
                    </li>
                    <li class="nav-item active" onclick="navigate('messages.php')">
                        <i class="fas fa-comment-alt"></i><span>Messages</span>
                    </li>
                    <li class="nav-item" onclick="navigate('settings.php')">
                        <i class="fas fa-cog"></i><span>Settings</span>
                    </li>
                </ul>
            </nav>
            <div class="sign-out" onclick="navigate('logout.php')">
                <i class="fas fa-sign-out-alt"></i><span>Sign Out</span>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="portal-title">Student Portal</div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search resources...">
                    </div>
                    <div class="header-icons">
    <!-- Message Icon - Links to messages page -->
    <div class="message-icon" style="position: relative; cursor: pointer;" onclick="window.location.href='messages.php'">
        <i class="fas fa-envelope"></i>
        <span class="message-badge" id="messageCount" style="display: none; position: absolute; top: -8px; right: -8px; background-color: #3b82f6; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;">0</span>
    </div>
    
    <!-- Notification Bell - Shows notifications popup -->
    <div class="notification-bell" onclick="showNotifications()" style="position: relative; cursor: pointer;">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" id="notificationCount" style="display: none; position: absolute; top: -8px; right: -8px; background-color: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;">0</span>
    </div>
    
    <div class="user-profile" style="cursor: pointer; position: relative; z-index: 100;">
        <div class="avatar" style="cursor: pointer;"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
        <div class="user-info" style="cursor: pointer;">
            <div class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
            <div class="user-role"><?php echo $_SESSION['department']; ?></div>
        </div>
        <i class="fas fa-chevron-down" style="font-size: 12px; cursor: pointer;"></i>
    </div>
</div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <div class="page-header">
                    <h1>Messages</h1>
                    <p>Connect with lecturers and peers</p>
                </div>

                <div class="messages-container">
                    <div class="contacts-sidebar">
                        <div class="search-contacts">
                            <input type="text" placeholder="Search contacts...">
                        </div>
                        <div class="contacts-list">
                            <?php while ($contact = $contacts->fetch_assoc()): 
                                $initials = substr($contact['FIRST_NAME'], 0, 1) . substr($contact['LAST_NAME'], 0, 1);
                            ?>
                            <div class="contact">
                                <div class="contact-avatar online"><?php echo $initials; ?></div>
                                <div class="contact-info">
                                    <div class="contact-name">
                                        <?php echo $contact['FIRST_NAME'] . ' ' . $contact['LAST_NAME']; ?>
                                        <span class="contact-time">10:30 AM</span>
                                    </div>
                                    <div class="contact-preview">Click to view conversation</div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="chat-area">
                        <div class="chat-header">
                            <div class="contact-avatar online">PJ</div>
                            <div class="chat-header-info">
                                <h4>Prof. John Doe</h4>
                                <span>Department Head • Online</span>
                            </div>
                        </div>
                        
                        <div class="chat-messages">
                            <?php while ($msg = $messages->fetch_assoc()): 
                                $is_me = ($msg['SENDER_ID'] == $user_id);
                            ?>
                            <div class="message <?php echo $is_me ? 'sent' : 'received'; ?>">
                                <?php echo nl2br(htmlspecialchars($msg['MESSAGE_CONTENT'])); ?>
                                <div class="message-time"><?php echo date('h:i A', strtotime($msg['MESSAGE_TIMESTAMP'])); ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <div class="chat-input">
                            <input type="text" placeholder="Type a message..." id="messageInput">
                            <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function navigate(url) { window.location.href = url; }
        
       document.querySelector('.user-profile').addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = 'settings.php';
});

// Notification functions (same as dashboard.php)
function showNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const popup = document.createElement('div');
            popup.className = 'notification-popup';
            popup.innerHTML = `
                <div style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0;">Notifications</h4>
                        <button onclick="markAllAsRead()" style="background: none; border: none; color: var(--navy); cursor: pointer;">Mark all read</button>
                    </div>
                    <div id="notificationList">
                        ${data.notifications.length > 0 ? 
                            data.notifications.map(n => `
                                <div class="notification-item ${n.is_read ? 'read' : 'unread'}" onclick="markAsRead(${n.id})">
                                    <div style="font-weight: 600;">${n.title}</div>
                                    <div style="font-size: 13px; color: var(--text-light);">${n.message}</div>
                                    <div style="font-size: 11px; color: var(--text-light); margin-top: 4px;">${n.time}</div>
                                </div>
                            `).join('') : 
                            '<div style="padding: 20px; text-align: center; color: var(--text-light);">No notifications</div>'
                        }
                    </div>
                </div>
            `;
            
            popup.style.cssText = 'position: absolute; top: 60px; right: 80px; z-index: 10000;';
            
            document.addEventListener('click', function closePopup(e) {
                if (!popup.contains(e.target) && !e.target.classList.contains('fa-bell') && !e.target.closest('.notification-bell')) {
                    popup.remove();
                    document.removeEventListener('click', closePopup);
                }
            });
            
            document.body.appendChild(popup);
        });
}

function updateNotificationCount() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationCount');
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
}

function updateMessageCount() {
    fetch('get_message_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('messageCount');
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
}

function markAsRead(notificationId) {
    fetch('mark_notification_read.php?id=' + notificationId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                showNotifications();
            }
        });
}

function markAllAsRead() {
    fetch('mark_all_read.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                showNotifications();
            }
        });
}

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    updateMessageCount();
    updateNotificationCount();
});
    </script>
</body>
</html>