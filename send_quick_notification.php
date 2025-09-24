<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';

$messages = [
    'urgent' => 'URGENT: Blood needed immediately! Your donation can save lives. Please contact us ASAP. Blood Center',
    'reminder' => 'Reminder: You are eligible to donate blood. Your contribution makes a difference. Schedule today!',
    'thankyou' => 'Thank you for your recent blood donation! Your generosity helps save lives in our community.',
    'appointment' => 'Appointment Reminder: Your blood donation is scheduled. Please arrive 15 minutes early.'
];

if (!isset($messages[$type])) {
    header("Location: ../config/dashboard/send_notifications.php?error=Invalid notification type");
    exit;
}

$message = $messages[$type];

try {
    switch ($type) {
        case 'urgent':
            // Send to all available donors
            $recipients = $conn->query("SELECT u.user_id, u.contact_no FROM users u 
                JOIN donors d ON u.user_id = d.user_id 
                WHERE d.availability_status = 1");
            break;
            
        case 'reminder':
            // Send to donors who haven't donated in 3 months
            $recipients = $conn->query("SELECT u.user_id, u.contact_no FROM users u 
                JOIN donors d ON u.user_id = d.user_id 
                WHERE d.last_donation_date IS NULL OR d.last_donation_date < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
            break;
            
        case 'thankyou':
            // Send to recent donors (last 7 days)
            $recipients = $conn->query("SELECT u.user_id, u.contact_no FROM users u 
                JOIN donors d ON u.user_id = d.user_id 
                WHERE d.last_donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            break;
            
        case 'appointment':
            // Send to donors with upcoming appointments (if you have appointment system)
            $recipients = $conn->query("SELECT u.user_id, u.contact_no FROM users u 
                JOIN donors d ON u.user_id = d.user_id 
                WHERE d.availability_status = 1 LIMIT 10");
            break;
    }

    $count = 0;
    while ($recipient = $recipients->fetch_assoc()) {
        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
        $stmt->bind_param("is", $recipient['user_id'], $message);
        $stmt->execute();
        $count++;
    }

    header("Location: ../config/dashboard/send_notifications.php?success=Quick notification sent to $count recipients");

} catch (Exception $e) {
    error_log("Quick notification error: " . $e->getMessage());
    header("Location: ../config/dashboard/send_notifications.php?error=Failed to send quick notification");
}
exit;
?>