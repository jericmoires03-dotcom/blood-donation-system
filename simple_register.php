<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = trim($_POST['address']);
    $role = $_POST['role'];

    // Validation
    if (empty($name) || empty($email) || empty($contact_no) || empty($_POST['password']) || empty($role)) {
        header("Location: ../register.php?error=All fields are required");
        exit;
    }

        // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        header("Location: ../register.php?error=Email already exists");
        exit;
    }

    // Check if contact number already exists
    $checkContact = $conn->prepare("SELECT contact_no FROM users WHERE contact_no = ?");
    $checkContact->bind_param("s", $contact_no);
    $checkContact->execute();
    if ($checkContact->get_result()->num_rows > 0) {
        header("Location: ../register.php?error=Contact number already exists");
        exit;
    }


    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (name, contact_no, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $contact_no, $email, $password, $role);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create user");
        }

        $user_id = $stmt->insert_id;

        // Create role-specific records
        if ($role === 'Donor') {
            $donorStmt = $conn->prepare("INSERT INTO donors (user_id, blood_type, availability_status) VALUES (?, 'Unknown', 1)");
            $donorStmt->bind_param("i", $user_id);
            if (!$donorStmt->execute()) {
                throw new Exception("Failed to create donor record");
            }
            
                      // Send notification to donor
            $donorMessage = "Thank you for registering as a blood donor! Please complete your profile with blood type information.";
            $notifyDonor = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notifyDonor->bind_param("is", $user_id, $donorMessage);
            $notifyDonor->execute();
            
        } else if ($role === 'Seeker') {
            $seekerStmt = $conn->prepare("INSERT INTO seekers (user_id, location, required_blood_type) VALUES (?, ?, 'Unknown')");
            $seekerStmt->bind_param("is", $user_id, $address);
            if (!$seekerStmt->execute()) {
                throw new Exception("Failed to create seeker record");
            }
            
            // Send notification to seeker
            $seekerMessage = "Thank you for registering as a blood seeker! Please complete your profile with required blood type information.";
            $notifySeeker = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notifySeeker->bind_param("is", $user_id, $seekerMessage);
            $notifySeeker->execute(); 
        }

         // Commit transaction
        $conn->commit();
        
        header("Location: ../login.php?success=Registration successful! You can now login to your account.");
        exit;

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        header("Location: ../register.php?error=Registration failed: " . $e->getMessage());
        exit;
    }
} else {
    header("Location: ../register.php");
    exit;
}
?>