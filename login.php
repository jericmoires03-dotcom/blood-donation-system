<?php
session_start();



// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            header("Location: config/dashboard/admin.php");
            break;
        case 'Staff':
            header("Location: config/dashboard/staff.php");
            break;
        case 'Donor':
            header("Location: config/dashboard/donor.php");
            break;
        case 'Seeker':
            header("Location: config/dashboard/seeker.php");
            break;
    }
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_success'] = true;



            switch ($user['role']) {
                case 'Admin':
                    header("Location: config/dashboard/admin.php");
                    break;
                case 'Staff':
                    header("Location: config/dashboard/staff.php");
                    break;
                case 'Donor':
                    header("Location: config/dashboard/donor.php");
                    break;
                case 'Seeker':
                    header("Location: config/dashboard/seeker.php");
                    break;
            }
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kidapawan City Blood Center</title>
    <link rel="icon" type ="image/png" href="LOGO.png" style="border-radius : 50%;">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
                 body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 50%, #90caf9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
        }  
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .role-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .register-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }
        .register-btn {
            margin: 5px;
            border-radius: 20px;
            padding: 10px 20px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-content {
            text-align: center;
            color: #dc3545;
        }
        
        .logo-spinner {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
            border: 3px solid rgba(220, 53, 69, 0.3);
            transform-style: preserve-3d;
        }
        
        @keyframes spin {
            0% { transform: rotateY(0deg); }
            100% { transform: rotateY(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card p-5">
                        <div class="text-center mb-4">
                        <img src="logo.png" alt="Kidapawan City Blood Center Logo" class="mb-3" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dc3545;">
                        <h3 class="fw-bold">Welcome to Kidapawan City Blood Center</h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Registration successful! Please login.
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 mb-3 btn-custom" id="loginBtn">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>

                        <div class="mb-3 text-center">
                            <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>

                    <!-- Registration Section -->
                    <div class="register-section">
                        <h6 class="mb-3">Don't have an account?</h6>
                        <a href="register.php" class="btn btn-info btn-lg btn-custom w-100">
                            <i class="fas fa-user-plus"></i>Create account
                        </a>
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-muted">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>