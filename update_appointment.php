<?php

session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
        $_SESSION['error'] = "Invalid request parameters";
        header("Location: view_appointments.php");
        exit;
    }

    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    // Update the appointment status
    $stmt = $conn->prepare("UPDATE donor_appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment status updated successfully";
    } else {
        $_SESSION['error'] = "Error updating appointment status";
    }
    
    $stmt->close();
    header("Location: view_appointments.php");
    exit;
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: view_appointments.php");
    exit;
}