<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id = $_POST['donor_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $blood_type = $_POST['blood_type'];
    $availability_status = $_POST['availability_status'];
    $last_donation_date = $_POST['last_donation_date'];
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update users table
        $update_user = $conn->prepare("UPDATE users SET name = ?, email = ?, contact_no = ? WHERE user_id = ?");
        $update_user->bind_param("sssi", $name, $email, $contact_no, $user_id);
        $update_user->execute();

        // Update donors table
        $update_donor = $conn->prepare("UPDATE donors SET blood_type = ?, availability_status = ?, last_donation_date = ? WHERE donor_id = ?");
        $update_donor->bind_param("sisi", $blood_type, $availability_status, $last_donation_date, $donor_id);
        $update_donor->execute();

        $conn->commit();
        header("Location: ../config/dashboard/donor_profile.php?success=Profile updated successfully");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../config/dashboard/donor_profile.php?error=Failed to update profile");
        exit;
    }
}
?>