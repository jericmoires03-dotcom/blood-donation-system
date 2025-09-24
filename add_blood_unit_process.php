<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

function validateBloodUnit($donorId, $bloodType, $quantity) {
    $errors = [];
    
    if (empty($donorId) || $donorId <= 0) {
        $errors[] = "Valid donor ID is required";
    }
    
    if (empty($bloodType)) {
        $errors[] = "Blood type is required";
    }
    
    if (empty($quantity) || $quantity <= 0) {
        $errors[] = "Valid quantity is required";
    }
    
    return $errors;
}

function checkDonorExists($conn, $donorId) {
    $stmt = $conn->prepare("SELECT donor_id FROM donors WHERE donor_id = ?");
    $stmt->bind_param("i", $donorId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $donor_id = intval($_POST['donor_id']);
    $blood_type = trim($_POST['blood_type']);
    $quantity = floatval($_POST['quantity']);
    
    // Validate input
    $errors = validateBloodUnit($donor_id, $blood_type, $quantity);
    
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: ../config/dashboard/add_blood_unit.php?error=" . urlencode($error_message));
        exit;
    }
    
    // Check if donor exists
    if (!checkDonorExists($conn, $donor_id)) {
        header("Location: ../config/dashboard/add_blood_unit.php?error=Donor not found");
        exit;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO blood_units (donor_id, blood_type, quantity, available_status) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("isd", $donor_id, $blood_type, $quantity);
        
        if ($stmt->execute()) {
            header("Location: ../config/dashboard/inventory.php?success=Blood unit added successfully");
        } else {
            throw new Exception("Database insertion failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Blood unit addition error: " . $e->getMessage());
        header("Location: ../config/dashboard/add_blood_unit.php?error=Failed to add blood unit: " . urlencode($e->getMessage()));
    }
} else {
    header("Location: ../config/dashboard/add_blood_unit.php");
}
?>