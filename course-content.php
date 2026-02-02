<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$course_id) {
    header("Location: dashboard.php");
    exit();
}

// Validate course_id is alphanumeric to prevent SQL injection
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $course_id)) {
    header("Location: dashboard.php");
    exit();
}

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

// Fetch course details
$course_query = "SELECT c.*, l.LECTURER_NAME, l.LECTURER_EMAIL 
                 FROM COURSE c 
                 JOIN LECTURER l ON c.LECTURER_ID = l.LECTURER_ID 
                 WHERE c.COURSE_ID = ?";
$stmt = $conn->prepare($course_query);
if (!$stmt) {
    die("Query preparation failed.");
}
$stmt->bind_param("s", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: dashboard.php");
    exit();
}

// Fetch course materials/notes
$materials_query = "SELECT * FROM COURSE_MATERIAL 
                    WHERE COURSE_ID = ? 
                    ORDER BY UPLOAD_DATE DESC";
$stmt2 = $conn->prepare($materials_query);
$stmt2->bind_param("s", $course_id);
$stmt2->execute();
$materials = $stmt2->get_result();
$stmt2->close();

// Fetch announcements
$announcements_query = "SELECT * FROM ANNOUNCEMENT 
                        WHERE COURSE_ID = ? 
                        ORDER BY CREATED_AT DESC LIMIT 5";
$stmt3 = $conn->prepare($announcements_query);
$stmt3->bind_param("s", $course_id);
$stmt3->execute();
$announcements = $stmt3->get_result();
$stmt3->close();

// Fetch assignments
$assignments_query = "SELECT * FROM ASSIGNMENT 
                      WHERE COURSE_ID = ? 
                      ORDER BY DUE_DATE ASC";
$stmt4 = $conn->prepare($assignments_query);
$stmt4->bind_param("s", $course_id);
$stmt4->execute();
$assignments = $stmt4->get_result();
$stmt4->close();

// Check enrollment and get progress
$enrollment_query = "SELECT PROGRESS_PERCENT FROM ENROLLMENT 
                     WHERE USER_ID = ? AND COURSE_ID = ?";
$stmt5 = $conn->prepare($enrollment_query);
$stmt5->bind_param("ss", $user_id, $course_id);
$stmt5->execute();
$enrollment = $stmt5->get_result()->fetch_assoc();
$stmt5->close();
$progress = $enrollment ? intval($enrollment['PROGRESS_PERCENT']) : 0;

// Ensure progress is between 0 and 100
$progress = max(0, min(100, $progress));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['COURSE_NAME'] ?? 'Course'); ?> - AIU Portal</title>
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

        /* Content */
        .content {
            padding: 30px;
            flex: 1;
        }

        /* Course Header */
        .course-header {
            background: linear-gradient(135deg, var(--navy), var(--navy-dark));
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .course-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(201, 162, 39, 0.1);
            border-radius: 50%;
        }

        .course-code {
            display: inline-block;
            background: var(--gold);
            color: var(--navy);
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .course-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 10px;
            color: white;
        }

        .course-meta {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .progress-section {
            margin-top: 25px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
        }

        .progress-bar {
            height: 8px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--gold);
            border-radius: 4px;
            width: 0%;
            transition: width 0.5s ease;
        }

        /* Course Content Layout */
        .course-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        /* Main Column */
        .materials-section, .assignments-section {
            background-color: var(--white);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--navy);
        }

        .add-btn {
            background-color: var(--navy);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .add-btn:hover {
            background-color: var(--navy-dark);
        }

        /* Materials List */
        .materials-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .material-item {
            display: flex;
            align-items: center;
            padding: 18px;
            background-color: var(--bg);
            border-radius: 12px;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .material-item:hover {
            border-color: var(--navy);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }

        .material-icon {
            width: 50px;
            height: 50px;
            background-color: var(--navy);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .material-info {
            flex: 1;
        }

        .material-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text);
        }

        .material-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: var(--text-light);
        }

        .download-btn {
            background-color: transparent;
            color: var(--navy);
            border: 1px solid var(--navy);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .download-btn:hover {
            background-color: var(--navy);
            color: white;
        }

        /* Assignments List */
        .assignments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .assignment-item {
            background-color: var(--bg);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--navy);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .assignment-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text);
        }

        .due-date {
            background-color: var(--orange);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .assignment-desc {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view, .btn-submit {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-view {
            background-color: var(--navy);
            color: white;
        }

        .btn-submit {
            background-color: var(--green);
            color: white;
        }

        /* Sidebar Column */
        .announcements-section, .lecturer-section {
            background-color: var(--white);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .announcements-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .announcement-item {
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .announcement-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .announcement-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }

        .announcement-date {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .announcement-content {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
        }

        /* Lecturer Info */
        .lecturer-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .lecturer-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--navy), var(--gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }

        .lecturer-details h4 {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text);
        }

        .lecturer-details p {
            font-size: 14px;
            color: var(--text-light);
        }

        .contact-lecturer {
            background-color: var(--gold);
            color: var(--navy);
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .contact-lecturer:hover {
            background-color: var(--gold-light);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .course-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 80px;
            }
            
            .brand h2, .brand span, .nav-item span, .sign-out span {
                display: none;
            }
            
            .nav-item {
                justify-content: center;
                padding: 20px 0;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .search-box input {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-box input {
                width: 180px;
            }
            
            .course-header {
                padding: 25px;
            }
            
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brand">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>AIU Portal</h2>
                <span>Learning Management System</span>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-book-open"></i>
                    <span>My Courses</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Discussions</span>
                </li>
                <li class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </li>
            </ul>
            
            <div class="sign-out">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="portal-title">AIU Learning Portal</div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search courses, materials...">
                    </div>
                    
                    <div class="header-icons">
                        <div class="message-icon">
                            <i class="fas fa-envelope"></i>
                            <div class="message-badge">3</div>
                        </div>
                        
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <div class="notification-badge">5</div>
                        </div>
                        
                        <div class="user-profile">
                            <div class="avatar">JD</div>
                            <div class="user-info">
                                <div class="user-name">John Doe</div>
                                <div class="user-role">Student</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="content">
                <!-- Course Header -->
                <div class="course-header">
                    <div class="course-code"><?php echo htmlspecialchars($course['COURSE_CODE'] ?? 'N/A'); ?></div>
                    <h1 class="course-title"><?php echo htmlspecialchars($course['COURSE_NAME'] ?? 'Course'); ?></h1>
                    
                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="fas fa-user-graduate"></i>
                            <span>Lecturer: <?php echo htmlspecialchars($course['LECTURER_NAME'] ?? 'TBA'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Duration: <?php echo htmlspecialchars($course['DURATION'] ?? '0'); ?> weeks</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            <span>Credits: <?php echo htmlspecialchars($course['CREDITS'] ?? '0'); ?></span>
                        </div>
                    </div>
                    
                    <p style="color: rgba(255,255,255,0.9); margin-bottom: 20px; max-width: 800px; line-height: 1.6;">
                        <?php echo htmlspecialchars($course['COURSE_DESCRIPTION'] ?? 'No description available.'); ?>
                    </p>
                    
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Course Progress</span>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" data-progress="<?php echo $progress; ?>"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Content -->
                <div class="course-layout">
                    <!-- Main Column -->
                    <div class="main-column">
                        <!-- Course Materials -->
                        <section class="materials-section">
                            <div class="section-header">
                                <h2 class="section-title">Course Materials</h2>
                                <button class="add-btn">
                                    <i class="fas fa-plus"></i>
                                    <span>Upload Material</span>
                                </button>
                            </div>
                            
                            <div class="materials-list">
                                <?php if ($materials && $materials->num_rows > 0): ?>
                                    <?php while ($material = $materials->fetch_assoc()): ?>
                                        <?php 
                                        $file_path = $material['FILE_PATH'] ?? '';
                                        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                        $material_id = $material['MATERIAL_ID'] ?? '';
                                        ?>
                                        <div class="material-item">
                                            <div class="material-icon">
                                                <?php if ($ext === 'pdf'): ?>
                                                    <i class="fas fa-file-pdf"></i>
                                                <?php elseif (in_array($ext, ['doc', 'docx'])): ?>
                                                    <i class="fas fa-file-word"></i>
                                                <?php elseif (in_array($ext, ['ppt', 'pptx'])): ?>
                                                    <i class="fas fa-file-powerpoint"></i>
                                                <?php elseif (in_array($ext, ['mp4', 'avi', 'mov', 'mkv'])): ?>
                                                    <i class="fas fa-file-video"></i>
                                                <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                    <i class="fas fa-file-image"></i>
                                                <?php elseif (in_array($ext, ['zip', 'rar', '7z'])): ?>
                                                    <i class="fas fa-file-archive"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-file-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="material-info">
                                                <div class="material-title"><?php echo htmlspecialchars($material['TITLE'] ?? 'Untitled'); ?></div>
                                                <div class="material-meta">
                                                    <span><i class="far fa-calendar"></i> <?php echo !empty($material['UPLOAD_DATE']) ? date('M d, Y', strtotime($material['UPLOAD_DATE'])) : 'N/A'; ?></span>
                                                    <span><i class="fas fa-file"></i> <?php echo htmlspecialchars(strtoupper($ext)); ?> File</span>
                                                    <span><i class="fas fa-weight-hanging"></i> <?php echo htmlspecialchars($material['FILE_SIZE'] ?? '0'); ?> MB</span>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($material_id)): ?>
                                                <a href="download.php?id=<?php echo urlencode($material_id); ?>" class="download-btn">
                                                    <i class="fas fa-download"></i>
                                                    <span>Download</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php $materials->free(); ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 40px; color: var(--text-light);">
                                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No course materials available yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                        
                        <!-- Assignments -->
                        <section class="assignments-section">
                            <div class="section-header">
                                <h2 class="section-title">Assignments</h2>
                                <button class="add-btn">
                                    <i class="fas fa-plus"></i>
                                    <span>New Assignment</span>
                                </button>
                            </div>
                            
                            <div class="assignments-list">
                                <?php if ($assignments && $assignments->num_rows > 0): ?>
                                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                                        <div class="assignment-item">
                                            <div class="assignment-header">
                                                <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['TITLE'] ?? 'Untitled'); ?></h3>
                                                <div class="due-date">
                                                    Due: <?php echo !empty($assignment['DUE_DATE']) ? date('M d, Y', strtotime($assignment['DUE_DATE'])) : 'TBA'; ?>
                                                </div>
                                            </div>
                                            
                                            <p class="assignment-desc">
                                                <?php echo htmlspecialchars($assignment['DESCRIPTION'] ?? 'No description available.'); ?>
                                            </p>
                                            
                                            <div class="assignment-actions">
                                                <button class="btn-view">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View Details</span>
                                                </button>
                                                <button class="btn-submit">
                                                    <i class="fas fa-paper-plane"></i>
                                                    <span>Submit Work</span>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php $assignments->free(); ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 40px; color: var(--text-light);">
                                        <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No assignments posted yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                    
                    <!-- Sidebar Column -->
                    <div class="sidebar-column">
                        <!-- Announcements -->
                        <section class="announcements-section">
                            <div class="section-header">
                                <h2 class="section-title">Announcements</h2>
                            </div>
                            
                            <div class="announcements-list">
                                <?php if ($announcements && $announcements->num_rows > 0): ?>
                                    <?php while ($announcement = $announcements->fetch_assoc()): ?>
                                        <div class="announcement-item">
                                            <h4 class="announcement-title"><?php echo htmlspecialchars($announcement['TITLE'] ?? 'Untitled'); ?></h4>
                                            <div class="announcement-date">
                                                <i class="far fa-clock"></i> <?php echo !empty($announcement['CREATED_AT']) ? date('M d, Y - h:i A', strtotime($announcement['CREATED_AT'])) : 'N/A'; ?>
                                            </div>
                                            <p class="announcement-content">
                                                <?php echo htmlspecialchars($announcement['CONTENT'] ?? 'No content.'); ?>
                                            </p>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php $announcements->free(); ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 20px; color: var(--text-light);">
                                        <i class="fas fa-bullhorn" style="font-size: 36px; margin-bottom: 10px; opacity: 0.5;"></i>
                                        <p>No announcements yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                        
                        <!-- Lecturer Info -->
                        <section class="lecturer-section">
                            <div class="section-header">
                                <h2 class="section-title">Lecturer Information</h2>
                            </div>
                            
                            <div class="lecturer-info">
                                <div class="lecturer-avatar">
                                    <?php 
                                    $lecturer_name = $course['LECTURER_NAME'] ?? '';
                                    echo !empty($lecturer_name) ? htmlspecialchars(substr($lecturer_name, 0, 2)) : 'NA'; 
                                    ?>
                                </div>
                                <div class="lecturer-details">
                                    <h4><?php echo htmlspecialchars($course['LECTURER_NAME'] ?? 'Not Assigned'); ?></h4>
                                    <p>Course Instructor</p>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($course['LECTURER_EMAIL'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            
                            <button class="contact-lecturer">
                                <i class="fas fa-comment-dots"></i>
                                <span>Contact Lecturer</span>
                            </button>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Navigation active state
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const progress = progressFill.getAttribute('data-progress') || '0';
                setTimeout(() => {
                    progressFill.style.width = progress + '%';
                }, 300);
            }
        });
        
        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                if (searchTerm.length > 0) {
                    // Implement search logic - filter materials, assignments, etc.
                    console.log('Searching for:', searchTerm);
                }
            });
        }
        
        // Contact lecturer button
        document.querySelector('.contact-lecturer')?.addEventListener('click', function() {
            // Implement contact functionality
            alert('Contact lecturer feature coming soon!');
        });
    </script>
</body>
</html>
