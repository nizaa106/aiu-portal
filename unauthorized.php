<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        
        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .error-icon {
            width: 100px;
            height: 100px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        
        .error-icon i {
            font-size: 48px;
            color: #dc2626;
        }
        
        h1 {
            color: #dc2626;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        p {
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .info-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-box i {
            color: #3b82f6;
            margin-right: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #1e3a8a;
            border: 2px solid #e5e7eb;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        
        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> <strong>Faculty Only:</strong> Only lecturers and administrators can add academic events.</p>
        </div>
        
        <p>If you are a faculty member, please sign in with your institutional credentials.</p>
        
        <div style="margin-top: 30px;">
            <a href="login-lecturer.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Faculty Sign In
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Student Portal
            </a>
        </div>
    </div>
</body>
</html>