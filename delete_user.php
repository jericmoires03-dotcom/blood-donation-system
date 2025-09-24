<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: ../config/dashboard/manage_users.php?error=Invalid user ID");
    exit;
}

$user_id = intval($_GET['id']);

// Prevent self-deletion
if ($user_id == $_SESSION['user_id']) {
    header("Location: ../config/dashboard/manage_users.php?error=Cannot delete your own account");
    exit;
}

// Get user info before deletion
$userQuery = $conn->prepare("SELECT name, role FROM users WHERE user_id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$result = $userQuery->get_result();

if ($result->num_rows === 0) {
    header("Location: ../config/dashboard/manage_users.php?error=User not found");
    exit;
}

$user = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Delete role-specific data first
    switch ($user['role']) {
        case 'Donor':
            // Delete blood units first
            $stmt1 = $conn->prepare("DELETE FROM blood_units WHERE donor_id IN (SELECT donor_id FROM donors WHERE user_id = ?)");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            
            // Delete donor record
            $stmt2 = $conn->prepare("DELETE FROM donors WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            break;
            
        case 'Seeker':
            // Delete blood requests first
            $stmt1 = $conn->prepare("DELETE FROM blood_requests WHERE seeker_id IN (SELECT seeker_id FROM seekers WHERE user_id = ?)");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            
            // Delete seeker record
            $stmt2 = $conn->prepare("DELETE FROM seekers WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            break;
            
        case 'Staff':
            $stmt = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
            
        case 'Admin':
            $stmt = $conn->prepare("DELETE FROM admin WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
    }

    // Delete user record
    $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $deleteUser->bind_param("i", $user_id);
    $deleteUser->execute();

    // Commit transaction
    $conn->commit();
    
    header("Location: ../config/dashboard/manage_users.php?success=User " . urlencode($user['name']) . " deleted successfully");
    exit;

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    header("Location: ../config/dashboard/manage_users.php?error=Failed to delete user: " . $e->getMessage());
    exit;
}
?>