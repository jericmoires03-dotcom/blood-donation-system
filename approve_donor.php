<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $donor_id = $_POST['donor_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $notes = trim($_POST['notes']) ?: null;
    
    if ($action === 'approve') {
        // Update donor status to approved
        $stmt = $conn->prepare("UPDATE donors SET registration_status = 'approved', approval_date = NOW(), approval_notes = ? WHERE donor_id = ?");
        $stmt->bind_param("si", $notes, $donor_id);
        
        if ($stmt->execute()) {
            // Get donor user_id and details
            $donorQuery = $conn->prepare("SELECT d.user_id, u.name, u.email FROM donors d JOIN users u ON d.user_id = u.user_id WHERE d.donor_id = ?");
            $donorQuery->bind_param("i", $donor_id);
            $donorQuery->execute();
            $donor = $donorQuery->get_result()->fetch_assoc();
            
            // Send approval notification to donor
            $message = "Congratulations! Your donor registration has been approved. You can now participate in blood donation activities. Welcome to our blood donor community!";
            $notifyDonor = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'success', NOW())");
            $notifyDonor->bind_param("is", $donor['user_id'], $message);
            $notifyDonor->execute();
            
            header("Location: ../config/dashboard/manage_donors.php?success=Donor approved successfully");
        }
    } else if ($action === 'reject') {
        // Update donor status to rejected
        $stmt = $conn->prepare("UPDATE donors SET registration_status = 'rejected', approval_date = NOW(), approval_notes = ? WHERE donor_id = ?");
        $stmt->bind_param("si", $notes, $donor_id);
        
        if ($stmt->execute()) {
            // Get donor user_id
            $donorQuery = $conn->prepare("SELECT d.user_id, u.name FROM donors d JOIN users u ON d.user_id = u.user_id WHERE d.donor_id = ?");
            $donorQuery->bind_param("i", $donor_id);
            $donorQuery->execute();
            $donor = $donorQuery->get_result()->fetch_assoc();
            
            // Send rejection notification to donor
            $message = "We regret to inform you that your donor registration could not be approved at this time. Reason: " . ($notes ?: "Please contact us for more information.");
            $notifyDonor = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'warning', NOW())");
            $notifyDonor->bind_param("is", $donor['user_id'], $message);
            $notifyDonor->execute();
            
            header("Location: ../config/dashboard/manage_donors.php?success=Donor registration rejected");
        }
    }
} else {
    header("Location: ../config/dashboard/manage_donors.php");
}
?>