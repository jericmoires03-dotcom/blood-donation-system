<?php
require_once '../config/db.php';

function sendRegistrationNotification($userId, $status, $notes = '') {
    global $conn;
    
    $messages = [
        'approved' => "Congratulations! Your donor registration has been approved. You are now eligible to donate blood and help save lives!",
        'rejected' => "We regret to inform you that your donor registration could not be approved at this time." . ($notes ? " Reason: $notes" : ""),
        'pending' => "Thank you for registering as a blood donor! Your application is under review. You will be notified once the process is complete."
    ];
    
$message = isset($messages[$status]) ? $messages[$status] : $messages['pending'];
    $type = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'warning' : 'info');
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $message, $type);
    
    return $stmt->execute();
}

// Auto-send notifications for status changes
function notifyStatusChange($donorId, $oldStatus, $newStatus, $notes = '') {
    global $conn;
    
    $donorQuery = $conn->prepare("SELECT d.user_id, u.name, u.email FROM donors d JOIN users u ON d.user_id = u.user_id WHERE d.donor_id = ?");
    $donorQuery->bind_param("i", $donorId);
    $donorQuery->execute();
    $donor = $donorQuery->get_result()->fetch_assoc();
    
    if ($donor) {
        sendRegistrationNotification($donor['user_id'], $newStatus, $notes);
        
        // Also notify staff about the status change
        $staffMessage = "Donor registration status updated: {$donor['name']} - Status changed from $oldStatus to $newStatus";
        $staffQuery = $conn->query("SELECT user_id FROM users WHERE role IN ('Admin', 'Staff')");
        while ($staff = $staffQuery->fetch_assoc()) {
            $notifyStaff = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
            $notifyStaff->bind_param("is", $staff['user_id'], $staffMessage);
            $notifyStaff->execute();
        }
    }
}
?>