<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seeker') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$show_loading = isset($_SESSION['login_success']);
if ($show_loading) {
    unset($_SESSION['login_success']);
}

$user_id = $_SESSION['user_id'];

// Get seeker information
$seeker_query = $conn->prepare("SELECT s.*, u.name, u.email, u.contact_no FROM seekers s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$seeker_query->bind_param("i", $user_id);
$seeker_query->execute();
$seeker = $seeker_query->get_result()->fetch_assoc();

// Get request statistics
$total_requests = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE seeker_id = ?");
$total_requests->bind_param("i", $seeker['seeker_id']);
$total_requests->execute();
$totalCount = $total_requests->get_result()->fetch_assoc()['count'];

$pending_requests = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE seeker_id = ? AND fulfilled_status = 0");
$pending_requests->bind_param("i", $seeker['seeker_id']);
$pending_requests->execute();
$pendingCount = $pending_requests->get_result()->fetch_assoc()['count'];

$fulfilled_requests = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE seeker_id = ? AND fulfilled_status = 1");
$fulfilled_requests->bind_param("i", $seeker['seeker_id']);
$fulfilled_requests->execute();
$fulfilledCount = $fulfilled_requests->get_result()->fetch_assoc()['count'];

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seeker Dashboard - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #991b1b;
            --success-color: #059669;
            --warning-color: #d97706;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .quick-action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        
        .quick-action-btn:hover {
            background: var(--secondary-color);
            color: white;
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
<body class="bg-light">
    <?php if ($show_loading): ?>
    <div class="login-loader" id="loginLoader">
        <div class="loader-content">
            <img src="../../LOGO.png" alt="Loading" class="logo-spinner">
            <h4>Welcome, <?php echo htmlspecialchars($seeker['name']); ?>!</h4>
            <p>Loading Seeker Dashboard...</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../../LOGO.png" alt="Blood Center Logo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <span><i class="fa-solid fa-droplet"></i> Kidapawan City Blood Center</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($seeker['name']); ?></span>
                <a href="../../logout.php" class="btn btn-outline-light btn-sm" onclick="showLogoutAnimation()">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-dark mb-1">Seeker Dashboard</h2>
                <p class="text-muted">Manage your blood requests and track their status</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stats-card p-4 text-center">
                    <div class="stats-icon bg-danger bg-opacity-10 mx-auto mb-3">
                        <i class="fa-solid fa-list text-danger"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $totalCount; ?></h3>
                    <div class="text-muted">Total Requests</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card p-4 text-center">
                    <div class="stats-icon bg-warning bg-opacity-10 mx-auto mb-3">
                        <i class="fa-solid fa-hourglass-half text-warning"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $pendingCount; ?></h3>
                    <div class="text-muted">Pending Requests</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card p-4 text-center">
                    <div class="stats-icon bg-success bg-opacity-10 mx-auto mb-3">
                        <i class="fa-solid fa-check-circle text-success"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $fulfilledCount; ?></h3>
                    <div class="text-muted">Fulfilled Requests</div>
                </div>
            </div>
        </div>

        <!-- Blood Request Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tint"></i> Blood Request</h5>
                    </div>
                    <div class="card-body">
                        <form id="bloodRequestForm" method="POST" action="process_request.php">
                            <input type="hidden" name="action" value="request_blood">
                            <input type="hidden" name="seeker_id" value="<?php echo $seeker['seeker_id']; ?>">
                            <input type="hidden" name="urgency" value="normal">
                            <input type="hidden" name="hospital" value="">
                            <input type="hidden" name="contact" value="<?php echo $seeker['contact_no']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="blood_type" class="form-label">Blood Type Required</label>
                                    <select class="form-select" id="blood_type" name="blood_type" required>
                                        <option value="">Select Blood Type</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="quantity" class="form-label">Volume Needed (ml)</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="100" max="450" step="50" required>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label">Reason for Request</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-info me-2" onclick="checkAvailability()">
                                        <i class="fas fa-search"></i> Check Availability
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-paper-plane"></i> Submit Blood Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blood Availability Results -->
        <div class="row mb-4" id="availabilityResults" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Available Donors</h5>
                    </div>
                    <div class="card-body" id="donorsList">
                        <!-- Donor list will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Blood Requests -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Recent Blood Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_requests = $conn->prepare("SELECT * FROM blood_requests WHERE seeker_id = ? ORDER BY request_date DESC LIMIT 5");
                        $recent_requests->bind_param("i", $seeker['seeker_id']);
                        $recent_requests->execute();
                        $requests_result = $recent_requests->get_result();
                        
                        if ($requests_result->num_rows > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Blood Type</th>
                                            <th>Units</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($request = $requests_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $request['request_id']; ?></td>
                                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($request['required_blood_type']); ?></span></td>
                                                <td><?php echo isset($request['units_needed']) ? $request['units_needed'] . 'ml' : $request['quantity'] . 'ml'; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <?php if ($request['fulfilled_status'] == 1): ?>
                                                        <span class="badge bg-success">Fulfilled</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No blood requests yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card p-4">
                    <h5 class="mb-3">Profile Information</h5>
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <strong>Name:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($seeker['name']); ?></span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Email:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($seeker['email']); ?></span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Contact:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($seeker['contact_no']); ?></span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Required Blood Type:</strong><br>
                            <span class="badge bg-danger"><?php echo htmlspecialchars(isset($seeker['required_blood_type']) ? $seeker['required_blood_type'] : 'Not Set'); ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="update_seeker_profile.php" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-edit"></i> Update Profile
                        </a>
                    </div>   
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card p-4">
                    <h5 class="mb-3">Recent Notifications</h5>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        function checkAvailability() {
            const bloodType = document.getElementById('blood_type').value;
            if (!bloodType) {
                alert('Please select a blood type first');
                return;
            }
            
            fetch('check_blood_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'blood_type=' + encodeURIComponent(bloodType)
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('donorsList').innerHTML = data;
                document.getElementById('availabilityResults').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking availability');
            });
        }
        
        document.getElementById('bloodRequestForm').addEventListener('submit', function(e) {
            const bloodType = document.getElementById('blood_type').value;
            const quantity = document.getElementById('quantity').value;
            const reason = document.getElementById('reason').value;
            
            if (!bloodType || !quantity || !reason) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (parseInt(quantity) < 100 || parseInt(quantity) > 450) {
                e.preventDefault();
                alert('Volume needed must be between 100ml and 450ml');
                return;
            }
            
            if (confirm('Are you sure you want to submit this blood request?')) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.disabled = true;
                // Form will submit normally
            } else {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>