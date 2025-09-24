<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get all blood units for updating
$blood_units = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name 
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    ORDER BY bu.unit_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Blood Records - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="background: linear-gradient(135deg, #28a745, #20c997); min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Blood Center</h4>
                        <small class="text-white-50">Staff Portal</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="staff.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="process_test_results.php">
                                <i class="fas fa-vial"></i> Process Test Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="update_blood_records.php">
                                <i class="fas fa-clipboard-check"></i> Update Blood Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="send_notifications.php">
                                <i class="fas fa-bell"></i> Send Notifications
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-clipboard-check"></i> Update Blood Records</h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Blood Unit Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Blood Type</th>
                                        <th>Donor</th>
                                        <th>Test Results</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($unit = $blood_units->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $unit['unit_id'] ?></td>
                                        <td><span class="badge bg-danger"><?= $unit['blood_type'] ?></span></td>
                                        <td><?= $unit['donor_name'] ?></td>
                                        <td>
                                            <?php if ($unit['test_results']): ?>
                                                <span class="badge bg-<?= $unit['test_results'] == 'Passed' ? 'success' : ($unit['test_results'] == 'Failed' ? 'danger' : 'warning') ?>">
                                                    <?= $unit['test_results'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($unit['available_status']): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Used</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="updateRecord(<?= $unit['unit_id'] ?>, '<?= $unit['test_results'] ?>', <?= $unit['available_status'] ?>)">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Update Record Modal -->
                <div class="modal fade" id="updateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Blood Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                          <form action="../../process/update_blood_unit_process.php" method="POST">
    <div class="modal-body">
        <input type="hidden" name="unit_id" id="update_unit_id">
        
        <div class="mb-3">
            <label class="form-label">Test Results</label>
            <select name="test_results" id="update_test_results" class="form-select" required>
                <option value="">Select Result</option>
                <option value="Passed">Passed - Safe for Use</option>
                <option value="Failed">Failed - Discard</option>
                <option value="Pending">Pending Further Testing</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Availability Status</label>
            <select name="available_status" id="update_status" class="form-select" required>
                <option value="1">Available</option>
                <option value="0">Not Available</option>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Update Record</button>
    </div>
</form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateRecord(unitId, testResults, status) {
            document.getElementById('update_unit_id').value = unitId;
            document.getElementById('update_test_results').value = testResults || '';
            document.getElementById('update_status').value = status;
            new bootstrap.Modal(document.getElementById('updateModal')).show();
        }
    </script>
</body>
</html>