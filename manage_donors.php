<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get pending donor registrations
$pending_donors = $conn->query("SELECT d.*, u.name, u.email, u.contact_no, u.date_of_birth, u.gender, u.address 
    FROM donors d 
    JOIN users u ON d.user_id = u.user_id 
    WHERE d.registration_status = 'pending' 
    ORDER BY d.donor_id DESC");

// Get all donors with status
$all_donors = $conn->query("SELECT d.*, u.name, u.email, u.contact_no, u.date_of_birth 
    FROM donors d 
    JOIN users u ON d.user_id = u.user_id 
    ORDER BY d.donor_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donors - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Manage Donors</h2>
            <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Pending Registrations -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-clock"></i> Pending Registrations (<?= $pending_donors->num_rows ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($pending_donors->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Blood Type</th>
                                <th>Contact</th>
                                <th>Medical Info</th>
                                <th>Schedule</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($donor = $pending_donors->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $donor['donor_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($donor['name']) ?></strong><br>
                                    <small class="text-muted"><?= $donor['email'] ?></small>
                                </td>
                                <td><span class="badge bg-danger"><?= $donor['blood_type'] ?></span></td>
                                <td><?= $donor['contact_no'] ?></td>
                                <td>
                                    <small>
                                        Weight: <?= $donor['weight'] ?>kg<br>
                                        Height: <?= $donor['height'] ?>cm
                                        <?php if ($donor['medical_conditions']): ?>
                                        <br>Conditions: <?= htmlspecialchars($donor['medical_conditions']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php if ($donor['preferred_days']): ?>
                                        Days: <?= $donor['preferred_days'] ?><br>
                                        <?php endif; ?>
                                        <?php if ($donor['preferred_time']): ?>
                                        Time: <?= $donor['preferred_time'] ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDonorDetails(<?= $donor['donor_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="approveDonor(<?= $donor['donor_id'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectDonor(<?= $donor['donor_id'] ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No pending registrations.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Donors -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Donors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="donorsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Blood Type</th>
                                <th>Status</th>
                                <th>Last Donation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($donor = $all_donors->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $donor['donor_id'] ?></td>
                                <td><?= htmlspecialchars($donor['name']) ?></td>
                                <td><span class="badge bg-danger"><?= $donor['blood_type'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $donor['registration_status'] === 'approved' ? 'success' : ($donor['registration_status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($donor['registration_status']) ?>
                                    </span>
                                </td>
                                <td><?= $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'Never' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDonorDetails(<?= $donor['donor_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../../process/approve_donor.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approvalModalTitle">Approve Donor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="donor_id" id="approvalDonorId">
                        <input type="hidden" name="action" id="approvalAction">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveDonor(donorId) {
            document.getElementById('approvalDonorId').value = donorId;
            document.getElementById('approvalAction').value = 'approve';
            document.getElementById('approvalModalTitle').textContent = 'Approve Donor Registration';
            document.getElementById('approvalSubmitBtn').textContent = 'Approve';
            document.getElementById('approvalSubmitBtn').className = 'btn btn-success';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }

        function rejectDonor(donorId) {
            document.getElementById('approvalDonorId').value = donorId;
            document.getElementById('approvalAction').value = 'reject';
            document.getElementById('approvalModalTitle').textContent = 'Reject Donor Registration';
            document.getElementById('approvalSubmitBtn').textContent = 'Reject';
            document.getElementById('approvalSubmitBtn').className = 'btn btn-danger';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }

        function viewDonorDetails(donorId) {
            window.open(`view_donor_details.php?id=${donorId}`, '_blank');
        }
    </script>
</body>
</html>