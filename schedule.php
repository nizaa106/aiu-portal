<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's timetable with event details
$schedule_query = "SELECT e.*, c.COURSE_NAME 
                   FROM TIMETABLE t 
                   JOIN EVENTS e ON t.EVENT_ID = e.EVENT_ID 
                   JOIN COURSE c ON e.COURSE_ID = c.COURSE_ID 
                   WHERE t.USER_ID = ? 
                   AND e.START_TIME >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                   AND e.START_TIME < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
                   ORDER BY e.START_TIME";

$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$events = $stmt->get_result();

// Organize events by day and time slot
$schedule = [];
$timeSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

while ($event = $events->fetch_assoc()) {
    $startTime = strtotime($event['START_TIME']);
    $dayIndex = date('N', $startTime) - 1; // 0=Mon, 6=Sun
    $hour = date('H', $startTime);
    $timeKey = $hour . ':00';
    
    if ($dayIndex >= 0 && $dayIndex < 5) {
        $schedule[$dayIndex][$timeKey] = $event;
    }
}

// Fetch upcoming events for sidebar
$upcoming_query = "SELECT e.*, c.COURSE_NAME 
                   FROM EVENTS e 
                   JOIN COURSE c ON e.COURSE_ID = c.COURSE_ID 
                   JOIN ENROLLMENT en ON c.COURSE_ID = en.COURSE_ID 
                   WHERE en.USER_ID = ? AND e.START_TIME > NOW() 
                   ORDER BY e.START_TIME LIMIT 5";
$stmt2 = $conn->prepare($upcoming_query);
$stmt2->bind_param("s", $user_id);
$stmt2->execute();
$upcoming = $stmt2->get_result();

// Get current week dates
$weekStart = date('M d', strtotime('monday this week'));
$weekEnd = date('M d', strtotime('friday this week'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy: #1e3a5f;
            --navy-dark: #152a45;
            --gold: #c9a227;
            --gold-light: #f4d03f;
            --bg: #f5f7fa;
            --white: #ffffff;
            --text: #2c3e50;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --blue: #3b82f6;
            --green: #10b981;
            --orange: #f59e0b;
            --purple: #8b5cf6;
            --red: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--navy);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .brand {
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            border: 2px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--gold);
            font-size: 24px;
        }

        .brand h2 {
            color: white;
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .brand span {
            color: var(--gold);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-menu {
            flex: 1;
            padding: 20px 0;
            list-style: none;
        }

        .nav-item {
            padding: 14px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s;
            color: rgba(255,255,255,0.7);
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            color: var(--gold);
            border-left-color: var(--gold);
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
        }

        .sign-out {
            padding: 20px 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            color: rgba(255,255,255,0.7);
            transition: all 0.3s;
        }

        .sign-out:hover {
            color: white;
            background-color: rgba(255,255,255,0.05);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background-color: var(--white);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .portal-title {
            color: var(--navy);
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            background-color: var(--bg);
            border: 1px solid var(--border);
            padding: 10px 15px 10px 40px;
            border-radius: 25px;
            color: var(--text);
            width: 280px;
            outline: none;
            font-size: 14px;
        }

        .search-box input::placeholder {
            color: var(--text-light);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 14px;
        }

        .header-icons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .message-icon, .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.3s;
            color: var(--text-light);
        }

        .message-icon:hover, .notification-bell:hover {
            background-color: var(--bg);
            color: var(--navy);
        }

        .message-icon i, .notification-bell i {
            font-size: 18px;
        }

        .message-badge, .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            color: white;
            font-size: 10px;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-badge {
            background-color: var(--blue);
        }

        .notification-badge {
            background-color: var(--red);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 25px;
            transition: background-color 0.3s;
        }

        .user-profile:hover {
            background-color: var(--bg);
        }

        .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--navy), var(--gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 13px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }

        .user-role {
            font-size: 12px;
            color: var(--text-light);
        }

        /* Content Area */
        .content {
            padding: 30px;
            flex: 1;
        }

        .page-header {
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 28px;
            color: var(--navy);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 15px;
        }

        /* Calendar Navigation */
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .nav-btn {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: var(--text);
            transition: all 0.3s;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover {
            background: var(--bg);
            border-color: var(--navy);
            color: var(--navy);
        }

        .date-range {
            font-weight: 600;
            color: var(--navy);
            font-size: 15px;
            padding: 0 10px;
        }

        /* Schedule Container */
        .schedule-wrapper {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
        }

        /* Calendar Styles */
        .calendar-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }

        .calendar-header {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 1px;
            margin-bottom: 1px;
        }

        .time-header {
            background: var(--navy);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px 0 0 0;
        }

        .day-header {
            background: var(--navy);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .day-header:last-child {
            border-radius: 0 8px 0 0;
        }

        .day-header.today {
            background: var(--gold);
            color: var(--navy);
        }

        .calendar-body {
            display: flex;
            flex-direction: column;
            gap: 1px;
            background: var(--border);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .time-row {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 1px;
            min-height: 100px;
        }

        .time-label {
            background: #f8fafc;
            padding: 10px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            font-weight: 600;
            color: var(--text-light);
            font-size: 13px;
            padding-top: 15px;
        }

        .time-slot {
            background: white;
            padding: 8px;
            position: relative;
            transition: background 0.2s;
        }

        .time-slot:hover {
            background: #f8fafc;
        }

        .time-slot.today {
            background: #fefce8;
        }

        /* Event Cards */
        .event-card {
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .event-card.lecture {
            background: #dbeafe;
            border-left-color: var(--blue);
            color: #1e40af;
        }

        .event-card.tutorial {
            background: #fef3c7;
            border-left-color: var(--orange);
            color: #92400e;
        }

        .event-card.lab {
            background: #d1fae5;
            border-left-color: var(--green);
            color: #065f46;
        }

        .event-card.exam {
            background: #fee2e2;
            border-left-color: var(--red);
            color: #991b1b;
        }

        .event-card.meeting {
            background: #e0e7ff;
            border-left-color: var(--purple);
            color: #3730a3;
        }

        .event-title {
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .event-course {
            font-size: 11px;
            opacity: 0.9;
        }

        .event-time {
            font-size: 11px;
            margin-top: 6px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Empty Slot */
        .empty-slot {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 20px;
            opacity: 0.3;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .empty-slot:hover {
            opacity: 0.6;
        }

        /* Sidebar Widgets */
        .schedule-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .widget {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }

        .widget-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .widget-header i {
            color: var(--gold);
            font-size: 18px;
        }

        .widget-header h3 {
            font-size: 16px;
            color: var(--navy);
            font-weight: 600;
        }

        .event-list {
            list-style: none;
        }

        .event-list-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
        }

        .event-list-item:last-child {
            border-bottom: none;
        }

        .event-list-item:hover {
            background: var(--bg);
            margin: 0 -20px;
            padding-left: 20px;
            padding-right: 20px;
        }

        .event-date-box {
            text-align: center;
            min-width: 50px;
        }

        .event-day {
            font-size: 24px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1;
        }

        .event-month {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .event-details h4 {
            font-size: 14px;
            color: var(--navy);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .event-details p {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 2px;
        }

        .event-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .event-type-badge.lecture {
            background: #dbeafe;
            color: #1e40af;
        }

        .event-type-badge.tutorial {
            background: #fef3c7;
            color: #92400e;
        }

        .event-type-badge.lab {
            background: #d1fae5;
            color: #065f46;
        }

        /* Next Class Widget */
        .next-class-widget {
            background: linear-gradient(135deg, var(--navy), var(--navy-dark));
            color: white;
            border: none;
        }

        .next-class-widget .widget-header {
            border-bottom-color: rgba(255,255,255,0.2);
        }

        .next-class-widget .widget-header h3 {
            color: white;
        }

        .next-class-widget .widget-header i {
            color: var(--gold);
        }

        .next-class-info {
            text-align: center;
            padding: 20px 0;
        }

        .next-class-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: var(--gold);
        }

        .next-class-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .next-class-time {
            color: var(--gold);
            font-size: 14px;
            font-weight: 500;
        }

        .join-btn {
            width: 100%;
            padding: 12px;
            background: var(--gold);
            color: var(--navy);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .join-btn:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
        }

        /* Legend */
        .calendar-legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-light);
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border-left: 3px solid;
        }

        .legend-color.lecture {
            background: #dbeafe;
            border-color: var(--blue);
        }

        .legend-color.tutorial {
            background: #fef3c7;
            border-color: var(--orange);
        }

        .legend-color.lab {
            background: #d1fae5;
            border-color: var(--green);
        }

        .legend-color.exam {
            background: #fee2e2;
            border-color: var(--red);
        }

        /* Notification Popup */
        .notification-popup {
            position: absolute;
            top: 60px;
            right: 20px;
            z-index: 10000;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1200px) {
            .schedule-wrapper {
                grid-template-columns: 1fr;
            }
            
            .schedule-sidebar {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .widget {
                flex: 1;
                min-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .time-row {
                min-height: 80px;
            }
            
            .event-card {
                padding: 6px;
                font-size: 11px;
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
                    <li class="nav-item active" onclick="navigate('schedule.php')">
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
                        <div class="message-icon" onclick="window.location.href='messages.php'">
                            <i class="fas fa-envelope"></i>
                            <span class="message-badge" id="messageCount" style="display: none;">0</span>
                        </div>
                        
                        <div class="notification-bell" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </div>
                        
                        <div class="user-profile" onclick="window.location.href='settings.php'">
                            <div class="avatar">
                                <?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                                <div class="user-role"><?php echo $_SESSION['department']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Academic Schedule</h1>
                        <p>View your weekly timetable and upcoming events</p>
                    </div>
                    <div class="calendar-nav">
                        <button class="nav-btn" onclick="changeWeek(-1)">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <span class="date-range">
                            <i class="fas fa-calendar-week" style="margin-right: 8px; color: var(--gold);"></i>
                            <?php echo $weekStart; ?> - <?php echo $weekEnd; ?>
                        </span>
                        <button class="nav-btn" onclick="changeWeek(1)">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Schedule Wrapper -->
                <div class="schedule-wrapper">
                    <!-- Main Calendar -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <div class="time-header">Time</div>
                            <?php 
                            $today = date('N') - 1;
                            foreach ($days as $i => $day): 
                                $isToday = ($i == $today) ? 'today' : '';
                            ?>
                            <div class="day-header <?php echo $isToday; ?>">
                                <?php echo $day; ?>
                                <div style="font-size: 11px; font-weight: 400; margin-top: 4px; opacity: 0.8;">
                                    <?php echo date('M d', strtotime("monday this week +$i days")); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="calendar-body">
                            <?php foreach ($timeSlots as $time): 
                                list($hour, $minute) = explode(':', $time);
                            ?>
                            <div class="time-row">
                                <div class="time-label"><?php echo $time; ?></div>
                                <?php 
                                foreach ($days as $dayIndex => $day):
                                    $isToday = ($dayIndex == $today) ? 'today' : '';
                                    $hasEvent = isset($schedule[$dayIndex][$time]);
                                ?>
                                <div class="time-slot <?php echo $isToday; ?>">
                                    <?php if ($hasEvent): 
                                        $event = $schedule[$dayIndex][$time];
                                        $eventType = strtolower($event['EVENT_TYPE']);
                                        $eventClass = in_array($eventType, ['lecture', 'tutorial', 'lab', 'exam', 'meeting']) ? $eventType : 'lecture';
                                    ?>
                                    <div class="event-card <?php echo $eventClass; ?>" onclick="viewEvent(<?php echo $event['EVENT_ID']; ?>)">
                                        <div>
                                            <div class="event-title"><?php echo htmlspecialchars($event['TITLE']); ?></div>
                                            <div class="event-course"><?php echo htmlspecialchars($event['COURSE_NAME']); ?></div>
                                        </div>
                                        <div class="event-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('g:i A', strtotime($event['START_TIME'])); ?> - 
                                            <?php echo date('g:i A', strtotime($event['END_TIME'])); ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="empty-slot" onclick="addEvent('<?php echo $day; ?>', '<?php echo $time; ?>')">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Legend -->
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <div class="legend-color lecture"></div>
                                <span>Lecture</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color tutorial"></div>
                                <span>Tutorial</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color lab"></div>
                                <span>Lab</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color exam"></div>
                                <span>Exam</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="schedule-sidebar">
                        <!-- Upcoming Events -->
                        <div class="widget">
                            <div class="widget-header">
                                <i class="fas fa-calendar-day"></i>
                                <h3>Upcoming Events</h3>
                            </div>
                            <ul class="event-list">
                                <?php 
                                $upcomingCount = 0;
                                while ($event = $upcoming->fetch_assoc()): 
                                    $upcomingCount++;
                                    $eventType = strtolower($event['EVENT_TYPE']);
                                ?>
                                <li class="event-list-item" onclick="viewEvent(<?php echo $event['EVENT_ID']; ?>)">
                                    <div class="event-date-box">
                                        <div class="event-day"><?php echo date('d', strtotime($event['START_TIME'])); ?></div>
                                        <div class="event-month"><?php echo date('M', strtotime($event['START_TIME'])); ?></div>
                                    </div>
                                    <div class="event-details">
                                        <h4><?php echo htmlspecialchars($event['TITLE']); ?></h4>
                                        <p><i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i><?php echo htmlspecialchars($event['LOCATION']); ?></p>
                                        <p><i class="fas fa-clock" style="margin-right: 5px;"></i><?php echo date('g:i A', strtotime($event['START_TIME'])); ?></p>
                                        <span class="event-type-badge <?php echo $eventType; ?>"><?php echo $event['EVENT_TYPE']; ?></span>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                                
                                <?php if ($upcomingCount == 0): ?>
                                <li style="text-align: center; padding: 30px; color: var(--text-light);">
                                    <i class="fas fa-calendar-check" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                                    <p>No upcoming events</p>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- Next Class -->
                        <div class="widget next-class-widget">
                            <div class="widget-header">
                                <i class="fas fa-hourglass-half"></i>
                                <h3>Next Class</h3>
                            </div>
                            <?php 
                            // Reset the upcoming result pointer
                            $upcoming->data_seek(0);
                            $nextEvent = $upcoming->fetch_assoc();
                            if ($nextEvent): 
                                $minutesUntil = round((strtotime($nextEvent['START_TIME']) - time()) / 60);
                                $hoursUntil = floor($minutesUntil / 60);
                                $minsRemainder = $minutesUntil % 60;
                                $timeText = $hoursUntil > 0 ? "{$hoursUntil}h {$minsRemainder}m" : "{$minsRemainder} mins";
                            ?>
                            <div class="next-class-info">
                                <div class="next-class-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div class="next-class-name"><?php echo htmlspecialchars($nextEvent['COURSE_NAME']); ?></div>
                                <div class="next-class-time">
                                    <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                    Starting in <?php echo $timeText; ?>
                                </div>
                                <button class="join-btn" onclick="joinClass(<?php echo $nextEvent['EVENT_ID']; ?>)">
                                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Join Now
                                </button>
                            </div>
                            <?php else: ?>
                            <div style="text-align: center; padding: 30px;">
                                <i class="fas fa-coffee" style="font-size: 40px; color: rgba(255,255,255,0.3); margin-bottom: 15px;"></i>
                                <p style="color: rgba(255,255,255,0.7);">No upcoming classes</p>
                                <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 10px;">Enjoy your free time!</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function navigate(url) {
            window.location.href = url;
        }

        function changeWeek(direction) {
            // Implement week navigation
            const currentUrl = new URL(window.location.href);
            const currentWeek = parseInt(currentUrl.searchParams.get('week')) || 0;
            currentUrl.searchParams.set('week', currentWeek + direction);
            window.location.href = currentUrl.toString();
        }

        function viewEvent(eventId) {
            console.log('View event:', eventId);
            // window.location.href = 'event-details.php?id=' + eventId;
        }

        function addEvent(day, time) {
            console.log('Add event on', day, 'at', time);
            // window.location.href = 'add-event.php?day=' + day + '&time=' + time;
        }

        function joinClass(eventId) {
            console.log('Join class:', eventId);
            // Implement join class functionality
        }

        // Notification functions
        function showNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const popup = document.createElement('div');
                    popup.className = 'notification-popup';
                    popup.innerHTML = `
                        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto;">
                            <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="margin: 0; color: var(--navy);">Notifications</h4>
                                <button onclick="markAllAsRead()" style="background: none; border: none; color: var(--blue); cursor: pointer; font-size: 12px;">Mark all read</button>
                            </div>
                            <div id="notificationList">
                                ${data.notifications.length > 0 ? 
                                    data.notifications.map(n => `
                                        <div class="notification-item ${n.is_read ? 'read' : 'unread'}" onclick="markAsRead(${n.id})" style="padding: 12px 16px; border-bottom: 1px solid var(--border); cursor: pointer; transition: background 0.2s;">
                                            <div style="font-weight: 600; color: var(--navy); font-size: 13px;">${n.title}</div>
                                            <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">${n.message}</div>
                                            <div style="font-size: 11px; color: var(--text-light); margin-top: 4px;">${n.time}</div>
                                        </div>
                                    `).join('') : 
                                    '<div style="padding: 30px; text-align: center; color: var(--text-light);"><i class="fas fa-bell-slash" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i><p>No notifications</p></div>'
                                }
                            </div>
                        </div>
                    `;
                    
                    popup.style.cssText = 'position: absolute; top: 60px; right: 20px; z-index: 10000;';
                    
                    document.body.appendChild(popup);
                    
                    document.addEventListener('click', function closePopup(e) {
                        if (!popup.contains(e.target) && !e.target.closest('.notification-bell')) {
                            popup.remove();
                            document.removeEventListener('click', closePopup);
                        }
                    });
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
                    }
                });
        }

        function markAllAsRead() {
            fetch('mark_all_read.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationCount();
                        document.querySelector('.notification-popup').remove();
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