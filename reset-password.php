<?php
session_start();
require_once 'config/db.php';

$token = $_GET['token'] ?? '';
$valid_token = false;

if ($token) {
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $valid_token = true;
        $user = $result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password === $confirm_password) {
        if (strlen($password) >= 6) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
            $stmt->bind_param("si", $password_hash, $user['user_id']);
            $stmt->execute();
            
            $success = "Password reset successfully! You can now login.";
        } else {
            $error = "Password must be at least 6 characters long.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kidapawan City Blood Center</title>
    <link rel="icon" type="image/png" href="LOGO.png" style="border-radius: 50%;">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 50%, #90caf9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-card {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="reset-card p-5">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="Logo" class="mb-3" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dc3545;">
                        <h3 class="fw-bold">Reset Password</h3>
                        <p class="text-muted">Enter your new password</p>
                    </div>

                    <?php if (!$valid_token): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Invalid or expired reset token.
                        </div>
                        <div class="text-center">
                            <a href="forgot-password.php" class="btn btn-danger btn-custom">Request New Reset Link</a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= $success ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-danger btn-custom">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required minlength="6">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-danger w-100 mb-3 btn-custom">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="text-center">
                        <a href="login.php" class="text-muted">← Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>