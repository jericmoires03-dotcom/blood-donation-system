<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../db.php';

// Get all appointments with donor details
$appointments = $conn->query("SELECT da.*, d.blood_type, u.name as donor_name, u.contact_no 
    FROM donor_appointments da
    JOIN donors d ON da.donor_id = d.donor_id
    JOIN users u ON d.user_id = u.user_id
    ORDER BY da.appointment_date ASC, da.appointment_time ASC");
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-check"></i> Donation Appointments</h2>
            <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card">
            <div class="card-body">
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
                                <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
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
                                        <form method="POST" action="update_appointment.php" class="m-0 p-0" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['appointment_id']) ?>">
                                            <input type="hidden" name="status" value="Completed">
                                            <button type="submit" class="btn btn-sm btn-success" <?= $appointment['status'] !== 'Scheduled' ? 'disabled' : '' ?> onclick="return confirm('Are you sure you want to mark this appointment as completed?')" style="min-width:38px;min-height:31px;"><i class="fas fa-check fa-fw"></i></button>
                                        </form>
                                        <form method="POST" action="update_appointment.php" class="m-0 p-0" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['appointment_id']) ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-sm btn-danger" <?= $appointment['status'] !== 'Scheduled' ? 'disabled' : '' ?> onclick="return confirm('Are you sure you want to cancel this appointment?')" style="min-width:38px;min-height:31px;"><i class="fas fa-times fa-fw"></i></button>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
