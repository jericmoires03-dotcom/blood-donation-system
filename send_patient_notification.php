<?php
require_once '../config/db.php';

function sendPatientNotification($userId, $status, $requestId, $notes = '') {
    global $conn;
    
    $messages = [
        'approved' => "Good news! Your blood request (ID: $requestId) has been approved. Our team will contact you shortly with further details.",
        'rejected' => "We regret to inform you that your blood request (ID: $requestId) could not be approved at this time." . ($notes ? " Reason: $notes" : ""),
        'pending' => "Thank you for submitting your blood request (ID: $requestId)! Your request is under review. You will be notified once processed.",
        'fulfilled' => "Great news! Your blood request (ID: $requestId) has been fulfilled. Please contact the blood bank for collection details.",
        'urgent_update' => "Update on your blood request (ID: $requestId): We are actively searching for matching blood donors. We will keep you informed."
    ];
    $message = isset($messages[$status]) ? $messages[$status] : $messages['pending']; 
    $type = $status === 'approved' || $status === 'fulfilled' ? 'success' : ($status === 'rejected' ? 'warning' : 'info');
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $message, $type);
    
    return $stmt->execute();
}

// Auto-send notifications for request status changes
function notifyRequestStatusChange($requestId, $oldStatus, $newStatus, $notes = '') {
    global $conn;
    
    $requestQuery = $conn->prepare("SELECT br.*, s.user_id, u.name, u.email FROM blood_requests br 
        JOIN seekers s ON br.seeker_id = s.seeker_id 
        JOIN users u ON s.user_id = u.user_id 
        WHERE br.request_id = ?");
    $requestQuery->bind_param("i", $requestId);
    $requestQuery->execute();
    $request = $requestQuery->get_result()->fetch_assoc();
    
    if ($request) {
        sendPatientNotification($request['user_id'], $newStatus, $requestId, $notes);
        
        // Also notify staff about the status change
        $staffMessage = "Blood request status updated: {$request['name']} (Request ID: $requestId) - Status changed from $oldStatus to $newStatus";
        $staffQuery = $conn->query("SELECT user_id FROM users WHERE role IN ('Admin', 'Staff')");
        while ($staff = $staffQuery->fetch_assoc()) {
            $notifyStaff = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
            $notifyStaff->bind_param("is", $staff['user_id'], $staffMessage);
            $notifyStaff->execute();
        }
    }
}

// Send urgent notifications for critical blood requests
function sendUrgentBloodAlert($bloodType, $unitsNeeded, $patientName, $requestId) {
    global $conn;
    
    $urgentMessage = "URGENT BLOOD NEEDED: $unitsNeeded units of $bloodType blood required for patient $patientName (Request ID: $requestId). Please check availability immediately!";
    
    // Notify all staff
    $staffQuery = $conn->query("SELECT user_id FROM users WHERE role IN ('Admin', 'Staff')");
    while ($staff = $staffQuery->fetch_assoc()) {
        $notifyStaff = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'danger', NOW())");
        $notifyStaff->bind_param("is", $staff['user_id'], $urgentMessage);
        $notifyStaff->execute();
    }
    
    // Notify compatible donors
    $donorMessage = "Urgent blood donation needed! We need $unitsNeeded units of $bloodType blood. Your donation could save a life. Please contact us if you're available to donate.";
    $donorQuery = $conn->query("SELECT u.user_id FROM users u 
        JOIN donors d ON u.user_id = d.user_id 
        WHERE d.blood_type = '$bloodType' AND d.availability_status = 1 AND d.registration_status = 'approved'");
    while ($donor = $donorQuery->fetch_assoc()) {
        $notifyDonor = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'warning', NOW())");
        $notifyDonor->bind_param("is", $donor['user_id'], $donorMessage);
        $notifyDonor->execute();
    }
}
?>