<?php
session_start();
require_once 'config/db.php';
require_once 'config/smtp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        $reset_token = bin2hex(random_bytes(32));
        $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $reset_token, $reset_expires, $user['user_id']);
        if (!$stmt->execute()) {
            $error = "Database error: " . $stmt->error;
        }
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/blood-donation-system/reset-password.php?token=" . $reset_token;
        
        if (sendResetEmail($email, $user['name'], $reset_link)) {
            $success = "Password reset link has been sent to your email.";
        } else {
            $error = "Failed to send email. Please try again.";
        }
    } else {
        $error = "Email address not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Kidapawan City Blood Center</title>
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
        .forgot-card {
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
                <div class="forgot-card p-5">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="Logo" class="mb-3" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dc3545;">
                        <h3 class="fw-bold">Forgot Password</h3>
                        <p class="text-muted">Enter your email address to receive a password reset link</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>



                        <button type="submit" class="btn btn-danger w-100 mb-3 btn-custom">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="login.php" class="text-muted">← Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>