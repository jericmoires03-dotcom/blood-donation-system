<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Personal Details
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
    
    // Seeker specific details
    $hospital_id = isset($_POST['hospital_id']) ? $_POST['hospital_id'] : null;
    $location = trim($_POST['location']);
    $required_blood_type = $_POST['required_blood_type'];
    
    // Password
    $password = password_hash(isset($_POST['password']) ? $_POST['password'] : '', PASSWORD_DEFAULT);

    // Validation
    if ((isset($_POST['password']) ? $_POST['password'] : '') !== (isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '')) {
        header("Location: ../index.php?error=Passwords do not match");
        exit;
    }

    // Check if email already exists in users table
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        header("Location: ../index.php?error=Email already exists");
        exit;
    }

    // Check if contact number already exists
    $checkContact = $conn->prepare("SELECT contact_no FROM users WHERE contact_no = ?");
    $checkContact->bind_param("s", $contact_no);
    $checkContact->execute();
    if ($checkContact->get_result()->num_rows > 0) {
        header("Location: ../index.php?error=Contact number already exists");
        exit;
    }

    // Insert into users table first (matching donor_register.php schema)
    $stmt = $conn->prepare("INSERT INTO users (name, contact_no, email, password_hash, role) VALUES (?, ?, ?, ?, 'Seeker')");
    $stmt->bind_param("ssss", $name, $contact_no, $email, $password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Insert into seekers table
        $seekerStmt = $conn->prepare("INSERT INTO seekers (user_id, hospital_id, location, required_blood_type) VALUES (?, ?, ?, ?)");
        $seekerStmt->bind_param("iiss", $user_id, $hospital_id, $location, $required_blood_type);
        
        if ($seekerStmt->execute()) {
            $seeker_id = $seekerStmt->insert_id;
            
                        // Send notification to seeker
            $seekerMessage = "Thank you for registering as a blood seeker! Your registration is being reviewed. You will be notified once the process is complete.";
            $notifySeeker = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notifySeeker->bind_param("is", $user_id, $seekerMessage);
            $notifySeeker->execute();
            
            // Send notification to staff about new seeker registration
            $staffMessage = "New seeker registration: $name needs $required_blood_type blood at $location - ID: $seeker_id. Please review and approve.";
            $staffQuery = $conn->query("SELECT user_id FROM users WHERE role IN ('Admin', 'Staff')");
            while ($staff = $staffQuery->fetch_assoc()) {
                $notifyStaff = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
                $notifyStaff->bind_param("is", $staff['user_id'], $staffMessage);
                $notifyStaff->execute();
            }
            
            header("Location: ../index.php?success=Seeker registration successful! Your application is being reviewed. You can now login to access your account.");
            exit;
        } else {
            // Delete user record if seeker insertion fails
            $conn->query("DELETE FROM users WHERE user_id = $user_id");
            header("Location: ../index.php?error=Registration failed. Please try again.");
            exit;
        }
    } else {
        header("Location: ../index.php?error=Registration failed. Please try again.");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>