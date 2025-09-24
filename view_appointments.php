<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set the same timezone as schedule_donation.php
date_default_timezone_set('Asia/Manila');

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$appointments = $conn->query("SELECT 
    da.id,
    DATE(da.appointment_date) as appointment_date,
    TIME(da.appointment_time) as appointment_time,
    da.status,
    d.blood_type,
    u.name as donor_name,
    u.contact_no 
    FROM donor_appointments da
    INNER JOIN donors d ON da.donor_id = d.donor_id
    INNER JOIN users u ON d.user_id = u.user_id
    WHERE da.status = 'Scheduled'
    ORDER BY da.appointment_date ASC, da.appointment_time ASC");

// Add this after the query to debug
if (!$appointments) {
    error_log("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-check"></i> Donation Appointments</h2>
    <div>
        <a href="appointment_history.php" class="btn btn-primary me-2">
            <i class="fas fa-history"></i> View History
        </a>
        <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

       <div class="card">
    <div class="card-body">
        <?php if ($appointments->num_rows === 0): ?>
            <div class="alert alert-info">No scheduled appointments found.</div>
        <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Donor Name</th>
                                <th>Blood Type</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                                        <tr>
                                <td><?= date('F d, Y', strtotime($appointment['appointment_date'])) ?></td>
                                <td><?= date('g:i A', strtotime($appointment['appointment_time'])) ?></td>
                                <td><?= htmlspecialchars($appointment['donor_name']) ?></td>
                                <td><span class="badge bg-danger"><?= $appointment['blood_type'] ?></span></td>
                                <td><?= $appointment['contact_no'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $appointment['status'] === 'Scheduled' ? 'warning' : 
                                        ($appointment['status'] === 'Completed' ? 'success' : 'secondary') ?>">
                                        <?= $appointment['status'] ?>
                                    </span>
                                </td>
                     <td>
    <div class="d-flex gap-2">
        <form method="POST" action="update_appointment.php" style="margin:0; padding:0;">
            <input type="hidden" name="appointment_id" value="<?= isset($appointment['id']) ? htmlspecialchars($appointment['id']) : '' ?>">
            <input type="hidden" name="status" value="Completed">
            <button type="submit" class="btn btn-sm btn-success" 
                <?= !isset($appointment['status']) || $appointment['status'] !== 'Scheduled' ? 'disabled' : '' ?>
                onclick="return confirm('Are you sure you want to mark this appointment as completed?')"
                style="min-width: 38px; min-height: 31px;">
                <i class="fas fa-check fa-fw"></i>
            </button>
        </form>
        <form method="POST" action="update_appointment.php" style="margin:0; padding:0;">
            <input type="hidden" name="appointment_id" value="<?= isset($appointment['id']) ? htmlspecialchars($appointment['id']) : '' ?>">
            <input type="hidden" name="status" value="Cancelled">
            <button type="submit" class="btn btn-sm btn-danger"
                <?= !isset($appointment['status']) || $appointment['status'] !== 'Scheduled' ? 'disabled' : '' ?>
                onclick="return confirm('Are you sure you want to cancel this appointment?')"
                style="min-width: 38px; min-height: 31px;">
                <i class="fas fa-times fa-fw"></i>
            </button>
        </form>
    </div>
</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>