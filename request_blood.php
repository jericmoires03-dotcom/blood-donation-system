<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seeker') {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

// Get current seeker ID
$user_id = $_SESSION['user_id'];
$seekerQuery = $conn->query("SELECT seeker_id FROM seekers WHERE user_id = $user_id");
$seeker = $seekerQuery->fetch_assoc();
$seeker_id = $seeker['seeker_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #991b1b;
            --success-color: #059669;
            --light-bg: #f8fafc;
        }
        
        body {
            background: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="seeker.php">
                <i class="fas fa-tint me-2"></i>Blood Bank System
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="seeker.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

       

    <div class="container mt-4">
        <!-- Error Messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card form-card">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0"></div>
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="fas fa-hand-holding-medical text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <h3 class="card-title text-dark mb-1">Blood Request Form</h3>
                            <p class="text-muted">Submit your blood requirement details</p>
                        </div>
                    </div>
                    
                    <div class="card-body px-4 pb-4">
                 <form method="POST" action="../../process/request_blood_process.php">
    <input type="hidden" name="seeker_id" value="<?= $seeker_id ?>">

    <div class="mb-4">
        <label for="blood_type" class="form-label fw-semibold">
            <i class="fas fa-tint text-danger me-2"></i>Required Blood Type
        </label>
        <select class="form-control" id="blood_type" name="required_blood_type" required>
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

       <div class="mb-4">
        <label for="quantity" class="form-label fw-semibold">
            <i class="fas fa-flask text-danger me-2"></i>Quantity (ml)
        </label>
        <input type="number" class="form-control" id="quantity" name="quantity" 
               placeholder="Enter quantity in ml" min="100" max="1000" step="50" required>
        <div class="form-text">
            <i class="fas fa-info-circle me-1"></i>
            Enter blood quantity needed in milliliters (100-1000ml). Standard unit: 450ml.
        </div>
    </div>

    <div class="mb-3">
    <label for="units_needed" class="form-label">Units Needed</label>
    <input type="number" class="form-control" id="units_needed" name="units_needed" min="1" max="10" value="1" required>
</div>

<div class="mb-3">
    <label for="reason" class="form-label">Reason for Blood Request</label>
    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Please specify the medical reason or emergency details"></textarea>
</div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-2"></i>Submit Blood Request
        </button>
    </div>
</form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>