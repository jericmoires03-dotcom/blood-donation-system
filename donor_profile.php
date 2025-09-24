<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    header("Location: ../../login.php");
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];

// Get donor information
$donor_query = $conn->prepare("SELECT d.*, u.name, u.email, u.contact_no FROM donors d JOIN users u ON d.user_id = u.user_id WHERE u.user_id = ?");
$donor_query->bind_param("i", $user_id);
$donor_query->execute();
$donor = $donor_query->get_result()->fetch_assoc();


// Check if donor exists
if (!$donor) {
    // Redirect to registration or show error
    header("Location: ../../login.php?error=Donor profile not found");
    exit;
}

// Get donation statistics - using prepared statements to prevent SQL injection
$total_donations_query = $conn->prepare("SELECT COUNT(*) as count FROM blood_units WHERE donor_id = ?");
$total_donations_query->bind_param("i", $donor['donor_id']);
$total_donations_query->execute();
$total_donations = $total_donations_query->get_result()->fetch_assoc()['count'];

$available_units_query = $conn->prepare("SELECT COUNT(*) as count FROM blood_units WHERE donor_id = ? AND available_status = 1");
$available_units_query->bind_param("i", $donor['donor_id']);
$available_units_query->execute();
$available_units = $available_units_query->get_result()->fetch_assoc()['count'];

// Prepare stats array for use in HTML
$stats = [
    'total_donations' => $total_donations,
    'available_units' => $available_units
];

// Get recent donations
$recent_donations_query = $conn->prepare("SELECT * FROM blood_units WHERE donor_id = ? ORDER BY unit_id DESC LIMIT 5");
$recent_donations_query->bind_param("i", $donor['donor_id']);
$recent_donations_query->execute();
$recent_donations_result = $recent_donations_query->get_result();
$recent_donations = [];
while ($row = $recent_donations_result->fetch_assoc()) {
    $recent_donations[] = $row;
}
// Check if collection_date column exists
$columns_check = $conn->query("SHOW COLUMNS FROM blood_units LIKE 'collection_date'");
$has_collection_date = $columns_check->num_rows > 0;

$days_since_last = null;
$eligible_to_donate = true;
$eligibility = [
    'eligible' => true,
    'message' => 'You are eligible to donate.'
];
if ($donor['last_donation_date']) {
    $last_date = new DateTime($donor['last_donation_date']);
    $today = new DateTime();
    $days_since_last = $today->diff($last_date)->days;
    $eligible_to_donate = $days_since_last >= 56;
    $eligibility['eligible'] = $eligible_to_donate;
    if ($eligible_to_donate) {
        $eligibility['message'] = 'You are eligible to donate.';
    } else {
        $eligibility['message'] = 'You can donate again in ' . (56 - $days_since_last) . ' days.';
    }
} else {
    $eligibility['message'] = 'You have not donated yet.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 2rem 0;
        }
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
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
        .donation-item {
            border-left: 3px solid #dc2626;
            padding-left: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-circle me-3"></i>My Donor Profile</h1>
                    <p class="mb-0">Manage your donation profile and view your contribution history</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="donor.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Profile Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tint text-danger fa-2x mb-3"></i>
                        <h3 class="text-danger"><?= $stats['total_donations'] ?></h3>
                        <p class="text-muted mb-0">Total Donations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                        <h3 class="text-success"><?= $stats['available_units'] ?></h3>
                        <p class="text-muted mb-0">Available Units</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt text-info fa-2x mb-3"></i>
                        <h5 class="text-info"><?= $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'Never' ?></h5>
                        <p class="text-muted mb-0">Last Donation</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card profile-card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../process/update_donor_profile.php">
                            <input type="hidden" name="donor_id" value="<?= $donor['donor_id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($donor['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($donor['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($donor['contact_no']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Blood Type</label>
                                    <select class="form-select" name="blood_type" required>
                                        <option value="A+" <?= $donor['blood_type'] == 'A+' ? 'selected' : '' ?>>A+</option>
                                        <option value="A-" <?= $donor['blood_type'] == 'A-' ? 'selected' : '' ?>>A-</option>
                                        <option value="B+" <?= $donor['blood_type'] == 'B+' ? 'selected' : '' ?>>B+</option>
                                        <option value="B-" <?= $donor['blood_type'] == 'B-' ? 'selected' : '' ?>>B-</option>
                                        <option value="AB+" <?= $donor['blood_type'] == 'AB+' ? 'selected' : '' ?>>AB+</option>
                                        <option value="AB-" <?= $donor['blood_type'] == 'AB-' ? 'selected' : '' ?>>AB-</option>
                                        <option value="O+" <?= $donor['blood_type'] == 'O+' ? 'selected' : '' ?>>O+</option>
                                        <option value="O-" <?= $donor['blood_type'] == 'O-' ? 'selected' : '' ?>>O-</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Availability Status</label>
                                    <select class="form-select" name="availability_status">
                                        <option value="1" <?= $donor['availability_status'] ? 'selected' : '' ?>>Available</option>
                                        <option value="0" <?= !$donor['availability_status'] ? 'selected' : '' ?>>Not Available</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Donation Date</label>
                                    <input type="date" class="form-control" name="last_donation_date" 
                                           value="<?= $donor['last_donation_date'] ?>">
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Donations -->
                <div class="card profile-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Donations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_donations)): ?>
                            <?php foreach ($recent_donations as $donation): ?>
                                <div class="donation-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Unit ID: #<?= $donation['unit_id'] ?></h6>
                                            <small class="text-muted">
    Collected: <?php 
    if ($has_collection_date && isset($donation['collection_date']) && $donation['collection_date']) {
        echo date('M d, Y', strtotime($donation['collection_date']));
    } else {
        echo 'Date not available';
    }
    ?>
</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= $donation['available_status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $donation['available_status'] ? 'Available' : 'Used' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="donation_history.php" class="btn btn-outline-info">
                                    <i class="fas fa-list me-2"></i>View Full History
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No donation history available</p>
                                <a href="donor.php" class="btn btn-outline-danger">
                                    <i class="fas fa-plus me-2"></i>Schedule First Donation
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Eligibility Status -->
                <div class="card profile-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Donation Eligibility</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="badge eligibility-badge <?= $eligibility['eligible'] ? 'bg-success' : 'bg-warning' ?>">
                                <?= $eligibility['eligible'] ? 'Eligible' : 'Not Eligible' ?>
                            </span>
                        </div>
                        <p class="text-muted"><?= $eligibility['message'] ?></p>
                        
                        <?php if ($eligibility['eligible']): ?>
                            <a href="donor.php#schedule" class="btn btn-success">
                                <i class="fas fa-calendar-plus me-2"></i>Schedule Donation
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card profile-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="donor.php" class="btn btn-outline-primary">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a href="donation_history.php" class="btn btn-outline-info">
                                <i class="fas fa-history me-2"></i>Donation History
                            </a>
                            <a href="../../logout.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const bloodType = document.querySelector('select[name="blood_type"]').value;
            if (!bloodType) {
                e.preventDefault();
                alert('Please select a blood type');
                return false;
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>