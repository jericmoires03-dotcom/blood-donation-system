<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seeker') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$user_id = $_SESSION['user_id'];

// Get seeker information
$seeker_query = $conn->prepare("SELECT s.*, u.name, u.email, u.contact_no FROM seekers s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$seeker_query->bind_param("i", $user_id);
$seeker_query->execute();
$seeker = $seeker_query->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type = $_POST['blood_type'];
    $quantity = intval($_POST['quantity']);
    $reason = $_POST['reason'];
    $hospital = $_POST['hospital'];
    
    // Create emergency request
    $stmt = $conn->prepare("INSERT INTO blood_requests (seeker_id, required_blood_type, quantity, urgency, request_date, fulfilled_status, reason, hospital) VALUES (?, ?, ?, 'emergency', CURDATE(), 0, ?, ?)");
    $stmt->bind_param("isiss", $seeker['seeker_id'], $blood_type, $quantity, $reason, $hospital);
    
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        
        // Send emergency notification to all admins
        $admin_query = $conn->query("SELECT user_id FROM users WHERE role = 'Admin'");
        while ($admin = $admin_query->fetch_assoc()) {
            $message = "EMERGENCY BLOOD REQUEST #$request_id: $quantity ml of $blood_type needed urgently at $hospital. Reason: $reason";
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notify_stmt->bind_param("is", $admin['user_id'], $message);
            $notify_stmt->execute();
        }
        
        $_SESSION['success_message'] = "Emergency request submitted! Request ID: $request_id";
        header("Location: seeker.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Blood Request - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --emergency-color: #ef4444;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #991b1b);
        }
        .emergency-header {
            background: linear-gradient(135deg, var(--emergency-color), #dc2626);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .form-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-light">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card border-0">
                    <div class="card-header emergency-header text-center py-4">
                        <i class="fas fa-ambulance mb-3" style="font-size: 3rem;"></i>
                        <h3 class="mb-1">Emergency Blood Request</h3>
                        <p class="mb-0 opacity-75">Urgent medical assistance required</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Emergency Protocol:</strong> This request will be prioritized and all administrators will be notified immediately.
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-tint text-danger me-2"></i>Blood Type Required
                                    </label>
                                    <select class="form-select" name="blood_type" required>
                                        <option value="">Select Blood Type</option>
                                        <option value="O-">O- (Universal Donor)</option>
                                        <option value="O+">O+</option>
                                        <option value="A-">A-</option>
                                        <option value="A+">A+</option>
                                        <option value="B-">B-</option>
                                        <option value="B+">B+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="AB+">AB+ (Universal Recipient)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-flask text-danger me-2"></i>Quantity (ml)
                                    </label>
                                    <input type="number" class="form-control" name="quantity" 
                                           min="100" max="2000" step="50" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-hospital text-danger me-2"></i>Hospital/Medical Facility
                                    </label>
                                    <input type="text" class="form-control" name="hospital" 
                                           placeholder="Enter hospital name and location" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-notes-medical text-danger me-2"></i>Medical Emergency Details
                                    </label>
                                    <textarea class="form-control" name="reason" rows="4" 
                                              placeholder="Describe the medical emergency requiring immediate blood transfusion" required></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="emergency_confirm" required>
                                        <label class="form-check-label" for="emergency_confirm">
                                            I confirm this is a genuine medical emergency requiring immediate blood transfusion
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-danger btn-lg w-100">
                                        <i class="fas fa-ambulance me-2"></i>Submit Emergency Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h6><i class="fas fa-phone text-success me-2"></i>Emergency Contacts</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Blood Bank Emergency:</strong></p>
                                <p class="text-muted">+63 123 456 7890</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Medical Emergency:</strong></p>
                                <p class="text-muted">911 / +63 911</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>