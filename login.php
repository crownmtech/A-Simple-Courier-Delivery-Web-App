<?php
require_once 'config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple login for demo (in production, use proper authentication)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@crownmatrixtech.com.ng',
            'role' => 'admin'
        ];
        header('Location: admin.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrownCourier Admin - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        
        .login-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #e30613;
            font-size: 32px;
        }
        
        .logo span {
            color: #1e293b;
        }
        
        .logo p {
            color: #6b7280;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #e30613;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #e30613;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #c10510;
        }
        
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .demo-credentials {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f9ff;
            border-radius: 6px;
            border-left: 4px solid #0ea5e9;
        }
        
        .demo-credentials h4 {
            color: #0369a1;
            margin-bottom: 5px;
        }
        
        .demo-credentials p {
            color: #475569;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1>CROWN<span>COURIER</span></h1>
                <p>Admin Login</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>