<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $notes = trim($_POST['notes']) ?: null;
    
    if ($action === 'approve') {
        // Update request status to approved
        $stmt = $conn->prepare("UPDATE blood_requests SET request_status = 'approved', processed_date = NOW(), processing_notes = ? WHERE request_id = ?");
        $stmt->bind_param("si", $notes, $request_id);
        
        if ($stmt->execute()) {
            // Get request and patient details
            $requestQuery = $conn->prepare("SELECT br.*, s.user_id, u.name, u.email FROM blood_requests br 
                JOIN seekers s ON br.seeker_id = s.seeker_id 
                JOIN users u ON s.user_id = u.user_id 
                WHERE br.request_id = ?");
            $requestQuery->bind_param("i", $request_id);
            $requestQuery->execute();
            $request = $requestQuery->get_result()->fetch_assoc();
            
            if ($request) {
                // Send approval notification to patient
                $message = "Good news! Your blood request (ID: $request_id) has been approved. Our team will contact you shortly with further details about blood availability and collection.";
                $notifyPatient = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'success', NOW())");
                $notifyPatient->bind_param("is", $request['user_id'], $message);
                $notifyPatient->execute();
                
                // Update seeker registration status
                $conn->prepare("UPDATE seekers SET registration_status = 'approved' WHERE user_id = ?")->execute([$request['user_id']]);
            }
            
            header("Location: ../config/dashboard/manage_blood_requests.php?success=Blood request approved successfully");
        }
    } else if ($action === 'reject') {
        // Update request status to rejected
        $stmt = $conn->prepare("UPDATE blood_requests SET request_status = 'rejected', processed_date = NOW(), processing_notes = ? WHERE request_id = ?");
        $stmt->bind_param("si", $notes, $request_id);
        
        if ($stmt->execute()) {
            // Get request and patient details
            $requestQuery = $conn->prepare("SELECT br.*, s.user_id, u.name FROM blood_requests br 
                JOIN seekers s ON br.seeker_id = s.seeker_id 
                JOIN users u ON s.user_id = u.user_id 
                WHERE br.request_id = ?");
            $requestQuery->bind_param("i", $request_id);
            $requestQuery->execute();
            $request = $requestQuery->get_result()->fetch_assoc();
            
            if ($request) {
                // Send rejection notification to patient
                $message = "We regret to inform you that your blood request (ID: $request_id) could not be approved at this time. " . ($notes ? "Reason: $notes" : "Please contact us for more information.");
                $notifyPatient = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'warning', NOW())");
                $notifyPatient->bind_param("is", $request['user_id'], $message);
                $notifyPatient->execute();
            }
            
            header("Location: ../config/dashboard/manage_blood_requests.php?success=Blood request rejected");
        }
    }
} else {
    header("Location: ../config/dashboard/manage_blood_requests.php");
}
?>