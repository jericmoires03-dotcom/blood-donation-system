<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seeker') {
    header("Location: ../../login.php");
    exit;
}

require_once '../config/db.php';

// Function to validate blood type
function isValidBloodType($bloodType) {
    $validTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    return in_array($bloodType, $validTypes);
}

// Function to get seeker information
function getSeekerInfo($conn, $userId) {
    $stmt = $conn->prepare("SELECT s.*, u.name, u.contact_no FROM seekers s JOIN users u ON s.user_id = u.user_id WHERE s.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to create blood request
function createBloodRequest($conn, $seekerId, $bloodType, $quantity, $reason, $unitsNeeded) {
    $stmt = $conn->prepare("INSERT INTO blood_requests 
        (seeker_id, required_blood_type, quantity, request_date, fulfilled_status, reason, units_needed) 
        VALUES (?, ?, ?, CURRENT_DATE, 0, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isdsi", $seekerId, $bloodType, $quantity, $reason, $unitsNeeded);
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Execute failed: " . $stmt->error);
    }
    return $result;
}

// Function to send notification to admin
function sendNotificationToAdmin($conn, $requestId, $bloodType, $quantity) {
    $message = "New blood request #$requestId: $quantity ml of $bloodType blood type requested.";
    
    // Check which date column exists in notifications table
    $dateColumns = $conn->query("SHOW COLUMNS FROM notifications WHERE Field IN ('created_at', 'sent_date')");
    $availableColumns = [];
    while ($col = $dateColumns->fetch_assoc()) {
        $availableColumns[] = $col['Field'];
    }
    
    $dateColumn = in_array('created_at', $availableColumns) ? 'created_at' : 'sent_date';
    
    // Get all admin users
    $adminQuery = $conn->query("SELECT user_id FROM users WHERE role = 'Admin'");
    
    if ($adminQuery && $adminQuery->num_rows > 0) {
        while ($admin = $adminQuery->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, $dateColumn) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("is", $admin['user_id'], $message);
                $stmt->execute();
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Get seeker info
        $seeker_info = getSeekerInfo($conn, $_SESSION['user_id']);
        if (!$seeker_info) {
            throw new Exception("Seeker profile not found");
        }

        // Validate input data
        if (!isset($_POST['blood_type']) || !isset($_POST['units_needed']) || !isset($_POST['reason'])) {
            throw new Exception("Missing required fields");
        }

        $blood_type = trim($_POST['blood_type']);
        $units_needed = intval($_POST['units_needed']);
        $reason = trim($_POST['reason']);
        
        // Units are already in milliliters
        $quantity = $units_needed;

        // Validate blood type
        if (!isValidBloodType($blood_type)) {
            throw new Exception("Invalid blood type");
        }

        // Validate quantity
        if ($quantity < 100 || $quantity > 450) {
            throw new Exception("Invalid quantity requested. Must be between 100ml and 450ml");
        }

        // Create blood request
        if (createBloodRequest($conn, $seeker_info['seeker_id'], $blood_type, $quantity, $reason, $units_needed)) {
            $request_id = $conn->insert_id;
            
            // Send notification to admin
            try {
                sendNotificationToAdmin($conn, $request_id, $blood_type, $quantity);
            } catch (Exception $e) {
                error_log("Notification error: " . $e->getMessage());
            }
            
            $_SESSION['success_message'] = "Blood request submitted successfully! Request ID: " . $request_id;
            header("Location: ../config/dashboard/seeker.php");
            exit;
        } else {
            $error_msg = "Failed to create blood request. Database error: " . $conn->error;
            error_log($error_msg);
            throw new Exception($error_msg);
        }

    } catch (Exception $e) {
        error_log("Blood request error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: ../config/dashboard/seeker.php");
        exit;
    }
} else {
    header("Location: ../config/dashboard/seeker.php");
    exit;
}
?>
