<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$show_loading = isset($_SESSION['login_success']);
if ($show_loading) {
    unset($_SESSION['login_success']);
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_donors = $conn->query("SELECT COUNT(*) as count FROM donors")->fetch_assoc()['count'];
$total_seekers = $conn->query("SELECT COUNT(*) as count FROM seekers")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) as count FROM staff")->fetch_assoc()['count'];
$total_blood_units = $conn->query("SELECT COUNT(*) as count FROM blood_units")->fetch_assoc()['count'];
$available_units = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE available_status = 1")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kidapawan City Blood Center </title>
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
            background: transparent;
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
            background: url('../../logo.png') no-repeat center center;
            background-size: cover;
            opacity: 0.999;
            z-index: -1;
        }

        .sidebar {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(153, 27, 27, 0.9) 100%);
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

        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
            background: rgba(255, 255, 255, 0.9);
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }

        .quick-actions .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quick-actions .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        .quick-actions .btn {
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .quick-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
         .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
        }
       
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .export-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
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


        
        .login-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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
            <img src="../../logo.png" alt="Loading" class="logo-spinner">
            <h4>Welcome, <?= $_SESSION['name'] ?>!</h4>
            <p>Loading Admin Dashboard...</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="admin-profile">
                        <img src="../../logo.png" alt="Kidapawan City Blood Center Logo">
                        <div class="brand-name">Kidapawan City Blood Center</div>
                        <h5 class="text-white mb-1">Admin Panel</h5>
                        <small class="text-white-50"><?= $_SESSION['name'] ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin.php">
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
                            <a class="nav-link" href="match_requests.php">
                                <i class="fas fa-handshake"></i> Match Requests
                            </a>
                        </li> 
    <a class="nav-link" href="view_appointments.php">
        <i class="fas fa-calendar-check"></i> View Appointments
    </a>
</li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
    

                    <a class="nav-link logout-btn text-white" href="../../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div>
                                <h1 class="page-title mb-0">
                                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                                </h1>
                                <small class="text-muted">Kidapawan City Blood Center</small>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h3 class="text-primary"><?= $total_users ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-hand-holding-heart fa-2x text-success mb-2"></i>
                                <h3 class="text-success"><?= $total_donors ?></h3>
                                <p class="text-muted mb-0">Donors</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-search fa-2x text-info mb-2"></i>
                                <h3 class="text-info"><?= $total_seekers ?></h3>
                                <p class="text-muted mb-0">Blood Seekers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-user-md fa-2x text-warning mb-2"></i>
                                <h3 class="text-warning"><?= $total_staff ?></h3>
                                <p class="text-muted mb-0">Staff Members</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Inventory Overview -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card stat-card border-danger">
                            <div class="card-body text-center">
                                <i class="fas fa-flask fa-2x text-danger mb-2"></i>
                                <h3 class="text-danger"><?= $total_blood_units ?></h3>
                                <p class="text-muted mb-0">Total Blood Units</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="text-success"><?= $available_units ?></h3>
                                <p class="text-muted mb-0">Available Units</p>
                            </div>
                        </div>
                    </div>
                </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        <?php if ($show_loading): ?>
        setTimeout(function() {
            document.getElementById('loginLoader').style.display = 'none';
        }, 2000);
        <?php endif; ?>
        

    </script>
</body>
</html>
