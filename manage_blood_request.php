<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Get pending blood requests
$pending_requests = $conn->query("SELECT br.*, s.*, u.name, u.email, u.contact_no, u.date_of_birth, u.gender, u.address 
    FROM blood_requests br 
    JOIN seekers s ON br.seeker_id = s.seeker_id 
    JOIN users u ON s.user_id = u.user_id 
    WHERE br.fulfilled_status = 0 
    ORDER BY br.request_date ASC");

// Get all blood requests
$all_requests = $conn->query("SELECT br.*, s.*, u.name, u.email, u.contact_no 
    FROM blood_requests br 
    JOIN seekers s ON br.seeker_id = s.seeker_id 
    JOIN users u ON s.user_id = u.user_id 
    ORDER BY br.request_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blood Requests - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tint"></i> Manage Blood Requests</h2>
            <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Pending Requests -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-clock"></i> Pending Blood Requests (<?= $pending_requests->num_rows ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($pending_requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                    <thead>
    <tr>
        <th>Request ID</th>
        <th>Patient Info</th>
        <th>Blood Type</th>
        <th>Units</th>
        <th>Request Date</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
    <?php while ($request = $pending_requests->fetch_assoc()): ?>
    <tr>
        <td><strong>#<?= $request['request_id'] ?></strong></td>
        <td>
            <strong><?= htmlspecialchars($request['name']) ?></strong><br>
            <small class="text-muted">
                <?= $request['email'] ?><br>
                <?= $request['contact_no'] ?>
            </small>
        </td>
        <td><span class="badge bg-danger fs-6"><?= $request['required_blood_type'] ?></span></td>
        <td><strong><?= $request['quantity'] ?> units</strong></td>
        <td><?= date('M d, Y', strtotime($request['request_date'])) ?></td>
        <td>
            <button class="btn btn-sm btn-info" onclick="viewRequestDetails(<?= $request['request_id'] ?>)">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-success" onclick="fulfillRequest(<?= $request['request_id'] ?>)">
                <i class="fas fa-check"></i>
            </button>
        </td>
    </tr>
    <?php endwhile; ?>
</tbody>    
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No pending blood requests.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Requests -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Blood Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="requestsTable">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Patient</th>
                                <th>Blood Type</th>
                                <th>Units</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $all_requests->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $request['request_id'] ?></td>
                                <td><?= htmlspecialchars($request['name']) ?></td>
                                <td><span class="badge bg-danger"><?= $request['required_blood_type'] ?></span></td>
                                <td><?= $request['quantity'] ?> units</td>
                                <td>
                                    <span class="badge bg-<?= $request['request_status'] === 'approved' ? 'success' : ($request['request_status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($request['request_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewRequestDetails(<?= $request['request_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../../process/approve_blood_request.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approvalModalTitle">Process Blood Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="approvalRequestId">
                        <input type="hidden" name="action" id="approvalAction">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveRequest(requestId) {
            document.getElementById('approvalRequestId').value = requestId;
            document.getElementById('approvalAction').value = 'approve';
            document.getElementById('approvalModalTitle').textContent = 'Approve Blood Request';
            document.getElementById('approvalSubmitBtn').textContent = 'Approve';
            document.getElementById('approvalSubmitBtn').className = 'btn btn-success';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }

        function rejectRequest(requestId) {
            document.getElementById('approvalRequestId').value = requestId;
            document.getElementById('approvalAction').value = 'reject';
            document.getElementById('approvalModalTitle').textContent = 'Reject Blood Request';
            document.getElementById('approvalSubmitBtn').textContent = 'Reject';
            document.getElementById('approvalSubmitBtn').className = 'btn btn-danger';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }

        function viewRequestDetails(requestId) {
            window.open(`view_request_details.php?id=${requestId}`, '_blank');
        }
    </script>
</body>
</html>