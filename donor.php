<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$show_loading = isset($_SESSION['login_success']);
if ($show_loading) {
    unset($_SESSION['login_success']);
}

$user_id = $_SESSION['user_id'];

// Get donor information
$donor_query = $conn->prepare("SELECT d.*, u.name, u.email, u.contact_no FROM donors d JOIN users u ON d.user_id = u.user_id WHERE u.user_id = ?");
$donor_query->bind_param("i", $user_id);
$donor_query->execute();
$donor = $donor_query->get_result()->fetch_assoc();

// Check if donor exists
if (!$donor) {
    header("Location: ../../login.php?error=Donor profile not found");
    exit;
}

// Get donation statistics
$total_donations = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE donor_id = {$donor['donor_id']}")->fetch_assoc()['count'];
$available_units = $conn->query("SELECT COUNT(*) as count FROM blood_units WHERE donor_id = {$donor['donor_id']} AND available_status = 1")->fetch_assoc()['count'];

// Get recent donations
$recent_donations = $conn->query("SELECT * FROM blood_units WHERE donor_id = {$donor['donor_id']} ORDER BY unit_id DESC LIMIT 5");

// Check eligibility for next donation (56 days rule)
$days_since_last = null;
$eligible_to_donate = true;
if ($donor['last_donation_date']) {
    $last_date = new DateTime($donor['last_donation_date']);
    $today = new DateTime();
    $days_since_last = $today->diff($last_date)->days;
    $eligible_to_donate = $days_since_last >= 56;
}

// Check which date column exists in notifications table
$date_columns = $conn->query("SHOW COLUMNS FROM notifications WHERE Field IN ('created_at', 'sent_date')");
$available_columns = [];
while ($col = $date_columns->fetch_assoc()) {
    $available_columns[] = $col['Field'];
}

$dateColumn = in_array('created_at', $available_columns) ? 'created_at' : 'sent_date';

// Get recent notifications
$notifications = $conn->prepare("SELECT *, $dateColumn as display_date FROM notifications WHERE user_id = ? ORDER BY $dateColumn DESC LIMIT 5");
$notifications->bind_param("i", $user_id);
$notifications->execute();
$notificationResult = $notifications->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Donor Dashboard - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        .hero-section {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            border-left: 4px solid #dc2626;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .eligibility-badge {
            font-size: 1.1em;
            padding: 8px 16px;
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
            <img src="../../LOGO.png" alt="Loading" class="logo-spinner">
            <h4>Welcome, <?= htmlspecialchars($donor['name']) ?>!</h4>
            <p>Loading Donor Dashboard...</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <img src="../../LOGO.png" alt="Blood Center Logo" class="img-fluid rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="col-md-6">
                    <h5 class="text-light mb-2">Kidapawan City Blood Center</h5>
                    <h1><i class="fas fa-heart me-3"></i>Welcome, <?= htmlspecialchars($donor['name']) ?></h1>
                    <p class="mb-0">Thank you for being a life-saving blood donor</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">Blood Type: <?= $donor['blood_type'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Donation Eligibility Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($eligible_to_donate): ?>
                            <span class="badge bg-success eligibility-badge">
                                <i class="fas fa-check-circle me-2"></i>Eligible to Donate
                            </span>
                            <p class="mt-2 mb-0">You can donate blood now!</p>
                        <?php else: ?>
                            <span class="badge bg-warning eligibility-badge">
                                <i class="fas fa-clock me-2"></i>Not Eligible Yet
                            </span>
                            <p class="mt-2 mb-0">Next donation available in <?= 56 - $days_since_last ?> days</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?= $total_donations ?></h3>
                        <p class="text-muted mb-0">Total Donations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= $available_units ?></h3>
                        <p class="text-muted mb-0">Available Units</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-info"><?= isset($days_since_last) ? $days_since_last : 'N/A' ?></h3>
                        <p class="text-muted mb-0">Days Since Last Donation</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card quick-actions">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <a href="donor_profile.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="donation_history.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-history me-2"></i>Donation History
                                </a>
                            </div>
                            <div class="col-md-3">
                                <?php if ($eligible_to_donate): ?>
                                    <a href="schedule_donation.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-calendar me-2"></i>Schedule Donation
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-calendar me-2"></i>Schedule Donation
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <a href="../../logout.php" class="btn btn-outline-danger w-100" onclick="showLogoutAnimation()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Donations and Notifications -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Recent Donations</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_donations->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Unit ID</th>
                                            <th>Blood Type</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Test Results</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($donation = $recent_donations->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $donation['unit_id'] ?></td>
                                            <td><span class="badge bg-primary"><?= $donation['blood_type'] ?></span></td>
                                            <td><?= $donation['quantity'] ?> ml</td>
                                            <td>
                                                <?php if ($donation['available_status']): ?>
                                                    <span class="badge bg-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Used</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($donation['test_results']): ?>
                                                    <span class="badge bg-<?= $donation['test_results'] === 'Safe' ? 'success' : 'warning' ?>">
                                                        <?= $donation['test_results'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="donation_history.php" class="btn btn-primary">View All Donations</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                <h5>No donations yet</h5>
                                <p class="text-muted">Start your life-saving journey by scheduling your first donation!</p>
                                <?php if ($eligible_to_donate): ?>
                                    <a href="schedule_donation.php" class="btn btn-primary">Schedule First Donation</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Schedule First Donation</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>Recent Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($notificationResult->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($notification = $notificationResult->fetch_assoc()): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between">
                                           <small class="text-muted"><?php echo date('M d, Y', strtotime($notification['display_date'])); ?></small>
                                        </div>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="view_notifications.php" class="btn btn-outline-primary btn-sm">View All</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No notifications yet.</p>
                        <?php endif; ?>
                    </div>
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
        
        function showLogoutAnimation() {
            // Redirect to logout page which has its own animation
            window.location.href = '../../logout.php';
        }
    </script>
</body>
</html>
