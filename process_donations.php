<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get pending donations (units that need processing)
$pending_units = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name, u.contact_no, u.email,
    DATE_FORMAT(bu.collection_date, '%M %d, %Y') as formatted_date
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    WHERE (bu.test_results IS NULL OR bu.test_results = '') 
    ORDER BY bu.unit_id DESC");

// Updated query for processed units - removed processed_date references
$processed_units = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name, u.contact_no,
    DATE_FORMAT(bu.collection_date, '%M %d, %Y') as formatted_date,
    DATE_FORMAT(bu.collection_date, '%M %d, %Y') as collection_formatted_date,
    up.name as processed_by_name
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    LEFT JOIN users up ON bu.processed_by = up.user_id
    WHERE bu.test_results IS NOT NULL AND bu.test_results != '' 
    ORDER BY bu.unit_id DESC 
    LIMIT 20");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Donations - Kidapawan City Blood Center</title>
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #991b1b;
            --success-color: #059669;
            --info-color: #0284c7;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('../../logo.png') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
        }

        .sidebar {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.85) 0%, rgba(153, 27, 27, 0.85) 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .admin-profile {
            text-align: center;
            padding: 24px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .admin-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            margin-bottom: 12px;
        }

        .brand-name {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 12px 20px;
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateX(4px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 8px;
        }

        .main-content {
            padding: 24px;
            background: transparent;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--primary-color);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            background: rgba(255, 255, 255, 0.9);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 20px 24px;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            border-color: var(--success-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .btn-danger {
            background: var(--danger-color);
            border-color: var(--danger-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .table {
            background: transparent;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #374151;
            background: rgba(0, 0, 0, 0.02);
        }

        .badge {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 600;
        }

        .logout-btn {
            background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            border: none;
            margin: 20px 12px 12px 12px;
            border-radius: 12px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(0, 0, 0, 0.02);
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #d1d5db;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="admin-profile">
                        <img src="../../logo.png" alt="Kidapawan City Blood Center Logo">
                        <div class="brand-name">Kidapawan City Blood Center</div>
                        <h5 class="text-white mb-1"><?= $_SESSION['role'] ?> Panel</h5>
                        <small class="text-white-50"><?= $_SESSION['name'] ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">
                                <i class="fas fa-flask"></i> Blood Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="process_donations.php">
                                <i class="fas fa-vial"></i> Process Donations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_appointments.php">
                                <i class="fas fa-calendar-check"></i> View Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    </ul>

                    <a class="nav-link logout-btn text-white" href="../../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">
                                <i class="fas fa-vial me-2"></i>Process Blood Donations
                            </h1>
                            <small class="text-muted">Review and process pending blood donations</small>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Pending Donations -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending Blood Units for Processing
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($pending_units->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Unit ID</th>
                                            <th>Donor</th>
                                            <th>Blood Type</th>
                                            <th>Collection Date</th>
                                            <th>Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($unit = $pending_units->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong>#<?= $unit['unit_id'] ?></strong></td>
                                                <td><?= htmlspecialchars($unit['donor_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?= $unit['blood_type'] ?></span>
                                                </td>
                                                <td><?= $unit['formatted_date'] ?></td>
                                                <td><?= htmlspecialchars($unit['contact_no']) ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#processModal<?= $unit['unit_id'] ?>">
                                                        <i class="fas fa-flask me-1"></i>Process
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Process Modal -->
                                            <div class="modal fade" id="processModal<?= $unit['unit_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Process Blood Unit #<?= $unit['unit_id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="../../process/process_donations.php" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="unit_id" value="<?= $unit['unit_id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Donor</label>
                                                                    <p class="form-control-plaintext"><strong><?= htmlspecialchars($unit['donor_name']) ?></strong></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Blood Type</label>
                                                                    <p class="form-control-plaintext"><span class="badge bg-danger"><?= $unit['blood_type'] ?></span></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Collection Date</label>
                                                                    <p class="form-control-plaintext"><?= $unit['formatted_date'] ?></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="test_results<?= $unit['unit_id'] ?>" class="form-label">Test Results <span class="text-danger">*</span></label>
                                                                    <select class="form-select" id="test_results<?= $unit['unit_id'] ?>" name="test_results" required>
                                                                        <option value="">Select Result</option>
                                                                        <option value="Safe">Safe</option>
                                                                        <option value="Unsafe">Unsafe</option>
                                                                        <option value="Inconclusive">Inconclusive</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="notes<?= $unit['unit_id'] ?>" class="form-label">Notes</label>
                                                                    <textarea class="form-control" id="notes<?= $unit['unit_id'] ?>" name="notes" rows="3" placeholder="Additional notes or test details..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="process_unit" class="btn btn-primary">
                                                                    <i class="fas fa-check me-1"></i>Process Unit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">No pending blood units to process</h5>
                                <p class="text-muted">All blood donations have been processed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Processed Donations History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recently Processed Units
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($processed_units->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Unit ID</th>
                                            <th>Donor</th>
                                            <th>Blood Type</th>
                                            <th>Test Result</th>
                                            <th>Processed By</th>
                                            <th>Processed Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($unit = $processed_units->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong>#<?= $unit['unit_id'] ?></strong></td>
                                                <td><?= htmlspecialchars($unit['donor_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?= $unit['blood_type'] ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $badge_class = '';
                                                    switch($unit['test_results']) {
                                                        case 'Safe': $badge_class = 'bg-success'; break;
                                                        case 'Unsafe': $badge_class = 'bg-danger'; break;
                                                        case 'Inconclusive': $badge_class = 'bg-warning'; break;
                                                        default: $badge_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_class ?>"><?= $unit['test_results'] ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($unit['processed_by_name'] ?? 'System') ?></td>
                                                <td><?= $unit['formatted_date'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                                <h5 class="text-muted">No processed units found</h5>
                                <p class="text-muted">Blood donation processing history will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
