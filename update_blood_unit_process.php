<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

// Validation function
function validateBloodUnitUpdate($unitId, $testResults, $status) {
    $errors = [];
    
    if (empty($unitId) || $unitId <= 0) {
        $errors[] = "Valid unit ID is required";
    }
    
    if (empty($testResults)) {
        $errors[] = "Test results are required";
    }
    
    if (!in_array($status, [0, 1])) {
        $errors[] = "Valid status is required";
    }
    
    return $errors;
}

// Check if blood unit exists
function checkBloodUnitExists($conn, $unitId) {
    $stmt = $conn->prepare("SELECT unit_id FROM blood_units WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unit_id = intval($_POST['unit_id']);
    $test_results = trim($_POST['test_results']);
    $status = intval($_POST['available_status']);
    
    // Validate input
    $errors = validateBloodUnitUpdate($unit_id, $test_results, $status);
    
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: ../config/dashboard/inventory.php?error=" . urlencode($error_message));
        exit;
    }
    
    // Check if blood unit exists
    if (!checkBloodUnitExists($conn, $unit_id)) {
        header("Location: ../config/dashboard/inventory.php?error=Blood unit not found");
        exit;
    }
    
    try {
        // Update blood unit
        $stmt = $conn->prepare("UPDATE blood_units SET test_results = ?, available_status = ? WHERE unit_id = ?");
        $stmt->bind_param("sii", $test_results, $status, $unit_id);
        
        if ($stmt->execute()) {
            header("Location: ../config/dashboard/inventory.php?success=Blood unit updated successfully");
        } else {
            throw new Exception("Database update failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Blood unit update error: " . $e->getMessage());
        header("Location: ../config/dashboard/inventory.php?error=Failed to update blood unit");
    }
    
} else {
    // If not POST request, redirect to inventory
    header("Location: ../config/dashboard/inventory.php");
}

exit;
?>