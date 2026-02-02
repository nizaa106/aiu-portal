<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user data
$user_query = "SELECT u.*, p.* FROM USER u 
               LEFT JOIN PROFILE p ON u.USER_ID = p.USER_ID 
               WHERE u.USER_ID = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($conn, $_POST['first_name']);
    $last_name = sanitize($conn, $_POST['last_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    
    // Update profile
    $update_profile = $conn->prepare("UPDATE PROFILE SET FIRST_NAME = ?, LAST_NAME = ?, PHONE_NUM = ? WHERE USER_ID = ?");
    $update_profile->bind_param("ssss", $first_name, $last_name, $phone, $user_id);
    
    if ($update_profile->execute()) {
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $success = "Profile updated successfully!";
        
        // Log the update
        $log = $conn->prepare("INSERT INTO AUDIT_LOG (USER_ID, ACTION_TYPE, TARGET_ENTITY, DESCRIPTION) 
                              VALUES (?, 'UPDATE', 'PROFILE', 'User updated profile information')");
        $log->bind_param("s", $user_id);
        $log->execute();
    } else {
        $error = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; border-left: 3px solid #28a745; }
        .alert-error { background-color: #f8d7da; color: #721c24; border-left: 3px solid #dc3545; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
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
                    <li class="nav-item" onclick="navigate('messages.php')">
                        <i class="fas fa-comment-alt"></i><span>Messages</span>
                    </li>
                    <li class="nav-item active" onclick="navigate('settings.php')">
                        <i class="fas fa-cog"></i><span>Settings</span>
                    </li>
                </ul>
            </nav>
            <div class="sign-out" onclick="navigate('logout.php')">
                <i class="fas fa-sign-out-alt"></i><span>Sign Out</span>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="top-header">
                <div class="portal-title">Student Portal</div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search resources...">
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
                <div class="page-header">
                    <h1>Account Settings</h1>
                    <p>Manage your profile and preferences</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="settings-container">
                    <div class="settings-nav">
                        <div class="settings-nav-item active" onclick="showTab('profile')">Profile</div>
                        <div class="settings-nav-item" onclick="showTab('notifications')">Notifications</div>
                        <div class="settings-nav-item" onclick="showTab('privacy')">Privacy</div>
                        <div class="settings-nav-item" onclick="showTab('security')">Security</div>
                    </div>

                    <div class="settings-content">
                        <div id="profile" class="settings-section active">
                            <h3><i class="fas fa-user" style="color: var(--gold); margin-right: 10px;"></i> Personal Information</h3>
                            
                            <form method="POST" action="">
                                <div class="avatar-upload" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 20px; background: var(--bg); border-radius: 12px;">
                                    <div class="avatar-large" style="width: 80px; height: 80px; background: var(--navy); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 700;"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
                                    <div class="avatar-actions">
                                        <button type="button" class="btn-secondary" style="padding: 8px 16px; border: 1px solid var(--border); background: white; border-radius: 6px; cursor: pointer;">Change Avatar</button>
                                        <span class="avatar-hint" style="display: block; margin-top: 8px; color: var(--text-light); font-size: 12px;">JPG, GIF or PNG. Max size of 800K</span>
                                    </div>
                                </div>

                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy);">First Name</label>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['FIRST_NAME'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy);">Last Name</label>
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['LAST_NAME'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
                                    </div>
                                </div>

                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy);">Email Address</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['EMAIL'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy);">Phone Number</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['PHONE_NUM'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 30px;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy);">Department</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user_data['DEPARTMENT'] ?? ''); ?>" disabled style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background-color: var(--bg); color: var(--text-light);">
                                </div>

                                <div class="settings-footer" style="display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1px solid var(--border);">
                                    <button type="button" class="btn-text" onclick="location.reload()" style="padding: 10px 20px; border: none; background: transparent; color: var(--text-light); cursor: pointer;">Cancel</button>
                                    <button type="submit" class="btn-primary" style="padding: 10px 24px; background: var(--gold); color: var(--navy); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div id="notifications" class="settings-section" style="display: none;">
                            <h3><i class="fas fa-bell" style="color: var(--gold); margin-right: 10px;"></i> Notifications</h3>
                            <p style="color: var(--text-light);">Notification settings would be implemented here with database integration.</p>
                        </div>
                        
                        <div id="privacy" class="settings-section" style="display: none;">
                            <h3><i class="fas fa-lock" style="color: var(--gold); margin-right: 10px;"></i> Privacy</h3>
                            <p style="color: var(--text-light);">Privacy settings would be implemented here.</p>
                        </div>
                        
                        <div id="security" class="settings-section" style="display: none;">
                            <h3><i class="fas fa-shield-alt" style="color: var(--gold); margin-right: 10px;"></i> Security</h3>
                            <p style="color: var(--text-light);">Security settings would be implemented here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function navigate(url) { window.location.href = url; }
        
        function showTab(tabName) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(section => {
                section.style.display = 'none';
            });
            // Remove active class from all nav items
            document.querySelectorAll('.settings-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            // Show selected section
            document.getElementById(tabName).style.display = 'block';
            // Add active class to clicked nav item
            event.target.classList.add('active');
        }
        
        function showNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const popup = document.createElement('div');
                    popup.className = 'notification-popup';
                    popup.innerHTML = `
                        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto; position: absolute; top: 60px; right: 20px; z-index: 10000;">
                            <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="margin: 0; color: var(--navy);">Notifications</h4>
                                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; cursor: pointer;"><i class="fas fa-times"></i></button>
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
                        </div>
                    `;
                    document.body.appendChild(popup);
                });
        }

        function updateNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notificationCount');
                    if (badge && data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    }
                });
        }

        function updateMessageCount() {
            fetch('get_message_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('messageCount');
                    if (badge && data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateMessageCount();
            updateNotificationCount();
        });
    </script>
</body>
</html>