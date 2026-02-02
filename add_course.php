<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Get lecturers for dropdown
$lecturers = $conn->query("SELECT LECTURER_ID, LECTURER_NAME FROM LECTURER");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = sanitize($conn, $_POST['course_id']);
    $course_name = sanitize($conn, $_POST['course_name']);
    $description = sanitize($conn, $_POST['description']);
    $credits = intval($_POST['credits']);
    $lecturer_id = sanitize($conn, $_POST['lecturer_id']);
    
    $stmt = $conn->prepare("INSERT INTO COURSE (COURSE_ID, COURSE_NAME, COURSE_DESCRIPTION, CREDITS, LECTURER_ID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $course_id, $course_name, $description, $credits, $lecturer_id);
    
    if ($stmt->execute()) {
        $success = "Course added successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Course - AIU Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Include Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <h2>AIU e-Learning</h2>
                <span>Est. 1985</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item" onclick="navigate('dashboard.php')">
                        <i class="fas fa-book-open"></i><span>My Courses</span>
                    </li>
                    <li class="nav-item active" onclick="navigate('add_course.php')">
                        <i class="fas fa-plus-circle"></i><span>Add Course</span>
                    </li>
                    <li class="nav-item" onclick="navigate('add_event.php')">
                        <i class="fas fa-calendar-plus"></i><span>Add Event</span>
                    </li>
                    <li class="nav-item" onclick="navigate('logout.php')">
                        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    </li>
                    <li class="nav-item" onclick="navigate('add_event.php')">
            <i class="fas fa-calendar-plus"></i><span>Admin: Add Event</span>
        </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="portal-title">Admin Panel</div>
                <div class="header-right">
                    <div class="user-profile">
                        <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['first_name']; ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <div class="page-header">
                    <h1>Add New Course</h1>
                    <p>Create a new course in the system</p>
                </div>

                <?php if ($success): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); max-width: 700px;">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Course ID</label>
                                <input type="text" name="course_id" placeholder="e.g., CS402" required>
                            </div>
                            <div class="form-group">
                                <label>Course Name</label>
                                <input type="text" name="course_name" placeholder="e.g., Database Systems" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Description</label>
                            <textarea name="description" rows="3" style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 8px; font-family: inherit;"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Credits</label>
                                <input type="number" name="credits" min="1" max="6" value="3" required>
                            </div>
                            <div class="form-group">
                                <label>Lecturer</label>
                                <select name="lecturer_id" style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 8px;">
                                    <?php while($row = $lecturers->fetch_assoc()): ?>
                                    <option value="<?php echo $row['LECTURER_ID']; ?>">
                                        <?php echo $row['LECTURER_NAME']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                                        <!-- Existing Courses Section -->
                <div style="margin-top: 40px;">
                    <div class="page-header" style="margin-bottom: 20px;">
                        <div>
                            <h2>Existing Courses</h2>
                            <p>All courses currently in the system</p>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border);">
                                    <th style="text-align: left; padding: 12px; color: var(--navy);">Course ID</th>
                                    <th style="text-align: left; padding: 12px; color: var(--navy);">Course Name</th>
                                    <th style="text-align: left; padding: 12px; color: var(--navy);">Credits</th>
                                    <th style="text-align: left; padding: 12px; color: var(--navy);">Lecturer</th>
                                    <th style="text-align: center; padding: 12px; color: var(--navy);">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $courses_query = "SELECT c.*, l.LECTURER_NAME 
                                                  FROM COURSE c 
                                                  LEFT JOIN LECTURER l ON c.LECTURER_ID = l.LECTURER_ID 
                                                  ORDER BY c.COURSE_ID";
                                $courses_result = $conn->query($courses_query);
                                
                                if ($courses_result && $courses_result->num_rows > 0):
                                    while($course = $courses_result->fetch_assoc()):
                                ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 12px; font-weight: 600; color: var(--navy);">
                                        <?php echo htmlspecialchars($course['COURSE_ID']); ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php echo htmlspecialchars($course['COURSE_NAME']); ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: var(--gold); color: var(--navy); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                            <?php echo $course['CREDITS']; ?> Credits
                                        </span>
                                    </td>
                                    <td style="padding: 12px; color: var(--text-light);">
                                        <i class="fas fa-user" style="margin-right: 6px; color: var(--gold);"></i>
                                        <?php echo htmlspecialchars($course['LECTURER_NAME']); ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button style="background: none; border: none; color: var(--blue); cursor: pointer; margin-right: 10px;" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button style="background: none; border: none; color: var(--red); cursor: pointer;" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-light);">
                                        <i class="fas fa-book" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                                        <p>No courses found in the system.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
    
    <script>
        function navigate(url) { window.location.href = url; }
    </script>
</body>
</html>

