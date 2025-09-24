<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seeker_id = $_POST['seeker_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $required_blood_type = $_POST['required_blood_type'];
    $location = $_POST['location'];
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update users table
        $update_user = $conn->prepare("UPDATE users SET name = ?, email = ?, contact_no = ? WHERE user_id = ?");
        $update_user->bind_param("sssi", $name, $email, $contact_no, $user_id);
        $update_user->execute();

        // Update seekers table
        $update_seeker = $conn->prepare("UPDATE seekers SET required_blood_type = ?, location = ? WHERE seeker_id = ?");
        $update_seeker->bind_param("ssi", $required_blood_type, $location, $seeker_id);
        $update_seeker->execute();

        $conn->commit();
        header("Location: ../config/dashboard/update_seeker_profile.php?success=Profile updated successfully");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../config/dashboard/update_seeker_profile.php?error=Failed to update profile");
        exit;
    }
}
?>