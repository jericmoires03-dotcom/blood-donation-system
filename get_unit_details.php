<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    echo "No unit ID provided";
    exit;
}

$unit_id = intval($_GET['id']);

// Prepare detailed query
$query = $conn->prepare("
    SELECT 
        bu.*,
        u.name AS donor_name,
        u.contact_no AS donor_contact,
        d.blood_type,
        d.last_donation_date,
        DATEDIFF(CURRENT_DATE, bu.collection_date) as days_old
    FROM blood_units bu
    LEFT JOIN donors d ON bu.donor_id = d.donor_id
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE bu.unit_id = ?
");

$query->bind_param("i", $unit_id);
$query->execute();
$unit = $query->get_result()->fetch_assoc();

if (!$unit) {
    echo "Blood unit not found";
    exit;
}

// Format the output
?>
<div class="card">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Unit #<?= $unit['unit_id'] ?></h6>
        
        <div class="mb-3">
            <strong><i class="fas fa-tint me-2"></i>Blood Type:</strong>
            <span class="badge bg-danger ms-2"><?= $unit['blood_type'] ?></span>
        </div>

        <div class="mb-3">
            <strong><i class="fas fa-user me-2"></i>Donor Information:</strong><br>
            Name: <?= htmlspecialchars($unit['donor_name']) ?><br>
            Contact: <?= $unit['donor_contact'] ?><br>
            Last Donation: <?= $unit['last_donation_date'] ? date('M d, Y', strtotime($unit['last_donation_date'])) : 'N/A' ?>
        </div>

        <div class="mb-3">
            <strong><i class="fas fa-flask me-2"></i>Unit Details:</strong><br>
            Quantity: <?= $unit['quantity'] ?> ml<br>
            Collection Date: <?= date('M d, Y', strtotime($unit['collection_date'])) ?><br>
            Age: <?= $unit['days_old'] ?> days<br>
            Status: <span class="badge bg-<?= $unit['available_status'] ? 'success' : 'secondary' ?>">
                <?= $unit['available_status'] ? 'Available' : 'Used' ?>
            </span>
        </div>

        <?php if ($unit['test_results']): ?>
        <div class="mb-3">
            <strong><i class="fas fa-clipboard-check me-2"></i>Test Results:</strong><br>
            <?= htmlspecialchars($unit['test_results']) ?>
        </div>
        <?php endif; ?>

        <?php if ($unit['notes']): ?>
        <div class="mb-3">
            <strong><i class="fas fa-notes-medical me-2"></i>Notes:</strong><br>
            <?= htmlspecialchars($unit['notes']) ?>
        </div>
        <?php endif; ?>
        
        <div class="text-muted">
            <small>
                <i class="fas fa-clock me-1"></i>
                Last Updated: <?= date('M d, Y H:i', strtotime($unit['updated_at'])) ?>
            </small>
        </div>
    </div>
</div>