<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$result = $conn->query("SELECT bu.*, u.name AS donor_name
                        FROM blood_units bu 
                        LEFT JOIN donors d ON bu.donor_id = d.donor_id 
                        LEFT JOIN users u ON d.user_id = u.user_id
                        ORDER BY bu.unit_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dataTables_filter {
            float: right;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .dataTables_filter label {
            font-weight: 600;
            color: white;
            margin: 0;
        }
        .dataTables_filter input {
            margin-left: 10px;
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 25px;
            width: 280px;
            transition: all 0.3s ease;
            background: white;
            font-size: 14px;
        }
        .dataTables_filter input:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 0 0.3rem rgba(255, 193, 7, 0.4);
            transform: scale(1.02);
        }
        .dataTables_filter input::placeholder {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-flask"></i> Blood Inventory Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="add_blood_unit.php" class="btn btn-success me-2">
                    <i class="fas fa-plus"></i> Add Blood Unit
                </a>
                <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

       

        <!-- Inventory Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Blood Units Inventory</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inventoryTable">
                                               <thead class="table-dark">
                            <tr>
                                <th>Unit ID</th>
                                <th>Donor</th>
                                <th>Blood Type</th>
                                <th>Quantity (ml)</th>
                                <th>Status</th>
                                <th>Test Results</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['unit_id'] ?></strong></td>
                                <td>
                                    <?php if ($row['donor_name']): ?>
                                        <i class="fas fa-user"></i> <?= $row['donor_name'] ?>
                                    <?php else: ?>
                                        <span class="text-muted">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-danger fs-6"><?= $row['blood_type'] ?></span>
                                </td>
                                <td><?= $row['quantity'] ?> ml</td>
                                <td>
                                    <?php if ($row['available_status']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Available
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times"></i> Used
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['test_results']): ?>
                                        <span class="badge bg-info">Tested</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="update_blood_unit.php?id=<?= $row['unit_id'] ?>" 
                                           class="btn btn-outline-primary" title="Update">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteUnit(<?= $row['unit_id'] ?>)" 
                                                class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Blood Unit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true
            });
        });

        function viewDetails(unitId) {
            // Fetch and display unit details in modal
            fetch(`../../process/get_unit_details.php?id=${unitId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('detailsModal')).show();
                });
        }

        function deleteUnit(unitId) {
            if (confirm('Are you sure you want to delete this blood unit? This action cannot be undone.')) {
                fetch('../../process/delete_blood_unit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `unit_id=${unitId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Blood unit deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting blood unit');
                });
            }
        }

        function applyFilters() {
            // Implement filtering logic
            const bloodType = document.getElementById('bloodTypeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            // Reload page with filters
            let url = 'inventory.php?';
            if (bloodType) url += `blood_type=${bloodType}&`;
            if (status) url += `status=${status}&`;
            if (date) url += `date=${date}&`;
            
            window.location.href = url;
        }
    </script>
</body>
</html>