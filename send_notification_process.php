<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

// SMS Sending Function
function sendSMS($phone, $message) {
    // Simulate SMS API - replace with actual SMS service
    // Example: Twilio, Nexmo, or local SMS gateway
    error_log("Simulated SMS to $phone: $message");
    $success = true; // Replace with actual API call
    return $success;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $notification_type = isset($_POST['notification_type']) ? $_POST['notification_type'] : 'email';
    $recipient_type = $_POST['recipient_type'];
    $message = trim($_POST['message']);
      $subject = isset($_POST['subject']) ? $_POST['subject'] : 'Blood Center Notification';
    $user_ids = [];

    try {
        // Determine recipients based on type
        switch ($recipient_type) {
            case 'all_donors':
                $result = $conn->query("SELECT user_id FROM users WHERE role = 'Donor'");
                while ($row = $result->fetch_assoc()) {
                    $user_ids[] = $row['user_id'];
                }
                break;

            case 'all_seekers':
                $result = $conn->query("SELECT user_id FROM users WHERE role = 'Seeker'");
                while ($row = $result->fetch_assoc()) {
                    $user_ids[] = $row['user_id'];
                }
                break;

            case 'specific_user':
                $user_ids[] = $_POST['user_id'];
                break;

            case 'blood_type':
                $blood_type = $_POST['blood_type'];
                $result = $conn->query("SELECT u.user_id FROM users u 
                    JOIN donors d ON u.user_id = d.user_id 
                    WHERE d.blood_type = '$blood_type'");
                while ($row = $result->fetch_assoc()) {
                    $user_ids[] = $row['user_id'];
                }
                break;
        }

        $success_count = 0;
        $failed_count = 0;

        foreach ($user_ids as $user_id) {
            if ($notification_type === 'sms') {
                // Send SMS
                $user_query = $conn->prepare("SELECT contact_no FROM users WHERE user_id = ?");
                $user_query->bind_param("i", $user_id);
                $user_query->execute();
                $user_data = $user_query->get_result()->fetch_assoc();
                
                if ($user_data && sendSMS($user_data['contact_no'], $message)) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            } else {
                // Send Email/In-app notification
                $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
                $notifyStmt->bind_param("is", $user_id, $message);
                
                if ($notifyStmt->execute()) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Notifications sent successfully! Success: $success_count, Failed: $failed_count"
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send notifications: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    header("Location: ../config/dashboard/send_notifications.php");
}
exit;
?>