<?php
require_once 'config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = sanitize($conn, $_POST['user_id']);
    $email = sanitize($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = sanitize($conn, $_POST['first_name']);
    $last_name = sanitize($conn, $_POST['last_name']);
    $department = sanitize($conn, $_POST['department']);
    $role = sanitize($conn, $_POST['role']);
    
    // Check if user exists
    $check = $conn->prepare("SELECT USER_ID FROM `USER` WHERE USER_ID = ? OR EMAIL = ?");
    $check->bind_param("ss", $user_id, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "User ID or Email already exists!";
    } else {
        // Insert user
        $stmt = $conn->prepare("INSERT INTO `USER` (USER_ID, EMAIL, PASSWORD_HASH, ACCOUNT_STATUS) VALUES (?, ?, ?, 'ACTIVE')");
        $stmt->bind_param("sss", $user_id, $email, $password);
        
        if ($stmt->execute()) {
            // Insert profile
            $profile = $conn->prepare("INSERT INTO `PROFILE` (USER_ID, FIRST_NAME, LAST_NAME, DEPARTMENT) VALUES (?, ?, ?, ?)");
            $profile->bind_param("ssss", $user_id, $first_name, $last_name, $department);
            $profile->execute();
            
            // Assign role
            $role_id = ($role == 'Student') ? 1 : 2;
            $user_role = $conn->prepare("INSERT INTO USER_ROLE (USER_ID, ROLE_ID) VALUES (?, ?)");
            $user_role->bind_param("si", $user_id, $role_id);
            $user_role->execute();
            
            $success = "User registered successfully! You can now login.";
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register New User - AIU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            color: var(--navy);
            margin-bottom: 30px;
            text-align: center;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background-color: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background-color: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    </style>
</head>
<body style="background-color: var(--bg);">
    <div class="form-container">
        <h2><i class="fas fa-user-plus"></i> Register New User</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>User ID (Matric Number)</label>
                    <input type="text" name="user_id" placeholder="e.g., AIU24102155" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@student.aiu.edu.my" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Department</label>
                <input type="text" name="department" placeholder="e.g., Computer Science & Engineering" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Role</label>
                <select name="role" style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 8px;">
                    <option value="Student">Student</option>
                    <option value="Lecturer">Lecturer</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" minlength="6" required>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Register User
            </button>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" style="color: var(--navy); text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>