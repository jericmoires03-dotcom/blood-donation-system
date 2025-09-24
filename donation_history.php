<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

$user_id = $_SESSION['user_id'];

// Get donor ID
$donorQuery = $conn->query("SELECT donor_id FROM donors WHERE user_id = $user_id");
$donor = $donorQuery->fetch_assoc();
$donor_id = $donor['donor_id'];

// Get blood units
$units = $conn->query("SELECT * FROM blood_units WHERE donor_id = $donor_id ORDER BY unit_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donation History - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 2rem 0;
        }
        .history-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-available {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-unavailable {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .blood-type-badge {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-history me-3"></i>My Donation History
                    </h1>
                    <p class="lead mb-0">Track your blood donation contributions and test results</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="donor.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <div class="p-4">
                        <h3 class="mb-4">
                            <i class="fas fa-tint text-danger me-2"></i>Donation Records
                        </h3>
                        
                        <?php if ($units->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th><i class="fas fa-hashtag me-2"></i>Unit ID</th>
                                        <th><i class="fas fa-tint me-2"></i>Blood Type</th>
                                        <th><i class="fas fa-flask me-2"></i>Quantity</th>
                                        <th><i class="fas fa-check-circle me-2"></i>Available</th>
                                        <th><i class="fas fa-clipboard-check me-2"></i>Test Results</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $units->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $row['unit_id'] ?></td>
                                        <td>
                                            <span class="blood-type-badge"><?= $row['blood_type'] ?></span>
                                        </td>
                                        <td><?= $row['quantity'] ?> ml</td>
                                        <td>
                                            <?php if ($row['available_status']): ?>
                                                <span class="status-badge status-available">
                                                    <i class="fas fa-check me-1"></i>Available
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-unavailable">
                                                    <i class="fas fa-times me-1"></i>Used
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['test_results']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i><?= $row['test_results'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tint text-muted" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mt-3">No Donation History</h4>
                            <p class="text-muted">You haven't made any blood donations yet.</p>
                            <a href="donor.php" class="btn btn-danger">
                                <i class="fas fa-heart me-2"></i>Start Donating
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>