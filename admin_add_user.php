<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function checkEmailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function checkContactExists($conn, $contact) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE contact_no = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function addUser($conn, $name, $email, $contact, $password, $role, $bloodType = 'Unknown', $availability = 'Available', $location = 'N/A', $requiredBlood = 'N/A') {
    $conn->begin_transaction();
    
    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (name, contact_no, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $name, $contact, $email, $passwordHash, $role);
        
        if (!$stmt->execute()) {
            throw new \Exception("Failed to create user");
        }
        
        $userId = $stmt->insert_id;
        
        // Add role-specific data
        switch ($role) {
            case 'Staff':
                $stmt = $conn->prepare("INSERT INTO staff (user_id, department, designation) VALUES (?, 'General', 'Staff')");
                $stmt->bind_param("i", $userId);
                if (!$stmt->execute()) {
                    throw new \Exception("Failed to create staff record");
                }
                break;
                
            case 'Admin':
                $stmt = $conn->prepare("INSERT INTO admin (user_id) VALUES (?)");
                $stmt->bind_param("i", $userId);
                if (!$stmt->execute()) {
                    throw new \Exception("Failed to create admin record");
                }
                break;
                
            case 'Donor':
                $stmt = $conn->prepare("INSERT INTO donors (user_id, blood_type, availability_status) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $userId, $bloodType, $availability);
                if (!$stmt->execute()) {
                    throw new \Exception("Failed to create donor record");
                }
                break;
                
            case 'Seeker':
                $stmt = $conn->prepare("INSERT INTO seekers (user_id, location, required_blood_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $userId, $location, $requiredBlood);
                if (!$stmt->execute()) {
                    throw new \Exception("Failed to create seeker record");
                }
                break;
        }
        
        $conn->commit();
        return $userId;
        
    } catch (\Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($name) || empty($email) || empty($contact_no) || empty($password) || empty($role)) {
        header("Location: ../config/dashboard/manage_users.php?error=All fields are required");
        exit;
    }
    
    if (!validateEmail($email)) {
        header("Location: ../config/dashboard/manage_users.php?error=Invalid email format");
        exit;
    }
    
    if (checkEmailExists($conn, $email)) {
        header("Location: ../config/dashboard/manage_users.php?error=Email already exists");
        exit;
    }
    
    if (checkContactExists($conn, $contact_no)) {
        header("Location: ../config/dashboard/manage_users.php?error=Contact number already exists");
        exit;
    }
    
    try {
        // Extract additional parameters for Donor and Seeker roles
        $bloodType = 'Unknown';
        $availability = 'Available';
        $location = 'N/A';
        $requiredBlood = 'N/A';
        
        if ($role === 'Donor') {
            $bloodType = isset($_POST['blood_type']) ? trim($_POST['blood_type']) : 'Unknown';
            $availability = isset($_POST['availability_status']) ? trim($_POST['availability_status']) : 'Available';
        } elseif ($role === 'Seeker') {
            $location = isset($_POST['location']) ? trim($_POST['location']) : 'N/A';
            $requiredBlood = isset($_POST['required_blood_type']) ? trim($_POST['required_blood_type']) : 'N/A';
        }
        
        $user_id = addUser($conn, $name, $email, $contact_no, $password, $role, $bloodType, $availability, $location, $requiredBlood);
        header("Location: ../config/dashboard/manage_users.php?success=User added successfully");
    } catch (\Exception $e) {
        header("Location: ../config/dashboard/manage_users.php?error=Failed to add user: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../config/dashboard/manage_users.php");
exit;
?>