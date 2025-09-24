<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$show_loading = isset($_SESSION['login_success']);
if ($show_loading) {
    unset($_SESSION['login_success']);
}

// Get staff information
$staff_query = $conn->prepare("SELECT s.*, u.name, u.email, u.contact_no FROM staff s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$staff_query->bind_param("i", $_SESSION['user_id']);
$staff_query->execute();
$staff = $staff_query->get_result()->fetch_assoc();

// Get dynamic task counts
$pending_tests = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE test_results IS NULL OR test_results = ''")->fetch_assoc()['count'];
$records_to_update = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE test_results = 'Pending' OR available_status = 1")->fetch_assoc()['count'];

// Get dashboard statistics
$total_blood_units = $conn->query("SELECT COUNT(*) as count FROM blood_units")->fetch_assoc()['count'];
$available_units = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE available_status = 1")->fetch_assoc()['count'];
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE fulfilled_status = 0")->fetch_assoc()['count'];

// Check if collection_date column exists, otherwise use unit_id as proxy for recent donations
$columns_check = $conn->query("SHOW COLUMNS FROM blood_units LIKE 'collection_date'");
if ($columns_check->num_rows > 0) {
    $recent_donations = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE DATE(collection_date) = CURDATE()")->fetch_assoc()['count'];
} else {
    // Use recent unit_id as proxy for today's donations (last 5 units)
    $recent_donations = $conn->query("SELECT COUNT(*) as count FROM blood_units ORDER BY unit_id DESC LIMIT 5")->fetch_assoc()['count'];
}

// Get notification count - check if notifications table exists
$notification_count = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check->num_rows > 0) {
    // Check what columns exist in notifications table
    $columns = $conn->query("SHOW COLUMNS FROM notifications");
    $has_created_at = false;
    $has_sent_date = false;
    
    while ($column = $columns->fetch_assoc()) {
        if ($column['Field'] == 'created_at') {
            $has_created_at = true;
        } elseif ($column['Field'] == 'sent_date') {
            $has_sent_date = true;
        }
    }
    
    if ($has_created_at) {
        $notification_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    } elseif ($has_sent_date) {
        $notification_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE DATE(sent_date) = CURDATE()")->fetch_assoc()['count'];
    } else {
        $notification_count = $conn->query("SELECT COUNT(*) as count FROM notifications")->fetch_assoc()['count'];
    }
}
$recent_units = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name 
    FROM blood_units bu 
    JOIN donors d ON bu.donor_id = d.donor_id 
    JOIN users u ON d.user_id = u.user_id 
    ORDER BY bu.unit_id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../../LOGO.png') no-repeat center center;
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
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.85), rgba(32, 201, 151, 0.85));
            min-height: 100vh;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
            background: rgba(255, 255, 255, 0.9);
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .welcome-card {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.85), rgba(32, 201, 151, 0.85));
            color: white;
        }
        .quick-action-btn {
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .login-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .logo-spinner {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transform-style: preserve-3d;
        }
        
        @keyframes spin {
            0% { transform: rotateY(0deg); }
            100% { transform: rotateY(360deg); }
        }

    </style>
</head>
<body>
    <?php if ($show_loading): ?>
    <div class="login-loader" id="loginLoader">
        <div class="loader-content">
            <img src="../../LOGO.png" alt="Loading" class="logo-spinner">
            <h4>Welcome, <?= $staff['name'] ?>!</h4>
            <p>Loading Staff Dashboard...</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="../../LOGO.png" alt="Blood Center Logo" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid rgba(255, 255, 255, 0.3); object-fit: cover; margin-bottom: 12px;">
                        <h5 class="text-white">Staff Panel</h5>
                        <small class="text-white-50"><?= $staff['name'] ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="staff.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
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
                            <a class="nav-link text-white" href="donated_blood.php">
                                <i class="fas fa-tint"></i> Donated Blood
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="process_request.php">
                                <i class="fas fa-cogs"></i> Process 
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-white bg-danger rounded" href="../../logout.php" style="background-color: #dc3545 !important;" onclick="showLogoutAnimation()">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Welcome Section -->
                <div class="welcome-card rounded p-4 mb-4 mt-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-user-md"></i> Welcome, <?= $staff['name'] ?>!</h2>
                            <p class="mb-0"> | Department: <?= $staff['department'] ?></p>
                            <small>Manage blood inventory and assist with laboratory operations</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-hospital fa-4x opacity-50"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-flask fa-2x text-primary mb-2"></i>
                                <h3 class="text-primary"><?= $total_blood_units ?></h3>
                                <p class="text-muted mb-0">Total Blood Units</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="text-success"><?= $available_units ?></h3>
                                <p class="text-muted mb-0">Available Units</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h3 class="text-warning"><?= $pending_requests ?></h3>
                                <p class="text-muted mb-0">Pending Requests</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                                <h3 class="text-info"><?= $recent_donations ?></h3>
                                <p class="text-muted mb-0">Today's Donations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-history"></i> Recent Blood Units</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_units->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Unit ID</th>
                                                    <th>Blood Type</th>
                                                    <th>Donor</th>
                                                    <th>Collection Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($unit = $recent_units->fetch_assoc()): ?>
                                                <tr>
                                                    <td>#<?= $unit['unit_id'] ?></td>
                                                    <td><span class="badge bg-danger"><?= $unit['blood_type'] ?></span></td>
                                                    <td><?= $unit['donor_name'] ?></td>
                                                     <td><?= isset($unit['collection_date']) ? date('M d, Y', strtotime($unit['collection_date'])) : 'N/A' ?></td>
                                                    <td>
                                                        <?php if ($unit['available_status']): ?>
                                                            <span class="badge bg-success">Available</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Used</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center">
                                        <a href="inventory.php" class="btn btn-sm btn-outline-primary">View All Units</a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No blood units recorded yet</p>
                                        <a href="add_blood_unit.php" class="btn btn-success">Add First Unit</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tasks"></i> Staff Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                  <div class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-vial text-primary"></i>
        <span class="ms-2">Process Test Results</span>
    </div>
    <span class="badge bg-primary rounded-pill"><?= $pending_tests ?></span>
</div>
<div class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-clipboard-check text-success"></i>
        <span class="ms-2">Update Blood Records</span>
    </div>
    <span class="badge bg-success rounded-pill"><?= $records_to_update ?></span>
</div>
<div class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-bell text-warning"></i>
        <span class="ms-2">Send Notifications</span>
    </div>
    <span class="badge bg-warning rounded-pill"><?= $notification_count ?></span>
</div>  
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        Tasks are updated in real-time
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($show_loading): ?>
        setTimeout(function() {
            document.getElementById('loginLoader').style.display = 'none';
        }, 2000);
        <?php endif; ?>
        
        function showLogoutAnimation() {
            // Redirect to logout page which has its own animation
            window.location.href = '../../logout.php';
        }
        
        // Auto-refresh statistics every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>