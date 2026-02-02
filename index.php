<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($conn, $_POST['role']);
    
    // Check if user already exists
    $check = $conn->prepare("SELECT u.USER_ID, u.PASSWORD_HASH, u.ACCOUNT_STATUS, 
                            p.FIRST_NAME, p.LAST_NAME, p.DEPARTMENT, r.ROLE_NAME 
                            FROM USER u 
                            LEFT JOIN PROFILE p ON u.USER_ID = p.USER_ID 
                            LEFT JOIN USER_ROLE ur ON u.USER_ID = ur.USER_ID 
                            LEFT JOIN ROLE r ON ur.ROLE_ID = r.ROLE_ID 
                            WHERE u.EMAIL = ? AND r.ROLE_NAME = ?");
    $check->bind_param("ss", $email, $role);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 1) {
        // Existing user - verify password
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['PASSWORD_HASH'])) {
            // Login successful
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $user['FIRST_NAME'];
            $_SESSION['last_name'] = $user['LAST_NAME'];
            $_SESSION['department'] = $user['DEPARTMENT'];
            $_SESSION['role'] = $user['ROLE_NAME'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Wrong password!";
        }
    } else {
        // NEW USER - Auto create account
        // Generate User ID from email (e.g., dr.chen@aiu.edu → DRCHEN001)
        $email_parts = explode('@', $email);
        $username = strtoupper(preg_replace('/[^a-zA-Z]/', '', $email_parts[0]));
        $user_id = $username . rand(100, 999);
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Extract name from email (dr.chen → Dr Chen)
        $name_parts = explode('.', $email_parts[0]);
        $first_name = ucfirst($name_parts[0]);
        $last_name = isset($name_parts[1]) ? ucfirst($name_parts[1]) : 'User';
        
        // Insert into USER table
        $stmt = $conn->prepare("INSERT INTO `USER` (USER_ID, EMAIL, PASSWORD_HASH, ACCOUNT_STATUS, CREATED_AT) 
                               VALUES (?, ?, ?, 'ACTIVE', NOW())");
        $stmt->bind_param("sss", $user_id, $email, $password_hash);
        
        if ($stmt->execute()) {
            // Insert into PROFILE
            $dept = ($role == 'Student') ? 'Computer Science' : 'Academic Department';
            $profile = $conn->prepare("INSERT INTO `PROFILE` (USER_ID, FIRST_NAME, LAST_NAME, DEPARTMENT) 
                                      VALUES (?, ?, ?, ?)");
            $profile->bind_param("ssss", $user_id, $first_name, $last_name, $dept);
            $profile->execute();
            
            // Assign Role
            $role_id = ($role == 'Student') ? 1 : 2;
            $user_role = $conn->prepare("INSERT INTO USER_ROLE (USER_ID, ROLE_ID) VALUES (?, ?)");
            $user_role->bind_param("si", $user_id, $role_id);
            $user_role->execute();
            
            // Auto login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['department'] = $dept;
            $_SESSION['role'] = $role;
            
            $success = "Account created successfully! Redirecting...";
            header("Refresh: 1; URL=dashboard.php");
            exit();
        } else {
            $error = "Error creating account: " . $conn->error;
        }
    }
}

$current_role = isset($_GET['role']) ? $_GET['role'] : 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIU Course Compass - Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 3px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-left-content {
            transition: opacity 0.3s ease;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            color: rgba(255,255,255,0.9);
            font-size: 15px;
        }
        
        .feature-item i {
            color: var(--gold);
            font-size: 18px;
            width: 20px;
        }
        
        .tagline {
            font-size: 17px;
            line-height: 1.7;
            margin-bottom: 40px;
            padding-left: 20px;
            border-left: 3px solid var(--gold);
            color: rgba(255,255,255,0.9);
            max-width: 420px;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <!-- LEFT SIDE -->
        <div class="login-left">
            <div class="logo-large">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>AIU</h1>
            <h2 style="font-family: 'Playfair Display', serif; font-style: italic; font-weight: 400;">Course Compass</h2>
            
            <!-- STUDENT CONTENT -->
            <div id="student-content" class="login-left-content" style="<?php echo $current_role == 'Student' ? '' : 'display: none;'; ?>">
                <p class="tagline">
                    Empowering the next generation of leaders through accessible, world-class digital education.
                </p>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>24/7 Access to Course Materials</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Direct Communication with Lecturers</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Real-time Academic Progress Tracking</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Comprehensive Digital Library</span>
                    </div>
                </div>
            </div>
            
            <!-- LECTURER CONTENT -->
            <div id="lecturer-content" class="login-left-content" style="<?php echo $current_role == 'Lecturer' ? '' : 'display: none;'; ?>">
                <p class="tagline">
                    Providing educators with advanced tools to inspire, teach, and mentor the leaders of tomorrow.
                </p>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Manage Course Content & Materials</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Grade Assignments & Track Progress</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Schedule Virtual Office Hours</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Direct Student Communication Channel</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE -->
        <div class="login-right">
            <div class="login-box">
                <h2>Sign In</h2>
                <p class="subtitle" id="portal-subtitle">
                    <?php echo $current_role == 'Student' ? 'Access Your Student Portal' : 'Access Your Lecturer Portal'; ?>
                </p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm" autocomplete="off">
                    <!-- Decoy fields -->
                    <input type="text" style="display:none" autocomplete="off">
                    <input type="password" style="display:none" autocomplete="off">
                    
                    <div class="role-toggle">
                        <button type="button" class="role-btn <?php echo $current_role == 'Student' ? 'active' : ''; ?>" onclick="switchRole('Student')">Student</button>
                        <button type="button" class="role-btn <?php echo $current_role == 'Lecturer' ? 'active' : ''; ?>" onclick="switchRole('Lecturer')">Lecturer</button>
                    </div>
                    <input type="hidden" name="role" id="roleInput" value="<?php echo $current_role; ?>">
                    
                    <div class="form-group">
                        <label>Username or Email</label>
                        <input type="email" name="email" id="emailInput" placeholder="<?php echo $current_role == 'Student' ? 'student@aiu.edu.my' : 'lecturer@aiu.edu'; ?>" required autocomplete="off" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="pwdVisible" placeholder="••••••••" required autocomplete="off">
                        <input type="hidden" name="password" id="pwdHidden">
                    </div>
                    
                    <div class="form-options">
                        <label class="remember" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="remember" style="width: 16px; height: 16px;">
                            <span style="color: var(--text); font-size: 14px;">Remember me</span>
                        </label>
                        <a href="#" class="forgot">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-primary">Sign In <i class="fas fa-arrow-right"></i></button>
                </form>
                
                <div style="text-align: center; margin-top: 20px; font-size: 14px;">
                    <p style="color: var(--text-light);">
                        Are you faculty? <a href="login-lecturer.php" style="color: var(--navy); font-weight: 600;">Sign in here →</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Switch between Student and Lecturer roles
        function switchRole(role) {
            // If Lecturer is selected, redirect to the dedicated lecturer login page
            if (role === 'Lecturer') {
                window.location.href = 'login-lecturer.php';
                return;
            }
            
            // For Student, update the form inline
            document.getElementById('roleInput').value = role;
            
            // Update subtitle
            const subtitle = document.getElementById('portal-subtitle');
            subtitle.textContent = 'Access Your Student Portal';
            
            // Update email placeholder
            document.getElementById('emailInput').placeholder = 'student@aiu.edu.my';
            
            // Toggle active button state
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.trim() === role) {
                    btn.classList.add('active');
                }
            });
            
            // Switch left panel content
            const studentContent = document.getElementById('student-content');
            const lecturerContent = document.getElementById('lecturer-content');
            
            lecturerContent.style.display = 'none';
            studentContent.style.display = 'block';
            studentContent.style.opacity = '0';
            setTimeout(() => {
                studentContent.style.opacity = '1';
            }, 50);
            
            // Update URL without reloading
            const newUrl = window.location.pathname + '?role=' + role;
            window.history.pushState({role: role}, '', newUrl);
        }
        
        // Handle browser back button
        window.onpopstate = function(event) {
            if (event.state && event.state.role) {
                switchRole(event.state.role);
            }
        };
        
        // Check URL on load
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const role = urlParams.get('role');
            if (role && role === 'Student') {
                switchRole(role);
            }
        };
        
        // Form submission handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // STOP the form from submitting immediately
            e.preventDefault();
            
            // Copy password to hidden field
            document.getElementById('pwdHidden').value = document.getElementById('pwdVisible').value;
            
            // DESTROY the password field completely
            var pwdField = document.getElementById('pwdVisible');
            pwdField.value = '';
            pwdField.type = 'text';
            pwdField.removeAttribute('id');
            
            // Now submit
            this.submit();
        });
    </script>
</body>
</html>