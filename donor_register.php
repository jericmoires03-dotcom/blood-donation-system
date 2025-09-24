<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Personal Details
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $password = password_hash(isset($_POST['password']) ? $_POST['password'] : '', PASSWORD_DEFAULT);
    $address = trim($_POST['address']);
    
    // Medical Details
    $blood_type = $_POST['blood_type'];
    $availability_status = $_POST['availability_status'];
    $last_donation_date = $_POST['last_donation_date'] ?: null;

    // Check if email already exists
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

    $stmt = $conn->prepare("INSERT INTO users (name, contact_no, email, password_hash, role) VALUES (?, ?, ?, ?, 'Donor')");
$stmt->bind_param("ssss", $name, $contact_no, $email, $password);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Insert into donors table with only existing columns
        $donorStmt = $conn->prepare("INSERT INTO donors (user_id, blood_type, availability_status, last_donation_date) VALUES (?, ?, ?, ?)");
        $donorStmt->bind_param("isis", $user_id, $blood_type, $availability_status, $last_donation_date);

        if ($donorStmt->execute()) {
            $donor_id = $donorStmt->insert_id;
            
            // Send notification to donor
            $donorMessage = "Thank you for registering as a blood donor! Your application is being reviewed. You will be notified once the process is complete.";
           $notifyDonor = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
$notifyDonor->bind_param("is", $user_id, $donorMessage);
            $notifyDonor->execute();
            
            // Send notification to staff about new donor registration
            $staffMessage = "New donor registration: $name ($blood_type) - ID: $donor_id. Please review and approve.";
            $staffQuery = $conn->query("SELECT user_id FROM users WHERE role IN ('Admin', 'Staff')");
            while ($staff = $staffQuery->fetch_assoc()) {
                $notifyStaff = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'info', NOW())");
                $notifyStaff->bind_param("is", $staff['user_id'], $staffMessage);
                $notifyStaff->execute();
            }
            
            header("Location: ../index.php?success=Donor registration successful! Your application is being reviewed. You will receive a notification once approved.");
            exit;
        } else {
            // Delete user record if donor insertion fails
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