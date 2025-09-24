<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get blood inventory with donor information
$inventory = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name, u.contact_no
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    ORDER BY bu.unit_id DESC");

// Get blood type statistics
$blood_stats = $conn->query("SELECT d.blood_type, 
    COUNT(*) as total_units,
    SUM(bu.available_status) as available_units
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    GROUP BY d.blood_type 
    ORDER BY d.blood_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .blood-type-badge {
            font-size: 1.1em;
            padding: 8px 12px;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-flask text-primary"></i> Blood Inventory</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="add_blood_unit.php" class="btn btn-success me-2">
                    <i class="fas fa-plus"></i> Add Blood Unit
                </a>
                <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Blood Type Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Blood Type Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $colors = ['A+' => 'danger', 'A-' => 'warning', 'B+' => 'info', 'B-' => 'primary', 
                                      'AB+' => 'success', 'AB-' => 'dark', 'O+' => 'secondary', 'O-' => 'danger'];
                            while ($stat = $blood_stats->fetch_assoc()): 
                                $color = isset($colors[$stat['blood_type']]) ? $colors[$stat['blood_type']] : 'primary';
                            ?>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card border-<?= $color ?>">
                                    <div class="card-body text-center">
                                        <h3 class="text-<?= $color ?>"><?= $stat['blood_type'] ?></h3>
                                        <p class="mb-1"><strong><?= $stat['available_units'] ?></strong> Available</p>
                                        <small class="text-muted"><?= $stat['total_units'] ?> Total Units</small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
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
                                <th>Blood Type</th>
                                <th>Donor Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($unit = $inventory->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $unit['unit_id'] ?></strong></td>
                                <td>
                                    <span class="badge blood-type-badge bg-danger"><?= $unit['blood_type'] ?></span>
                                </td>
                                <td>
                                    <i class="fas fa-user text-muted"></i> <?= $unit['donor_name'] ?>
                                </td>
                                <td><?= $unit['contact_no'] ?></td>
                                <td>
                                    <?php if ($unit['available_status']): ?>
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
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="viewUnit(<?= $unit['unit_id'] ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($unit['available_status']): ?>
                                        <button class="btn btn-outline-warning" onclick="markAsUsed(<?= $unit['unit_id'] ?>)" title="Mark as Used">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                                        <button class="btn btn-outline-danger" onclick="deleteUnit(<?= $unit['unit_id'] ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

    <!-- View Unit Modal -->
    <div class="modal fade" id="viewUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Blood Unit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="unitDetails">
                    <!-- Unit details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                "responsive": true,
                "language": {
                    "search": "Search inventory:",
                    "lengthMenu": "Show _MENU_ units per page"
                }
            });
        });

        function viewUnit(unitId) {
            // Load unit details via AJAX
            fetch(`../../process/get_unit_details.php?id=${unitId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('unitDetails').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('viewUnitModal')).show();
                })
                .catch(error => {
                    alert('Error loading unit details');
                });
        }

        function markAsUsed(unitId) {
            if (confirm('Mark this blood unit as used?')) {
                window.location.href = `../../process/mark_unit_used.php?id=${unitId}`;
            }
        }

        function deleteUnit(unitId) {
            if (confirm('Are you sure you want to delete this blood unit? This action cannot be undone.')) {
                window.location.href = `../../process/delete_unit.php?id=${unitId}`;
            }
        }

        // Auto-refresh every 2 minutes
        setInterval(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>