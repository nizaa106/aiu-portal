<?php
session_start();
require_once 'config.php';

// Check if user is logged in as lecturer/admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lecturer', 'admin'])) {
    header("Location: login-lecturer.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$success = '';
$error = '';

// Function to sanitize input
function sanitize($conn, $input) {
    if (empty($input)) return '';
    return mysqli_real_escape_string($conn, trim($input));
}

// Get courses for dropdown - Only show courses taught by this lecturer
$courses_query = $conn->prepare("SELECT COURSE_ID, COURSE_NAME FROM COURSE WHERE LECTURER_ID = ?");
$courses_query->bind_param("s", $user_id);
$courses_query->execute();
$courses_result = $courses_query->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    if (empty($_POST['course_id'])) {
        $error = "Course selection is required.";
    } elseif (empty($_POST['title'])) {
        $error = "Event title is required.";
    } elseif (empty($_POST['start_time']) || empty($_POST['end_time'])) {
        $error = "Start time and end time are required.";
    } elseif (empty($_POST['location'])) {
        $error = "Location is required.";
    } else {
        // Get and sanitize form data
        $course_id = sanitize($conn, $_POST['course_id']);
        $title = sanitize($conn, $_POST['title']);
        $event_type = sanitize($conn, $_POST['event_type']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $location = sanitize($conn, $_POST['location']);
        $description = isset($_POST['description']) ? sanitize($conn, $_POST['description']) : '';

        // Validate event times
        if (strtotime($end_time) <= strtotime($start_time)) {
            $error = "End time must be after start time.";
        } else {
            // Check for schedule conflict
            $clash_check = $conn->prepare("SELECT * FROM EVENTS 
                                          WHERE LOCATION = ? 
                                          AND COURSE_ID = ?
                                          AND (
                                              (START_TIME BETWEEN ? AND ?) 
                                              OR (END_TIME BETWEEN ? AND ?) 
                                              OR (? BETWEEN START_TIME AND END_TIME)
                                              OR (? BETWEEN START_TIME AND END_TIME)
                                          )");
            if ($clash_check) {
                $clash_check->bind_param("ssssssss", $location, $course_id, 
                                         $start_time, $end_time, 
                                         $start_time, $end_time, 
                                         $start_time, $end_time);
                $clash_check->execute();
                $clash_result = $clash_check->get_result();
                
                if ($clash_result->num_rows > 0) {
                    $error = "❌ Schedule Conflict! This room is already booked at the selected time for this course.";
                } else {
                    // No clash - proceed with insertion
                    $stmt = $conn->prepare("INSERT INTO EVENTS (COURSE_ID, LECTURER_ID, TITLE, EVENT_TYPE, START_TIME, END_TIME, LOCATION, DESCRIPTION) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssssssss", $course_id, $user_id, $title, $event_type, $start_time, $end_time, $location, $description);
                        
                        if ($stmt->execute()) {
                            $event_id = $conn->insert_id;
                            $success = "✅ Event scheduled successfully!";
                            
                            // Auto-add to enrolled students' timetables
                            $enrolled = $conn->prepare("SELECT USER_ID FROM ENROLLMENT WHERE COURSE_ID = ? AND ENROLLMENT_STATUS = 'ACTIVE'");
                            if ($enrolled) {
                                $enrolled->bind_param("s", $course_id);
                                $enrolled->execute();
                                $result = $enrolled->get_result();
                                
                                while($student = $result->fetch_assoc()) {
                                    $tt = $conn->prepare("INSERT INTO TIMETABLE (USER_ID, EVENT_ID) VALUES (?, ?)");
                                    if ($tt) {
                                        $tt->bind_param("si", $student['USER_ID'], $event_id);
                                        $tt->execute();
                                        $tt->close();
                                    }
                                }
                                $enrolled->close();
                            }
                            
                            // Create notification for enrolled students
                            $notification_msg = "New event scheduled: " . $title . " at " . $location . " on " . date('M d, Y', strtotime($start_time));
                            $notification_type = "NEW_EVENT";
                            
                            $notify_stmt = $conn->prepare("INSERT INTO NOTIFICATION (USER_ID, EVENT_ID, MESSAGE, NOTIFICATION_TYPE, STATUS) 
                                                          SELECT USER_ID, ?, ?, ?, 'UNREAD' 
                                                          FROM ENROLLMENT 
                                                          WHERE COURSE_ID = ? AND ENROLLMENT_STATUS = 'ACTIVE'");
                            if ($notify_stmt) {
                                $notify_stmt->bind_param("isss", $event_id, $notification_msg, $notification_type, $course_id);
                                $notify_stmt->execute();
                                $notify_stmt->close();
                            }
                            
                            // Redirect to lecturer dashboard after successful insertion
                            $_SESSION['success_message'] = $success;
                            header("Location: lecturer-dashboard.php");
                            exit();
                        } else {
                            $error = "Error inserting event: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = "Error preparing statement: " . $conn->error;
                    }
                }
                $clash_check->close();
            } else {
                $error = "Error preparing conflict check: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - AIU Faculty Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --navy: #1e3a8a;
            --blue: #3b82f6;
            --light-blue: #dbeafe;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--text);
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }
        
        .brand {
            padding: 0 30px 30px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo-icon i {
            color: white;
            font-size: 24px;
        }
        
        .brand h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--navy);
            margin-bottom: 5px;
        }
        
        .brand span {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            flex: 1;
        }
        
        .nav-item {
            padding: 16px 30px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            margin-bottom: 5px;
        }
        
        .nav-item:hover {
            background: var(--light-blue);
            border-left-color: var(--blue);
        }
        
        .nav-item.active {
            background: var(--light-blue);
            border-left-color: var(--blue);
            font-weight: 600;
            color: var(--navy);
        }
        
        .nav-item i {
            width: 24px;
            margin-right: 15px;
            font-size: 18px;
            color: var(--text-light);
        }
        
        .nav-item.active i {
            color: var(--blue);
        }
        
        .nav-item span {
            font-size: 15px;
        }
        
        .sign-out {
            padding: 20px 30px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            cursor: pointer;
            color: var(--danger);
            transition: background 0.3s;
        }
        
        .sign-out:hover {
            background: #fef2f2;
        }
        
        .sign-out i {
            margin-right: 15px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            overflow-y: auto;
        }
        
        .top-header {
            background: white;
            padding: 20px 40px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .portal-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--navy);
            font-weight: 700;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 46px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-bell, .message-icon {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .notification-bell:hover, .message-icon:hover {
            background: var(--light-blue);
        }
        
        .notification-bell i, .message-icon i {
            font-size: 20px;
            color: var(--text-light);
        }
        
        .notification-badge, .message-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 11px;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .message-badge {
            background: var(--blue);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 12px;
            background: var(--light-blue);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-profile:hover {
            background: #e0f2fe;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--navy);
        }
        
        .user-role {
            font-size: 13px;
            color: var(--text-light);
        }
        
        /* Content Area */
        .content {
            padding: 40px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: var(--navy);
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--text-light);
            font-size: 16px;
        }
        
        /* Event Form */
        .event-form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
            font-size: 14px;
        }
        
        .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="datetime-local"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        input[type="text"]:focus,
        input[type="datetime-local"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--navy);
            border: 2px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--blue);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-header {
                padding: 15px 20px;
            }
            
            .content {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <div class="logo-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h2>AIU Faculty</h2>
                <span>Portal - 2026</span>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item" onclick="window.location.href='lecturer-dashboard.php'">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </li>
                    <li class="nav-item" onclick="window.location.href='manage-courses.php'">
                        <i class="fas fa-book"></i>
                        <span>My Courses</span>
                    </li>
                    <li class="nav-item active" onclick="window.location.href='add_event.php'">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Add Event</span>
                    </li>
                    <li class="nav-item" onclick="window.location.href='grading.php'">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Grading</span>
                    </li>
                    <li class="nav-item" onclick="window.location.href='messages.php'">
                        <i class="fas fa-comment-alt"></i>
                        <span>Messages</span>
                    </li>
                    <li class="nav-item" onclick="window.location.href='faculty-settings.php'">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </li>
                    <?php if ($user_role === 'admin'): ?>
                    <li class="nav-item" onclick="window.location.href='admin-panel.php'">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin Panel</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="sign-out" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="portal-title">Faculty Portal</div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <div class="header-icons">
                        <div class="message-icon" onclick="window.location.href='messages.php'">
                            <i class="fas fa-envelope"></i>
                            <span class="message-badge" id="messageCount">0</span>
                        </div>
                        
                        <div class="notification-bell" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount">0</span>
                        </div>
                        
                        <div class="user-profile" onclick="window.location.href='faculty-settings.php'">
                            <div class="avatar">
                                <?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                                <div class="user-role"><?php echo ucfirst($_SESSION['role']) . ' • ' . $_SESSION['department']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <div class="page-header">
                    <div>
                        <h1>Schedule New Event</h1>
                        <p>Add lectures, exams, or meetings for your courses</p>
                    </div>
                    <button class="btn-primary" onclick="window.location.href='lecturer-dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                </div>

                <div class="event-form-container">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <div class="form-card">
                        <form method="POST" action="" id="eventForm">
                            <div class="form-group">
                                <label for="course_id">Course <span class="required">*</span></label>
                                <select name="course_id" id="course_id" required>
                                    <option value="">Select a Course</option>
                                    <?php 
                                    if ($courses_result && $courses_result->num_rows > 0) {
                                        while($row = $courses_result->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo htmlspecialchars($row['COURSE_ID']); ?>">
                                        <?php echo htmlspecialchars($row['COURSE_ID'] . ' - ' . $row['COURSE_NAME']); ?>
                                    </option>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<option value="">No courses available</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">Event Title <span class="required">*</span></label>
                                    <input type="text" name="title" id="title" placeholder="e.g., Week 5 Lecture, Midterm Exam" required>
                                </div>
                                <div class="form-group">
                                    <label for="event_type">Event Type <span class="required">*</span></label>
                                    <select name="event_type" id="event_type" required>
                                        <option value="LECTURE">Lecture</option>
                                        <option value="TUTORIAL">Tutorial</option>
                                        <option value="LAB">Lab Session</option>
                                        <option value="EXAM">Exam</option>
                                        <option value="OFFICE_HOURS">Office Hours</option>
                                        <option value="MEETING">Meeting</option>
                                        <option value="WORKSHOP">Workshop</option>
                                        <option value="SEMINAR">Seminar</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_time">Start Time <span class="required">*</span></label>
                                    <input type="datetime-local" name="start_time" id="start_time" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_time">End Time <span class="required">*</span></label>
                                    <input type="datetime-local" name="end_time" id="end_time" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location <span class="required">*</span></label>
                                <input type="text" name="location" id="location" placeholder="e.g., Room 101, Block C, Online Meeting" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description (Optional)</label>
                                <textarea name="description" id="description" placeholder="Additional details about the event..."></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-calendar-check"></i> Schedule Event
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset Form
                                </button>
                                <a href="lecturer-dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Set min date/time to current date/time
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);
        
        document.getElementById('start_time').min = localDateTime;
        document.getElementById('end_time').min = localDateTime;
        
        // Set end time based on start time
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = this.value;
            const endTimeInput = document.getElementById('end_time');
            
            // Set end time minimum to start time
            endTimeInput.min = startTime;
            
            // If end time is before start time, update it to 1 hour after start
            if (endTimeInput.value && endTimeInput.value < startTime) {
                const startDate = new Date(startTime);
                startDate.setHours(startDate.getHours() + 1);
                const newEndTime = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000)
                    .toISOString()
                    .slice(0, 16);
                endTimeInput.value = newEndTime;
            }
        });
        
        // Form validation
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const location = document.getElementById('location').value.trim();
            
            // Check if end time is after start time
            if (startTime && endTime) {
                const start = new Date(startTime);
                const end = new Date(endTime);
                
                if (end <= start) {
                    e.preventDefault();
                    alert('Error: End time must be after start time.');
                    document.getElementById('end_time').focus();
                    return false;
                }
            }
            
            // Check if location is valid
            if (!location) {
                e.preventDefault();
                alert('Error: Please enter a valid location.');
                document.getElementById('location').focus();
                return false;
            }
            
            return true;
        });
        
        // Auto-fill location based on event type
        document.getElementById('event_type').addEventListener('change', function() {
            const eventType = this.value;
            const locationInput = document.getElementById('location');
            
            if (!locationInput.value) {
                switch(eventType) {
                    case 'LECTURE':
                        locationInput.placeholder = 'e.g., Lecture Hall A, Room 101';
                        break;
                    case 'LAB':
                        locationInput.placeholder = 'e.g., Computer Lab 3, Science Lab B';
                        break;
                    case 'ONLINE':
                        locationInput.placeholder = 'e.g., Zoom Meeting, Microsoft Teams';
                        break;
                    case 'EXAM':
                        locationInput.placeholder = 'e.g., Exam Hall, Room 5';
                        break;
                    default:
                        locationInput.placeholder = 'e.g., Room 101, Block C';
                }
            }
        });
        
        // Notification functions
        function showNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const popup = document.createElement('div');
                    popup.style.cssText = 'position: absolute; top: 60px; right: 20px; z-index: 10000; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto;';
                    popup.innerHTML = `
                        <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0;">Notifications</h4>
                            <button onclick="markAllAsRead()" style="background: none; border: none; color: var(--navy); cursor: pointer;">Mark all read</button>
                        </div>
                        <div id="notificationList">
                            ${data.notifications.length > 0 ? 
                                data.notifications.map(n => `
                                    <div class="notification-item ${n.is_read ? 'read' : 'unread'}" onclick="markAsRead(${n.id})" style="padding: 12px 16px; border-bottom: 1px solid var(--border); cursor: pointer;">
                                        <div style="font-weight: 600;">${n.title}</div>
                                        <div style="font-size: 13px; color: var(--text-light);">${n.message}</div>
                                        <div style="font-size: 11px; color: var(--text-light); margin-top: 4px;">${n.time}</div>
                                    </div>
                                `).join('') : 
                                '<div style="padding: 20px; text-align: center; color: var(--text-light);">No notifications</div>'
                            }
                        </div>
                    `;
                    
                    document.body.appendChild(popup);
                    
                    document.addEventListener('click', function closePopup(e) {
                        if (!popup.contains(e.target) && !e.target.classList.contains('fa-bell') && !e.target.closest('.notification-bell')) {
                            popup.remove();
                            document.removeEventListener('click', closePopup);
                        }
                    });
                });
        }
    </script>
</body>
</html>