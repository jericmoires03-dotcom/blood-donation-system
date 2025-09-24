<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Enhanced Statistics
function safe_query($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        return false;
    }
    return $result;
}

// Core Statistics
$total_units = safe_query($conn, "SELECT COUNT(*) AS total FROM blood_units")->fetch_assoc()['total'];
$available_units = safe_query($conn, "SELECT COUNT(*) AS total FROM blood_units WHERE available_status = 1")->fetch_assoc()['total'];
$total_donors = safe_query($conn, "SELECT COUNT(*) AS total FROM donors")->fetch_assoc()['total'];
$total_seekers = safe_query($conn, "SELECT COUNT(*) AS total FROM seekers")->fetch_assoc()['total'];
$total_staff = safe_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'Staff'")->fetch_assoc()['total'];
$pending_requests = safe_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE fulfilled_status = 0")->fetch_assoc()['total'];
$fulfilled_requests = safe_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE fulfilled_status = 1")->fetch_assoc()['total'];

// Blood Type Distribution with detailed stats
$stock_by_type = safe_query($conn, "SELECT 
    d.blood_type, 
    COUNT(*) as total_units,
    SUM(bu.available_status) as available_units,
    COUNT(*) - SUM(bu.available_status) as used_units
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    GROUP BY d.blood_type 
    ORDER BY d.blood_type");

// Monthly Donation Trends (last 12 months)
$columns_check = $conn->query("SHOW COLUMNS FROM blood_units LIKE 'collection_date'");
if ($columns_check->num_rows > 0) {
    $monthly_donations = safe_query($conn, "SELECT 
        MONTH(collection_date) as month, 
        YEAR(collection_date) as year,
        COUNT(*) as donations 
        FROM blood_units 
        WHERE collection_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY YEAR(collection_date), MONTH(collection_date) 
        ORDER BY year, month");
} else {
    $monthly_donations = safe_query($conn, "SELECT 
        MONTH(NOW()) as month, 
        YEAR(NOW()) as year,
        COUNT(*) as donations 
        FROM blood_units 
        LIMIT 12");
}

// Critical Blood Types (low stock)
$critical_stock = safe_query($conn, "SELECT 
    d.blood_type, 
    COUNT(*) as count 
    FROM blood_units bu
    JOIN donors d ON bu.donor_id = d.donor_id
    WHERE bu.available_status = 1 
    GROUP BY d.blood_type 
    HAVING count < 5 
    ORDER BY count ASC");

// Recent Activities with proper joins
$recent_activities = safe_query($conn, "(
    SELECT 
        'Donation' as activity_type,
        u.name as donor_name,
        d.blood_type,
        bu.unit_id,
        'Blood unit collected' as description,
        NOW() as activity_date
    FROM blood_units bu
    JOIN donors d ON bu.donor_id = d.donor_id
    JOIN users u ON d.user_id = u.user_id
)
UNION ALL
(
    SELECT 
        'Request' as activity_type,
        u.name as seeker_name,
        br.required_blood_type as blood_type,
        br.request_id as unit_id,
        'Blood request' as description,
        br.request_date as activity_date
    FROM blood_requests br
    JOIN seekers s ON br.seeker_id = s.seeker_id
    JOIN users u ON s.user_id = u.user_id
)
ORDER BY activity_date DESC
LIMIT 10");
// Request Status Distribution
$request_stats = safe_query($conn, "SELECT 
    'All' as urgency,
    COUNT(*) as count,
    SUM(fulfilled_status) as fulfilled,
    COUNT(*) - SUM(fulfilled_status) as pending
    FROM blood_requests");
   

// Top Donors
$top_donors = safe_query($conn, "SELECT 
    u.name,
    d.blood_type,
    COUNT(bu.unit_id) as donation_count,
    SUM(bu.available_status) as available_units
    FROM donors d
    JOIN users u ON d.user_id = u.user_id
    LEFT JOIN blood_units bu ON d.donor_id = bu.donor_id
    GROUP BY d.donor_id
    HAVING donation_count > 0
    ORDER BY donation_count DESC
    LIMIT 10");

// Test Results Summary
$test_results = safe_query($conn, "SELECT 
    test_results,
    COUNT(*) as count
    FROM blood_units 
    WHERE test_results IS NOT NULL
    GROUP BY test_results");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            min-height: 100vh;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .activity-item {
            border-left: 3px solid #dc2626;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        @media print {
            .sidebar, .btn-toolbar { display: none !important; }
            .col-md-9 { width: 100% !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Blood Center</h4>
                        <small class="text-white-50">Admin Portal</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="inventory.php">
                                <i class="fas fa-flask"></i> Blood Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="match_requests.php">
                                <i class="fas fa-handshake"></i> Match Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active bg-danger" href="admin_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-4 pt-3 border-top border-light">
                        <a href="../../logout.php" class="nav-link text-white">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Comprehensive System Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button class="btn btn-primary ms-2" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                        <button class="btn btn-success ms-2" onclick="refreshData()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Key Metrics Dashboard -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-primary">
                            <div class="card-body">
                                <i class="fas fa-flask fa-2x text-primary mb-2"></i>
                                <h5 class="card-title text-primary">Total Units</h5>
                                <h2 class="text-primary"><?= $total_units ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-success">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h5 class="card-title text-success">Available</h5>
                                <h2 class="text-success"><?= $available_units ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-info">
                            <div class="card-body">
                                <i class="fas fa-heart fa-2x text-info mb-2"></i>
                                <h5 class="card-title text-info">Donors</h5>
                                <h2 class="text-info"><?= $total_donors ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-warning">
                            <div class="card-body">
                                <i class="fas fa-user-injured fa-2x text-warning mb-2"></i>
                                <h5 class="card-title text-warning">Seekers</h5>
                                <h2 class="text-warning"><?= $total_seekers ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-danger">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-danger mb-2"></i>
                                <h5 class="card-title text-danger">Pending</h5>
                                <h2 class="text-danger"><?= $pending_requests ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card text-center border-success">
                            <div class="card-body">
                                <i class="fas fa-handshake fa-2x text-success mb-2"></i>
                                <h5 class="card-title text-success">Fulfilled</h5>
                                <h2 class="text-success"><?= $fulfilled_requests ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="fas fa-chart-pie"></i> Blood Type Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bloodTypeChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-chart-line"></i> Monthly Donation Trends</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Status & Test Results -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5><i class="fas fa-chart-bar"></i> Request Status by Urgency</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="requestChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-vial"></i> Test Results Summary</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="testChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Stock Alert -->
                <?php if ($critical_stock->num_rows > 0): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-danger border-0 shadow">
                            <h5><i class="fas fa-exclamation-triangle"></i> Critical Stock Alert</h5>
                            <p class="mb-3">The following blood types have critically low stock (less than 5 units):</p>
                            <div class="row">
                                <?php while ($critical = $critical_stock->fetch_assoc()): ?>
                                <div class="col-md-3 mb-2">
                                    <span class="badge bg-danger fs-6 p-2">
                                        <i class="fas fa-exclamation"></i> <?= $critical['blood_type'] ?>: <?= $critical['count'] ?> units
                                    </span>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <button class="btn btn-outline-danger mt-2" onclick="sendStockAlert()">
                                <i class="fas fa-bell"></i> Send Alert to Staff
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Detailed Tables Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5><i class="fas fa-history"></i> Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_activities->num_rows > 0): ?>
                                    <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?= $activity['activity_type'] == 'Donation' ? 'success' : 'info' ?> me-2">
                                                    <?= $activity['activity_type'] ?>
                                                </span>
                                                <strong><?= htmlspecialchars(isset($activity['donor_name']) ? $activity['donor_name'] : $activity['seeker_name']) ?></strong>
                                                <span class="text-muted">- <?= $activity['description'] ?></span>
                                                <span class="badge bg-secondary"><?= $activity['blood_type'] ?></span>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('M d, h:i A', strtotime($activity['activity_date'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted">No recent activities found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5><i class="fas fa-trophy"></i> Top Donors</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($top_donors->num_rows > 0): ?>
                                    <?php $rank = 1; while ($donor = $top_donors->fetch_assoc()): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                        <div>
                                            <span class="badge bg-warning text-dark">#<?= $rank++ ?></span>
                                            <strong><?= htmlspecialchars($donor['name']) ?></strong>
                                            <br><small class="text-muted"><?= $donor['blood_type'] ?> • <?= $donor['donation_count'] ?> donations</small>
                                        </div>
                                        <span class="badge bg-success"><?= $donor['available_units'] ?> available</span>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted">No donor data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Type Details Table -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="fas fa-table"></i> Blood Type Inventory Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Blood Type</th>
                                                <th>Total Units</th>
                                                <th>Available Units</th>
                                                <th>Used Units</th>
                                                <th>Availability %</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($stock = $stock_by_type->fetch_assoc()): 
                                                $availability_percent = ($stock['available_units'] / $stock['total_units']) * 100;
                                                $status_class = $stock['available_units'] < 5 ? 'danger' : ($stock['available_units'] < 10 ? 'warning' : 'success');
                                            ?>
                                            <tr>
                                                <td><span class="badge bg-danger fs-6"><?= $stock['blood_type'] ?></span></td>
                                                <td><?= $stock['total_units'] ?></td>
                                                <td><?= $stock['available_units'] ?></td>
                                                <td><?= $stock['used_units'] ?></td>
                                                <td><?= number_format($availability_percent, 1) ?>%</td>
                                                <td>
                                                    <span class="badge bg-<?= $status_class ?>">
                                                        <?= $stock['available_units'] < 5 ? 'Critical' : ($stock['available_units'] < 10 ? 'Low' : 'Good') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Blood Type Distribution Chart
        const bloodTypeData = {
            labels: [<?php 
                $stock_by_type->data_seek(0);
                $labels = [];
                while ($row = $stock_by_type->fetch_assoc()) {
                    $labels[] = "'" . $row['blood_type'] . "'";
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                label: 'Available Units',
                data: [<?php 
                    $stock_by_type->data_seek(0);
                    $data = [];
                    while ($row = $stock_by_type->fetch_assoc()) {
                        $data[] = $row['available_units'];
                    }
                    echo implode(',', $data);
                ?>],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        };

        new Chart(document.getElementById('bloodTypeChart'), {
            type: 'doughnut',
            data: bloodTypeData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Available Blood Units by Type' }
                }
            }
        });

        // Monthly Trends Chart
        const monthlyData = {
            labels: [<?php 
                $monthly_donations->data_seek(0);
                $months = [];
                while ($row = $monthly_donations->fetch_assoc()) {
                    $months[] = "'" . date('M Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])) . "'";
                }
                echo implode(',', $months);
            ?>],
            datasets: [{
                label: 'Donations',
                data: [<?php 
                    $monthly_donations->data_seek(0);
                    $counts = [];
                    while ($row = $monthly_donations->fetch_assoc()) {
                        $counts[] = $row['donations'];
                    }
                    echo implode(',', $counts);
                ?>],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: monthlyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { title: { display: true, text: 'Donation Trends (Last 12 Months)' } }
            }
        });

        // Request Status Chart
        const requestData = {
            labels: [<?php 
                $request_stats->data_seek(0);
                $urgencies = [];
                while ($row = $request_stats->fetch_assoc()) {
                    $urgencies[] = "'" . $row['urgency'] . "'";
                }
                echo implode(',', $urgencies);
            ?>],
            datasets: [{
                label: 'Pending',
                data: [<?php 
                    $request_stats->data_seek(0);
                    $pending = [];
                    while ($row = $request_stats->fetch_assoc()) {
                        $pending[] = $row['pending'];
                    }
                    echo implode(',', $pending);
                ?>],
                backgroundColor: '#FF6384'
            }, {
                label: 'Fulfilled',
                data: [<?php 
                    $request_stats->data_seek(0);
                    $fulfilled = [];
                    while ($row = $request_stats->fetch_assoc()) {
                        $fulfilled[] = $row['fulfilled'];
                    }
                    echo implode(',', $fulfilled);
                ?>],
                backgroundColor: '#36A2EB'
            }]
        };

        new Chart(document.getElementById('requestChart'), {
            type: 'bar',
            data: requestData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Test Results Chart
        const testData = {
            labels: [<?php 
                $test_results->data_seek(0);
                $results = [];
                while ($row = $test_results->fetch_assoc()) {
                    $results[] = "'" . (isset($row['test_results']) ? $row['test_results'] : 'Pending') . "'";
                }
                echo implode(',', $results);
            ?>],
            datasets: [{
                data: [<?php 
                    $test_results->data_seek(0);
                    $counts = [];
                    while ($row = $test_results->fetch_assoc()) {
                        $counts[] = $row['count'];
                    }
                    echo implode(',', $counts);
                ?>],
                backgroundColor: ['#28a745', '#dc3545', '#ffc107']
            }]
        };

        new Chart(document.getElementById('testChart'), {
            type: 'pie',
            data: testData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Quick Action Functions
        function generateDetailedReport() {
            window.open('../../process/generate_report.php?type=detailed', '_blank');
        }

        function sendStockAlert() {
            if (confirm('Send stock alert to all staff members?')) {
                window.location.href = '../../process/send_stock_alert.php';
            }
        }

        function scheduleReport() {
            alert('Report scheduling feature coming soon!');
        }

        function viewAnalytics() {
            window.location.href = 'analytics.php';
        }

        function exportReport() {
            window.location.href = '../../process/export_report.php?format=csv';
        }

        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html>