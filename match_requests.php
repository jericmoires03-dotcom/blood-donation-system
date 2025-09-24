<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Debug: Check if blood_requests table has data
$total_requests_check = $conn->query("SELECT COUNT(*) as count FROM blood_requests");
$total_count = $total_requests_check ? $total_requests_check->fetch_assoc()['count'] : 0;

$pendingRequests = $conn->query("SELECT br.*, u.name, u.contact_no, s.location
    FROM blood_requests br
    JOIN seekers s ON br.seeker_id = s.seeker_id
    JOIN users u ON s.user_id = u.user_id
    WHERE br.fulfilled_status = 0
    ORDER BY br.request_date ASC");

// Debug information
if (!$pendingRequests) {
    error_log("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Blood Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .urgent-request {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }
        .high-request {
            border-left: 4px solid #fd7e14;
            background-color: #fff8f0;
        }
        .normal-request {
            border-left: 4px solid #28a745;
            background-color: #f8fff8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-handshake"></i> Blood Request Matching</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-info me-2" onclick="refreshRequests()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
       

        <?php if (isset($_GET['fulfilled'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Blood request #<?= htmlspecialchars($_GET['request_id']) ?> has been successfully fulfilled!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

         
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-primary">Total Requests</h5>
                <h3><?= $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE fulfilled_status = 0")->fetch_assoc()['count'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-info">Pending Requests</h5>
                <h3><?= $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE fulfilled_status = 0")->fetch_assoc()['count'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-success">Fulfilled Requests</h5>
                <h3><?= $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE fulfilled_status = 1")->fetch_assoc()['count'] ?></h3>
            </div>
        </div>
    </div>
</div>

                <!-- Pending Requests -->
        <div class="row">
            <?php while ($req = $pendingRequests->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card normal-request">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-user"></i> Request #<?= $req['request_id'] ?>
                            </h6>
                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($req['request_date'])) ?></small>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Patient:</strong> <?= $req['name'] ?></p>
                                    <p class="mb-1"><strong>Contact:</strong> <?= $req['contact_no'] ?></p>
                                    <p class="mb-1"><strong>Location:</strong> <?= $req['location'] ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Blood Type:</strong> 
                                        <span class="badge bg-danger fs-6"><?= $req['required_blood_type'] ?></span>
                                      </p>
                                            <p class="mb-1"><strong>Quantity:</strong> <?= $req['quantity'] ?> ml</p>
<p class="mb-1"><strong>Units Needed:</strong> <?= isset($req['units_needed']) ? $req['units_needed'] : '1' ?></p>
<p class="mb-1"><strong>Reason:</strong> <?= isset($req['reason']) && !empty($req['reason']) ? htmlspecialchars($req['reason']) : 'Emergency blood requirement' ?></p>
                                        </div>
                                    </div>
                                    <form method="post" action="match_donors.php" class="d-flex justify-content-end">
                                        <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Match Donors
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($pendingRequests->num_rows === 0): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No pending blood requests at the moment.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                function refreshRequests() {
                    location.reload();
                }
            </script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>