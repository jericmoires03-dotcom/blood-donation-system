<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';


if (!isset($_POST['request_id'])) {
    header("Location: match_requests.php?error=Invalid request");
    exit;
}

$request_id = $_POST['request_id'];

$requestQuery = $conn->prepare("SELECT br.*, u.name, u.contact_no, s.location
    FROM blood_requests br
    JOIN seekers s ON br.seeker_id = s.seeker_id
    JOIN users u ON s.user_id = u.user_id
    WHERE br.request_id = ? AND br.fulfilled_status = 0");
$requestQuery->bind_param("i", $request_id);
$requestQuery->execute();
$request = $requestQuery->get_result()->fetch_assoc();

if (!$request) {
    header("Location: match_requests.php?error=Request not found or already fulfilled");
    exit;
}

// Find compatible donors
$bloodType = $request['required_blood_type'];
$compatibleTypes = [];

// Blood compatibility logic
switch ($bloodType) {
    case 'AB+':
        $compatibleTypes = ['AB+', 'AB-', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-'];
        break;
    case 'AB-':
        $compatibleTypes = ['AB-', 'A-', 'B-', 'O-'];
        break;
    case 'A+':
        $compatibleTypes = ['A+', 'A-', 'O+', 'O-'];
        break;
    case 'A-':
        $compatibleTypes = ['A-', 'O-'];
        break;
    case 'B+':
        $compatibleTypes = ['B+', 'B-', 'O+', 'O-'];
        break;
    case 'B-':
        $compatibleTypes = ['B-', 'O-'];
        break;
    case 'O+':
        $compatibleTypes = ['O+', 'O-'];
        break;
    case 'O-':
        $compatibleTypes = ['O-'];
        break;
}

$typesStr = "'" . implode("','", $compatibleTypes) . "'";

// Check if collection_date column exists
$columns_check = $conn->query("SHOW COLUMNS FROM blood_units LIKE 'collection_date'");
if ($columns_check->num_rows > 0) {
    $unitsQuery = $conn->query("SELECT bu.*, d.donor_id, d.blood_type, u.name as donor_name, u.contact_no as donor_contact, 
        d.last_donation_date, d.availability_status,
        DATEDIFF(CURDATE(), bu.collection_date) as days_old
        FROM blood_units bu
        JOIN donors d ON bu.donor_id = d.donor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE d.blood_type IN ($typesStr) 
        AND bu.available_status = 1 
        AND (bu.test_results = 'Safe for use' OR bu.test_results IS NULL)
        ORDER BY 
            CASE d.blood_type 
                WHEN '$bloodType' THEN 1 
                ELSE 2 
            END,
            bu.unit_id ASC");
} else {
    $unitsQuery = $conn->query("SELECT bu.*, d.donor_id, d.blood_type, u.name as donor_name, u.contact_no as donor_contact, 
        d.last_donation_date, d.availability_status,
        0 as days_old
        FROM blood_units bu
        JOIN donors d ON bu.donor_id = d.donor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE d.blood_type IN ($typesStr) 
        AND bu.available_status = 1 
        AND (bu.test_results = 'Safe for use' OR bu.test_results IS NULL)
        ORDER BY 
            CASE d.blood_type 
                WHEN '$bloodType' THEN 1 
                ELSE 2 
            END,
            bu.unit_id ASC");
}

// Get available donors for future donations
$donorsQuery = $conn->query("SELECT d.*, u.name, u.contact_no, u.email,
    DATEDIFF(CURDATE(), d.last_donation_date) as days_since_last
    FROM donors d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.blood_type IN ($typesStr) 
    AND d.availability_status = 1
    ORDER BY 
        CASE d.blood_type 
            WHEN '$bloodType' THEN 1 
            ELSE 2 
        END,
        d.last_donation_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Donors - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #991b1b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0284c7;
            --light-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .request-info-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .blood-type-display {
            background: linear-gradient(135deg, var(--danger-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .compatibility-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0.2rem;
        }

        .exact-match { background: #dcfce7; color: #166534; }
        .compatible { background: #fef3c7; color: #92400e; }

        .unit-card, .donor-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1rem;
        }

        .unit-card:hover, .donor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .freshness-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.3rem 0.6rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .fresh { background: #dcfce7; color: #166534; }
        .good { background: #fef3c7; color: #92400e; }
        .aging { background: #fee2e2; color: #991b1b; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .contact-btn {
            background: linear-gradient(135deg, var(--info-color) 0%, #0369a1 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .contact-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            color: white;
        }

        .fulfill-btn {
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .fulfill-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
            color: white;
        }

        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">
                        <i class="fas fa-search me-3"></i>Donor Matching System
                    </h1>
                    <p class="mb-0 opacity-75">Find compatible donors for blood request #<?= $request['request_id'] ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="match_requests.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Requests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card request-info-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-user-injured me-2"></i>Request Details
                            <span class="badge bg-<?= (isset($request['urgency_level']) && $request['urgency_level'] === 'Urgent') ? 'danger' : ((isset($request['urgency_level']) && $request['urgency_level'] === 'High') ? 'warning' : 'success') ?> ms-2">
                                <?= isset($request['urgency_level']) ? $request['urgency_level'] : 'Normal' ?> Priority
                            </span>
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><i class="fas fa-user me-2"></i><strong>Patient:</strong> <?= htmlspecialchars($request['name']) ?></p>
                                <p class="mb-2"><i class="fas fa-phone me-2"></i><strong>Contact:</strong> <?= $request['contact_no'] ?></p>
                                <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i><strong>Location:</strong> <?= htmlspecialchars($request['location']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><i class="fas fa-vial me-2"></i><strong>Units Needed:</strong> <?= isset($request['units_needed']) ? $request['units_needed'] : 'Not specified' ?></p>
                                <p class="mb-2"><i class="fas fa-calendar me-2"></i><strong>Requested:</strong> <?= date('M d, Y H:i', strtotime($request['request_date'])) ?></p>
                                <?php if (!empty($request['reason'])): ?>
                                <p class="mb-2"><i class="fas fa-notes-medical me-2"></i><strong>Reason:</strong> <?= htmlspecialchars($request['reason']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="blood-type-display">
                            <i class="fas fa-tint me-2"></i><?= $request['required_blood_type'] ?>
                        </div>
                        <div class="mt-3">
                            <p class="mb-1"><strong>Compatible Types:</strong></p>
                            <?php foreach ($compatibleTypes as $type): ?>
                                <span class="compatibility-badge <?= $type === $bloodType ? 'exact-match' : 'compatible' ?>">
                                    <?= $type ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

     
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-primary">Total Requests</h5>
                <h3><?= $conn->query("SELECT COUNT(*) as count FROM blood_requests")->fetch_assoc()['count'] ?></h3>
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

        
        <ul class="nav nav-pills mb-3" id="matchingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="units-tab" data-bs-toggle="pill" data-bs-target="#units" type="button" role="tab">
                    <i class="fas fa-vial me-2"></i>Available Blood Units (<?= $unitsQuery->num_rows ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="donors-tab" data-bs-toggle="pill" data-bs-target="#donors" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Available Donors (<?= $donorsQuery->num_rows ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="matchingTabsContent">
            
            <div class="tab-pane fade show active" id="units" role="tabpanel">
                <?php if ($unitsQuery->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($unit = $unitsQuery->fetch_assoc()): ?>
                            <?php
                            $freshnessClass = '';
                            $freshnessText = '';
                            if ($unit['days_old'] <= 7) {
                                $freshnessClass = 'fresh';
                                $freshnessText = 'Fresh';
                            } elseif ($unit['days_old'] <= 21) {
                                $freshnessClass = 'good';
                                $freshnessText = 'Good';
                            } else {
                                $freshnessClass = 'aging';
                                $freshnessText = 'Aging';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="unit-card" style="position: relative;">
                                    <div class="freshness-indicator <?= $freshnessClass ?>">
                                        <?= $freshnessText ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="card-title mb-1">Unit #<?= $unit['unit_id'] ?></h6>
                                                <span class="badge bg-<?= $unit['blood_type'] === $bloodType ? 'success' : 'warning' ?>">
                                                    <?= $unit['blood_type'] ?>
                                                    <?= $unit['blood_type'] === $bloodType ? ' (Exact Match)' : ' (Compatible)' ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <p class="mb-1"><i class="fas fa-user me-2"></i><strong>Donor:</strong> <?= htmlspecialchars($unit['donor_name']) ?></p>
                                            <?php if (isset($unit['collection_date']) && $unit['collection_date']): ?>
                                            <p class="mb-1"><i class="fas fa-calendar me-2"></i><strong>Collected:</strong> <?= date('M d, Y', strtotime($unit['collection_date'])) ?></p>
                                            <?php endif; ?>
                                            <p class="mb-1"><i class="fas fa-clock me-2"></i><strong>Age:</strong> <?= $unit['days_old'] ?> days</p>
                                            <p class="mb-1"><i class="fas fa-user me-2"></i><strong>Donor Contact:</strong> <?= $unit['donor_contact'] ?></p>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                           
                                            <form method="post" action="../../process/fulfill_request_process.php" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                <input type="hidden" name="unit_id" value="<?= $unit['unit_id'] ?>">
                                                <input type="hidden" name="seeker_id" value="<?= $request['seeker_id'] ?>">
                                                <button type="submit" class="btn fulfill-btn btn-sm" onclick="return confirm('Are you sure you want to fulfill this request with this blood unit?')">
                                                    <i class="fas fa-check me-1"></i>Use This Unit
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-vial text-muted mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                        <h5 class="text-muted">No Compatible Blood Units Available</h5>
                        <p class="text-muted">Consider contacting available donors for fresh donations.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Donors -->
            <div class="tab-pane fade" id="donors" role="tabpanel">
                <?php if ($donorsQuery->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($donor = $donorsQuery->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="donor-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="card-title mb-1"><?= htmlspecialchars($donor['name']) ?></h6>
                                                <span class="badge bg-<?= $donor['blood_type'] === $bloodType ? 'success' : 'warning' ?>">
                                                    <?= $donor['blood_type'] ?>
                                                    <?= $donor['blood_type'] === $bloodType ? ' (Exact Match)' : ' (Compatible)' ?>
                                                </span>
                                            </div>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?= $donor['contact_no'] ?></p>
                                            <?php if (!empty($donor['email'])): ?>
                                            <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= $donor['email'] ?></p>
                                            <?php endif; ?>
                                            <p class="mb-1"><i class="fas fa-id-badge me-2"></i><strong>Donor ID:</strong> <?= $donor['donor_id'] ?></p>
                                            <p class="mb-1">
                                                <i class="fas fa-calendar me-2"></i>
                                                <strong>Last Donation:</strong> 
                                                <?= $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) . ' (' . $donor['days_since_last'] . ' days ago)' : 'Never' ?>
                                            </p>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn contact-btn btn-sm flex-fill" onclick="openContactForm('<?= htmlspecialchars($donor['email']) ?>', '<?= htmlspecialchars($donor['name']) ?>', <?= $request['request_id'] ?>, '<?= $request['required_blood_type'] ?>', <?= isset($request['units_needed']) ? $request['units_needed'] : 1 ?>, '<?= isset($request['urgency_level']) ? $request['urgency_level'] : 'Normal' ?>')">
                                                <i class="fas fa-envelope me-1"></i>Contact Donor
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users text-muted mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                        <h5 class="text-muted">No Available Donors</h5>
                        <p class="text-muted">All compatible donors are currently unavailable or have donated recently.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contact Form Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Donor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="contactForm">
                    <div class="modal-body">
                        <input type="hidden" id="donorEmail" name="donor_email">
                        <input type="hidden" id="donorName" name="donor_name">
                        <input type="hidden" id="requestId" name="request_id">
                        <input type="hidden" id="bloodType" name="blood_type">
                        <input type="hidden" id="unitsNeeded" name="units_needed">
                        <input type="hidden" id="urgencyLevel" name="urgency_level">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Donor Name</label>
                                <input type="text" class="form-control" id="displayDonorName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Donor Email</label>
                                <input type="email" class="form-control" id="displayDonorEmail" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" id="emailSubject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" id="emailMessage" rows="6" required placeholder="Write your message to the donor..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeRequestDetails" name="includeRequestDetails" checked>
                                <label class="form-check-label" for="includeRequestDetails">
                                    Include blood request details in email
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


function openContactForm(donorEmail, donorName, requestId, bloodType, unitsNeeded, urgencyLevel) {
    
    document.getElementById('donorEmail').value = donorEmail;
    document.getElementById('donorName').value = donorName;
    document.getElementById('requestId').value = requestId;
    document.getElementById('bloodType').value = bloodType;
    document.getElementById('unitsNeeded').value = unitsNeeded;
    document.getElementById('urgencyLevel').value = urgencyLevel;
    
    // Set display values
    document.getElementById('displayDonorName').value = donorName;
    document.getElementById('displayDonorEmail').value = donorEmail;
    
    // Set default subject
    document.getElementById('emailSubject').value = `Urgent Blood Request - ${bloodType} Blood Type Needed`;
    
    // Set default message
    const defaultMessage = `Dear ${donorName},

We hope this message finds you well. We are reaching out from Kidapawan City Blood Center regarding an urgent blood request.

Blood Request Details:
- Blood Type Required: ${bloodType}
- Units Needed: ${unitsNeeded}
- Urgency Level: ${urgencyLevel}

Your generous donation could help save a life. If you are available and eligible to donate, please contact us at your earliest convenience.

Thank you for your continued support and willingness to help those in need.

Best regards,
Kidapawan City Blood Center Team`;
    
    document.getElementById('emailMessage').value = defaultMessage;
    
    // Show the modal
    new bootstrap.Modal(document.getElementById('contactModal')).show();
}

// Function to show notifications
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Update the contact form submission handler
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
    submitBtn.disabled = true;
    
    fetch('../../send_email_donor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            showNotification('✅ ' + data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
            document.getElementById('contactForm').reset();
        } else {
            showNotification('❌ ' + data.message, 'danger');
        }
    })
    .catch(error => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showNotification('❌ Failed to send email. Please try again.', 'danger');
        console.error('Error:', error);
    });
});
</script>
</body>
</html>