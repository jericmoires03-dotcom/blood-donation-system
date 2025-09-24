<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM donor_appointments WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: appointment_history.php");
        exit;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Get completed and cancelled appointments
$query = "SELECT da.*, d.blood_type, u.name as donor_name, u.contact_no 
    FROM donor_appointments da
    JOIN donors d ON da.donor_id = d.donor_id
    JOIN users u ON d.user_id = u.user_id
    WHERE da.status IN ('Completed', 'Cancelled')
    ORDER BY da.appointment_date DESC, da.appointment_time DESC";
$appointments = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history"></i> Appointment History</h2>
            <div>
                <a href="view_appointments.php" class="btn btn-primary me-2">
                    <i class="fas fa-calendar"></i> Current Appointments
                </a>
                <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <select class="form-select w-auto" id="statusFilter">
                        <option value="all">All History</option>
                        <option value="Completed">Completed Only</option>
                        <option value="Cancelled">Cancelled Only</option>
                    </select>
                </div>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <tr class="appointment-row" data-status="<?= $appointment['status'] ?>">
                                <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                                <td><?= htmlspecialchars($appointment['donor_name']) ?></td>
                                <td><span class="badge bg-danger"><?= $appointment['blood_type'] ?></span></td>
                                <td><?= $appointment['contact_no'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $appointment['status'] === 'Completed' ? 'success' : 'secondary' ?>">
                                        <?= $appointment['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this appointment?')">
                                        <input type="hidden" name="delete_id" value="<?= $appointment['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
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
    <script>
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('.appointment-row');
            
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>