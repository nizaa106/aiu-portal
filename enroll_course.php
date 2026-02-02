<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';

// Get available courses not enrolled
$available = $conn->prepare("SELECT c.*, l.LECTURER_NAME 
    FROM COURSE c 
    JOIN LECTURER l ON c.LECTURER_ID = l.LECTURER_ID
    WHERE c.COURSE_ID NOT IN (SELECT COURSE_ID FROM ENROLLMENT WHERE USER_ID = ?)");
$available->bind_param("s", $user_id);
$available->execute();
$courses = $available->get_result();

// Handle enrollment
if (isset($_GET['enroll'])) {
    $course_id = sanitize($conn, $_GET['enroll']);
    
    $enroll = $conn->prepare("INSERT INTO ENROLLMENT (USER_ID, COURSE_ID, PROGRESS_PERCENT) VALUES (?, ?, 0)");
    $enroll->bind_param("ss", $user_id, $course_id);
    
    if ($enroll->execute()) {
        // Auto-add events to timetable
        $events = $conn->prepare("SELECT EVENT_ID FROM EVENTS WHERE COURSE_ID = ?");
        $events->bind_param("s", $course_id);
        $events->execute();
        $ev_result = $events->get_result();
        
        while($ev = $ev_result->fetch_assoc()) {
            $tt = $conn->prepare("INSERT INTO TIMETABLE (USER_ID, EVENT_ID) VALUES (?, ?)");
            $tt->bind_param("si", $user_id, $ev['EVENT_ID']);
            $tt->execute();
        }
        
        $success = "Successfully enrolled!";
        header("Location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>AIU e-Learning</h2>
                <span>- 2026 -</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item" onclick="navigate('dashboard.php')">
                        <i class="fas fa-book-open"></i>
                        <span>My Courses</span>
                    </li>
                    <li class="nav-item active" onclick="navigate('enroll_course.php')">
                        <i class="fas fa-plus"></i>
                        <span>Enroll New</span>
                    </li>
                    <li class="nav-item" onclick="navigate('login-lecturer.php')">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Admin: Add Event</span>
                    </li>
                </ul>
            </nav>
            <div class="sign-out" onclick="navigate('logout.php')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="top-header">
                <div class="portal-title">Course Enrollment</div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search courses...">
                    </div>
                    <div class="header-icons">
                        <div class="message-icon" onclick="window.location.href='messages.php'" style="position: relative; cursor: pointer;">
                            <i class="fas fa-envelope"></i>
                            <span class="message-badge" id="messageCount" style="display: none;">0</span>
                        </div>
                        
                        <div class="notification-bell" onclick="showNotifications()" style="position: relative; cursor: pointer;">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </div>
                        
                        <div class="user-profile" onclick="window.location.href='settings.php'" style="cursor: pointer;">
                            <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                                <div class="user-role"><?php echo $_SESSION['department']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>Available Courses</h1>
                        <p>Click "Enroll" to add a course to your dashboard</p>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="card-grid">
                    <?php while($course = $courses->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="card-header">
                            <i class="fas fa-book"></i>
                            <span class="badge">Available</span>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($course['COURSE_NAME']); ?></h3>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($course['LECTURER_NAME']); ?></span>
                                <span><i class="fas fa-star"></i> <?php echo $course['CREDITS']; ?> Credits</span>
                            </div>
                            <p style="color: var(--text-light); font-size: 14px; margin-bottom: 20px; line-height: 1.5;">
                                <?php echo substr(htmlspecialchars($course['COURSE_DESCRIPTION']), 0, 100); ?>...
                            </p>
                            <a href="?enroll=<?php echo $course['COURSE_ID']; ?>" class="btn-primary" style="text-decoration: none; display: block; text-align: center;">
                                <i class="fas fa-plus"></i> Enroll Now
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($courses->num_rows == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-light);">
                        <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px; color: var(--gold);"></i>
                        <p>You're enrolled in all available courses!</p>
                        <a href="dashboard.php" class="btn-primary" style="margin-top: 20px; display: inline-block; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function navigate(url) { 
            window.location.href = url; 
        }
        
        function showNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const popup = document.createElement('div');
                    popup.style.cssText = 'position: absolute; top: 60px; right: 20px; z-index: 10000; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto;';
                    popup.innerHTML = `
                        <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0; color: var(--navy);">Notifications</h4>
                            <button onclick="this.closest('.notification-popup').remove()" style="background: none; border: none; color: var(--text-light); cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div>
                            ${data.notifications && data.notifications.length > 0 ? 
                                data.notifications.map(n => `
                                    <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); cursor: pointer;">
                                        <div style="font-weight: 600; color: var(--navy); font-size: 13px;">${n.title}</div>
                                        <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">${n.message}</div>
                                    </div>
                                `).join('') : 
                                '<div style="padding: 30px; text-align: center; color: var(--text-light);"><i class="fas fa-bell-slash" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i><p>No notifications</p></div>'
                            }
                        </div>
                    `;
                    popup.className = 'notification-popup';
                    document.body.appendChild(popup);
                    
                    setTimeout(() => {
                        document.addEventListener('click', function closePopup(e) {
                            if (!popup.contains(e.target) && !e.target.closest('.notification-bell')) {
                                popup.remove();
                                document.removeEventListener('click', closePopup);
                            }
                        });
                    }, 100);
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
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

        // Load counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMessageCount();
            updateNotificationCount();
        });
    </script>
</body>
</html>