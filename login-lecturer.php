<?php
session_start();
require_once 'config.php';

// If already logged in as lecturer/admin, redirect to lecturer dashboard
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['lecturer', 'admin'])) {
    header("Location: lecturer-dashboard.php");
    exit();
}

// If logged in as student, show unauthorized
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header("Location: unauthorized.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Query for lecturers/admins only
    $query = "SELECT u.USER_ID, u.EMAIL, u.FIRST_NAME, u.LAST_NAME, 
                     u.DEPARTMENT, u.ROLE, u.PASSWORD_HASH
              FROM USER u 
              WHERE u.EMAIL = ? 
              AND u.ROLE IN ('lecturer', 'admin') 
              AND u.ACCOUNT_STATUS = 'ACTIVE'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['PASSWORD_HASH'])) {
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['email'] = $user['EMAIL'];
            $_SESSION['first_name'] = $user['FIRST_NAME'];
            $_SESSION['last_name'] = $user['LAST_NAME'];
            $_SESSION['department'] = $user['DEPARTMENT'];
            $_SESSION['role'] = $user['ROLE'];
            
            // Redirect to lecturer dashboard after successful login
            header("Location: lecturer-dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Access denied. This portal is for lecturers and administrators only.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer & Admin Login - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .login-left {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-logo i {
            font-size: 64px;
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        
        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 16px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-with-icon input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
        }
        
        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #fecaca;
        }
        
        .welcome-title {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .welcome-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
       .features-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.features-list li {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    padding: 18px 20px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.features-list li:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateX(5px);
}

.feature-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
}

.feature-icon i {
    font-size: 22px;
    color: #1e3a8a;
}

.feature-content {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.feature-content strong {
    font-size: 17px;
    font-weight: 600;
    color: #ffffff;
}

.feature-content span {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.75);
    line-height: 1.5;
}
        
        .features-list i {
            margin-right: 15px;
            font-size: 20px;
            color: #fbbf24;
        }
        
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 20px;
            background: rgba(255,255,255,0.15);
            border-radius: 50px;
            font-size: 14px;
            margin-top: 40px;
            backdrop-filter: blur(10px);
        }
        
        .role-badge i {
            margin-right: 8px;
        }
        
        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
        }
        
        .forgot-password {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left, .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <h1 class="login-title">AIU Faculty Portal</h1>
            </div>
            
            <p class="login-subtitle">Access course management tools and administrative functions</p>
            
            <?php if (isset($error) && $error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Institutional Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="lecturer.email@aiu.edu" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In as Faculty
                </button>
            </form>
            
            <div class="login-footer">
                <p>Student? <a href="index.php">Access Student Portal →</a></p>
            </div>
        </div>
        
        <div class="login-right">
            <h2 class="welcome-title">Welcome, Faculty & Administrators</h2>
            <p class="welcome-subtitle">Comprehensive tools for academic management and administration at AI University</p>
            
            <ul class="features-list">
    <li>
        <div class="feature-icon">
            <i class="fas fa-calendar-plus"></i>
        </div>
        <div class="feature-content">
            <strong>Event Management</strong>
            <span>Schedule academic events, lectures, and meetings with automated notifications</span>
        </div>
    </li>
    <li>
        <div class="feature-icon">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="feature-content">
            <strong>Course Management</strong>
            <span>Create and manage course materials, syllabi, and resources</span>
        </div>
    </li>
    <li>
        <div class="feature-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="feature-content">
            <strong>Student Tracking</strong>
            <span>Monitor student progress, attendance, and performance metrics</span>
        </div>
    </li>
    <li>
        <div class="feature-icon">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="feature-content">
            <strong>Assignment Management</strong>
            <span>Create, distribute, and grade assignments with rubric-based scoring</span>
        </div>
    </li>
    <li>
        <div class="feature-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="feature-content">
            <strong>Analytics Dashboard</strong>
            <span>View detailed academic analytics and generate comprehensive reports</span>
        </div>
    </li>
    <li>
        <div class="feature-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="feature-content">
            <strong>Communication Hub</strong>
            <span>Connect with students and faculty through integrated messaging</span>
        </div>
    </li>
</ul>
            
            <div class="role-badge">
                <i class="fas fa-shield-alt"></i> Secure Access • Authorized Personnel Only
            </div>
        </div>
    </div>
</body>
</html>