<?php
session_start();
require_once '../db.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "SELECT bu.*, u.name, d.blood_type 
          FROM blood_units bu 
          JOIN donors d ON bu.donor_id = d.donor_id 
          JOIN users u ON d.user_id = u.user_id 
          ORDER BY bu.unit_id DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donated Blood Units - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 3rem 0;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
        }
        .hero-section h1 {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .main-card {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        .table-container {
            background: white;
            border-radius: 15px;
        }
        .table th {
            background: linear-gradient(135deg, #1f2937, #374151);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #e5e7eb;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .blood-type-badge {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }
        .status-available {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 3px 6px rgba(16, 185, 129, 0.3);
        }
        .status-used {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 3px 6px rgba(239, 68, 68, 0.3);
        }
        .btn-back {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: white;
            color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,255,255,0.3);
        }
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }
        .empty-state i {
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .unit-id {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="fas fa-tint me-3"></i>Donated Blood Units
                    </h1>
                    <p class="lead mb-0 opacity-90">Comprehensive view of all donated blood units in the system</p>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-tint text-danger" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Total Units</h5>
                            <h3 class="text-danger mb-0"><?= $result->num_rows ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Available</h5>
                            <h3 class="text-success mb-0"><?php 
                                $available_count = 0;
                                $temp_result = $conn->query($query);
                                while($temp_row = $temp_result->fetch_assoc()) {
                                    if($temp_row['available_status']) $available_count++;
                                }
                                echo $available_count;
                            ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-times-circle text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Used</h5>
                            <h3 class="text-warning mb-0"><?= $result->num_rows - $available_count ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="fas fa-hashtag me-2"></i>Unit ID</th>
                                <th><i class="fas fa-user me-2"></i>Donor</th>
                                <th><i class="fas fa-tint me-2"></i>Blood Type</th>
                                <th><i class="fas fa-flask me-2"></i>Quantity (ml)</th>
                                <th><i class="fas fa-check-circle me-2"></i>Status</th>
                                <th><i class="fas fa-clipboard-check me-2"></i>Test Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="unit-id">#<?= $row['unit_id'] ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle text-muted me-2"></i>
                                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="blood-type-badge"><?= $row['blood_type'] ?></span></td>
                                    <td>
                                        <span class="fw-bold text-primary"><?= $row['quantity'] ?> ml</span>
                                    </td>
                                    <td>
                                        <span class="<?= $row['available_status'] ? 'status-available' : 'status-used' ?>">
                                            <i class="fas fa-<?= $row['available_status'] ? 'check' : 'times' ?> me-1"></i>
                                            <?= $row['available_status'] ? 'Available' : 'Used' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['test_results']): ?>
                                            <?php 
                                            $test_result = strtolower($row['test_results']);
                                            $is_safe = (strpos($test_result, 'safe') !== false || strpos($test_result, 'okay') !== false || strpos($test_result, 'pass') !== false || strpos($test_result, 'clear') !== false || strpos($test_result, 'negative') !== false);
                                            ?>
                                            <span class="badge bg-<?= $is_safe ? 'success' : 'danger' ?>">
                                                <i class="fas fa-<?= $is_safe ? 'check' : 'times' ?> me-1"></i><?= htmlspecialchars($row['test_results']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-tint text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">No Blood Units Found</h5>
                                        <p class="text-muted">There are no donated blood units in the system yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>