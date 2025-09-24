<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get blood units pending test results
$pending_tests = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name 
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    WHERE bu.test_results IS NULL OR bu.test_results = ''
    ORDER BY bu.unit_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Test Results - Blood Center</title>
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
                            <a class="nav-link text-white active" href="process_test_results.php">
                                <i class="fas fa-vial"></i> Process Test Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="update_blood_records.php">
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
                    <h1 class="h2"><i class="fas fa-vial"></i> Process Test Results</h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Blood Units Pending Test Results</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($pending_tests->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Unit ID</th>
                                            <th>Blood Type</th>
                                            <th>Donor</th>
                                            <th>Collection Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($unit = $pending_tests->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $unit['unit_id'] ?></td>
                                            <td><span class="badge bg-danger"><?= $unit['blood_type'] ?></span></td>
                                            <td><?= $unit['donor_name'] ?></td>
                                            <td><?= isset($unit['collection_date']) ? date('M d, Y', strtotime($unit['collection_date'])) : 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="processTest(<?= $unit['unit_id'] ?>)">
                                                    <i class="fas fa-flask"></i> Process Test
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>All blood units have been tested</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Test Results Modal -->
                <div class="modal fade" id="testModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Process Test Results</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="../../process/update_blood_unit_process.php" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="unit_id" id="test_unit_id">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Test Results</label>
                                        <select name="test_results" class="form-select" required>
                                            <option value="">Select Result</option>
                                            <option value="Passed">Passed - Safe for Use</option>
                                            <option value="Failed">Failed - Discard</option>
                                            <option value="Pending">Pending Further Testing</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Availability Status</label>
                                        <select name="available_status" class="form-select" required>
                                            <option value="1">Available</option>
                                            <option value="0">Not Available</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Results</button>
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
        function processTest(unitId) {
            document.getElementById('test_unit_id').value = unitId;
            new bootstrap.Modal(document.getElementById('testModal')).show();
        }
    </script>
</body>
</html>