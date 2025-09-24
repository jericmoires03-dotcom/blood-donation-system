<?php
session_start();
require_once '../config/db.php';

$message = '';
$message_type = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $request_id = intval($_POST['request_id']);
    $unit_id = intval($_POST['unit_id']);
    $seeker_id = intval($_POST['seeker_id']);
    $staff_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // 1. Check if request is still pending
        $checkRequest = $conn->prepare("SELECT * FROM blood_requests WHERE request_id = ? AND fulfilled_status = 0");
        $checkRequest->bind_param("i", $request_id);
        $checkRequest->execute();
        $requestData = $checkRequest->get_result()->fetch_assoc();

        if (!$requestData) {
            throw new Exception("Request not found or already fulfilled");
        }

        // 2. Mark request as fulfilled
        $fulfillRequest = $conn->prepare("UPDATE blood_requests SET fulfilled_status = 1 WHERE request_id = ?");
        $fulfillRequest->bind_param("i", $request_id);
        $fulfillRequest->execute();

        // 3. Update blood unit status
        $updateUnit = $conn->prepare("UPDATE blood_units SET available_status = 0 WHERE unit_id = ?");
        $updateUnit->bind_param("i", $unit_id);
        $updateUnit->execute();

        // 4. Commit transaction
        $conn->commit();
        
        $message = "Blood request fulfilled successfully! Request ID: $request_id has been completed using Blood Unit ID: $unit_id";
        $message_type = 'success';
        $success = true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Blood request fulfillment error: " . $e->getMessage());
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = "Invalid request method";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulfill Request - Blood Donation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .result-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
        }
        
        .error-icon {
            color: #dc3545;
        }
        
        h1 {
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .success h1 {
            color: #28a745;
        }
        
        .error h1 {
            color: #dc3545;
        }
        
        .message {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #545b62);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: white;
        }
        
        .auto-redirect {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="result-container <?= $message_type ?>">
        <?php if ($success): ?>
            <div class="icon success-icon">✅</div>
            <h1>Request Fulfilled Successfully!</h1>
        <?php else: ?>
            <div class="icon error-icon">❌</div>
            <h1>Request Failed</h1>
        <?php endif; ?>
        
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <div class="btn-container">
            <a href="../config/dashboard/match_requests.php" class="btn btn-primary">
                Back to Requests
            </a>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="../config/dashboard/admin.php" class="btn btn-secondary">
                    Admin Dashboard
                </a>
            <?php elseif ($_SESSION['role'] === 'Staff'): ?>
                <a href="../config/dashboard/staff.php" class="btn btn-secondary">
                    Staff Dashboard
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
        <div class="auto-redirect">
            <p>You will be redirected to the requests page in <span id="countdown">5</span> seconds...</p>
        </div>
        
        <script>
            let countdown = 5;
            const countdownElement = document.getElementById('countdown');
            
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.href = '../config/dashboard/match_requests.php';
                }
            }, 1000);
        </script>
        <?php endif; ?>
    </div>
</body>
</html>