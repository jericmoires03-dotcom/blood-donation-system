<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seeker') {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

// Get current seeker ID
$user_id = $_SESSION['user_id'];
$seekerQuery = $conn->prepare("SELECT seeker_id FROM seekers WHERE user_id = ?");
$seekerQuery->bind_param("i", $user_id);
$seekerQuery->execute();
$seeker = $seekerQuery->get_result()->fetch_assoc();

if (!$seeker) {
    header("Location: seeker.php?error=Seeker profile not found");
    exit;
}

$seeker_id = $seeker['seeker_id'];

// Get all requests
$requestsQuery = $conn->prepare("SELECT br.*, 
    CASE 
        WHEN br.fulfilled_status = 1 THEN 'Fulfilled'
        ELSE 'Pending'
    END as status_display,
    DATEDIFF(CURDATE(), br.request_date) as days_ago
    FROM blood_requests br 
    WHERE br.seeker_id = ? 
    ORDER BY br.request_date DESC");
$requestsQuery->bind_param("i", $seeker_id);
$requestsQuery->execute();
$requests = $requestsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Blood Requests - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list-alt text-danger me-2"></i>My Blood Requests</h2>
            <div>
                <a href="request_blood.php" class="btn btn-danger me-2">
                    <i class="fas fa-plus me-1"></i>New Request
                </a>
                <a href="seeker.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($requests->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Request #<?= $row['request_id'] ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Blood Type:</strong> 
                                    <span class="badge bg-danger"><?= $row['required_blood_type'] ?></span>
                                </div>
                                    <div class="mb-2">
                                    <strong>Quantity:</strong> <?= $row['quantity'] ?> ml
                                </div>
                                <div class="mb-2">
                                    <strong>Request Date:</strong> <?= date('M d, Y', strtotime($row['request_date'])) ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Status:</strong>
                                    <?php 
                                    $badgeClass = $row['fulfilled_status'] == 1 ? 'bg-success' : 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= $row['status_display'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center mt-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4>No Blood Requests Found</h4>
                <p class="text-muted">You haven't made any blood requests yet.</p>
                <a href="request_blood.php" class="btn btn-danger">
                    <i class="fas fa-plus me-1"></i>Make Your First Request
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>