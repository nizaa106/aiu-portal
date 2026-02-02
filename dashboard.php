<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch enrolled courses with progress
$courses_query = "SELECT c.COURSE_ID, c.COURSE_NAME, l.LECTURER_NAME, 
                  e.PROGRESS_PERCENT, c.CREDITS
                  FROM ENROLLMENT e 
                  JOIN COURSE c ON e.COURSE_ID = c.COURSE_ID 
                  JOIN LECTURER l ON c.LECTURER_ID = l.LECTURER_ID 
                  WHERE e.USER_ID = ? AND e.ENROLLMENT_STATUS = 'ACTIVE'";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$courses = $stmt->get_result();

// Calculate total study time (mock data based on progress)
function getStudyTime($progress) {
    $hours = floor($progress * 0.2);
    $mins = ($progress * 0.2 - $hours) * 60;
    return $hours . 'h ' . round($mins) . 'm';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - AIU Portal</title>
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
                    <li class="nav-item active" onclick="navigate('dashboard.php')">
                        <i class="fas fa-book-open"></i>
                        <span>My Courses</span>
                    </li>
                    <li class="nav-item" onclick="navigate('schedule.php')">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule</span>
                    </li>
                    <li class="nav-item" onclick="navigate('messages.php')">
                        <i class="fas fa-comment-alt"></i>
                        <span>Messages</span>
                    </li>
                    <li class="nav-item" onclick="navigate('settings.php')">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </li>
                    <!-- Add Event button for students - redirects to lecturer login -->
                    <li class="nav-item" onclick="navigate('login-lecturer.php')">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Add Event</span>
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
            <header class="top-header">
                <div class="portal-title">Student Portal</div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search resources...">
                    </div>
                    <div class="header-icons">
                        <!-- Message Icon -->
                        <div class="message-icon" style="position: relative; cursor: pointer;" onclick="window.location.href='messages.php'">
                            <i class="fas fa-envelope"></i>
                            <span class="message-badge" id="messageCount" style="display: none;">0</span>
                        </div>
                        
                        <!-- Notification Bell -->
                        <div class="notification-bell" onclick="showNotifications()" style="position: relative; cursor: pointer;">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </div>
                        
                        <div class="user-profile" style="cursor: pointer;">
                            <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                                <div class="user-role"><?php echo $_SESSION['department']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>My Courses</h1>
                        <p>Manage your academic progress and enrollments</p>
                    </div>
                    <button class="btn-primary" style="width: auto; padding: 12px 24px;" onclick="window.location.href='enroll_course.php'">
                        <i class="fas fa-plus"></i> Enroll New
                    </button>
                </div>

                <div class="card-grid">
                    <?php while ($course = $courses->fetch_assoc()): 
                        $progress = $course['PROGRESS_PERCENT'];
                        $study_time = getStudyTime($progress);
                    ?>
                    <div class="course-card">
                        <div class="card-header">
                            <i class="fas fa-book"></i>
                            <span class="badge">Active Course</span>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($course['COURSE_NAME']); ?></h3>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($course['LECTURER_NAME']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo $study_time; ?></span>
                            </div>
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span>Progress</span>
                                    <span><?php echo $progress; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <button class="btn-outline" onclick="window.location.href='course_detail.php?id=<?php echo $course['COURSE_ID']; ?>'">
                                Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($courses->num_rows == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-light);">
                        <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>No enrolled courses found.</p>
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
        
        // Profile click - go to settings page
        document.querySelector('.user-profile').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'settings.php';
        });

        // Notification functions
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