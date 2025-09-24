<?php
session_start();
$_SESSION = array();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Kidapawan City Blood Center</title>
    <link rel="icon" type="image/png" href="LOGO.png">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        
        .logout-content {
            text-align: center;
            color: white;
        }
        
        .logo-spinner {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transform-style: preserve-3d;
        }
        
        @keyframes spin {
            0% { transform: rotateY(0deg); }
            100% { transform: rotateY(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-content">
        <img src="logo.png" alt="Loading" class="logo-spinner">
        <h4>Logging out...</h4>
        <p>Thank you for using Kidapawan City Blood Center!</p>
    </div>
    
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
    </script>
</body>
</html>
