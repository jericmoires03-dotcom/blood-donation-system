<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone explicitly to avoid timezone issues
date_default_timezone_set('Asia/Manila');

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$user_id = $_SESSION['user_id'];

$donor_query = $conn->prepare("SELECT donor_id, blood_type FROM donors WHERE user_id = ?");
if (!$donor_query) {
    die("Error preparing query: " . $conn->error);
}
$donor_query->bind_param("i", $user_id);
if (!$donor_query->execute()) {
    die("Error executing query: " . $donor_query->error);
}
$result = $donor_query->get_result();
if (!$result) {
    die("Error getting result: " . $donor_query->error);
}
$donor = $result->fetch_assoc();


if (!$donor) {
    header("Location: donor.php?error=Donor profile not found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';

    // Enhanced validation
    $errors = [];
    if (!$appointment_date) {
        $errors[] = "Please select an appointment date.";
    }
    if (!$appointment_time) {
        $errors[] = "Please select an appointment time.";
    }

    // Validate appointment date is not in the past
    if ($appointment_date < date('Y-m-d')) {
        $errors[] = "Appointment date cannot be in the past.";
    }

    // Format time to ensure consistency - store in 24-hour format
    $formatted_time = date('H:i:s', strtotime($appointment_time));
    
    if (empty($errors)) {
        try {
            // Ensure the date and time are stored exactly as provided
            $stmt = $conn->prepare("INSERT INTO donor_appointments (donor_id, appointment_date, appointment_time, status) VALUES (?, DATE(?), TIME(?), 'Scheduled')");
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            if (!$stmt->bind_param("iss", $donor['donor_id'], $appointment_date, $formatted_time)) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . $stmt->error);
            }
            
            $success = "Appointment scheduled successfully for " . date('F d, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($formatted_time)) . ".";
            
        } catch (Exception $e) {
            $errors[] = "Failed to schedule appointment: " . $e->getMessage();
            error_log("Database error in schedule_donation.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Schedule Donation - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Schedule a Blood Donation Appointment</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="appointment_date" class="form-label">Appointment Date</label>
            <input type="date" id="appointment_date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required />
        </div>
        <div class="mb-3">
            <label for="appointment_time" class="form-label">Appointment Time</label>
            <input type="time" id="appointment_time" name="appointment_time" class="form-control" required />
        </div>
        <button type="submit" class="btn btn-primary">Schedule Appointment</button>
        <a href="donor.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
