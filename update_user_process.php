<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_POST['user_id']);
    $current_role = $_POST['current_role'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $new_role = $_POST['role'];
    
    // Check email uniqueness
    $check = $conn->query("SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id");
    if ($check->num_rows > 0) {
        header("Location: ../config/dashboard/edit_user.php?id=$user_id&error=Email already exists");
        exit;
    }
    
    // Update basic user info
    if (isset($_POST['reset_password'])) {
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name = '$name', email = '$email', contact_no = '$contact_no', role = '$new_role', password_hash = '$password' WHERE user_id = $user_id";
    } else {
        $sql = "UPDATE users SET name = '$name', email = '$email', contact_no = '$contact_no', role = '$new_role' WHERE user_id = $user_id";
    }
    
    if ($conn->query($sql)) {
        // Handle role-specific updates
        if ($new_role == 'Donor') {
            $blood_type = $_POST['blood_type'] ?? 'Unknown';
            $availability_status = $_POST['availability_status'] ?? 1;
            
            // Check if donor record exists
            $donorCheck = $conn->query("SELECT donor_id FROM donors WHERE user_id = $user_id");
            if ($donorCheck->num_rows > 0) {
                $conn->query("UPDATE donors SET blood_type = '$blood_type', availability_status = $availability_status WHERE user_id = $user_id");
            } else {
                $conn->query("INSERT INTO donors (user_id, blood_type, availability_status) VALUES ($user_id, '$blood_type', $availability_status)");
            }
        }
        
        if ($new_role == 'Seeker') {
            $required_blood_type = $_POST['required_blood_type'] ?? 'N/A';
            $location = $_POST['location'] ?? 'N/A';
            
            // Check if seeker record exists
            $seekerCheck = $conn->query("SELECT seeker_id FROM seekers WHERE user_id = $user_id");
            if ($seekerCheck->num_rows > 0) {
                $conn->query("UPDATE seekers SET required_blood_type = '$required_blood_type', location = '$location' WHERE user_id = $user_id");
            } else {
                $conn->query("INSERT INTO seekers (user_id, location, required_blood_type) VALUES ($user_id, '$location', '$required_blood_type')");
            }
        }
        
        if ($new_role == 'Staff') {
            $staffCheck = $conn->query("SELECT staff_id FROM staff WHERE user_id = $user_id");
            if ($staffCheck->num_rows == 0) {
                $conn->query("INSERT INTO staff (user_id, department, designation) VALUES ($user_id, 'General', 'Staff')");
            }
        }
        
        if ($new_role == 'Admin') {
            $adminCheck = $conn->query("SELECT admin_id FROM admin WHERE user_id = $user_id");
            if ($adminCheck->num_rows == 0) {
                $conn->query("INSERT INTO admin (user_id) VALUES ($user_id)");
            }
        }
        
        header("Location: ../config/dashboard/manage_users.php?success=User updated successfully");
    } else {
        header("Location: ../config/dashboard/edit_user.php?id=$user_id&error=Update failed");
    }
    exit;
}

header("Location: ../config/dashboard/manage_users.php");
exit;
?>