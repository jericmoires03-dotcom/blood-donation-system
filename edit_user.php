<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php?error=Invalid user ID");
    exit;
}

$user_id = $_GET['id'];

// Fetch user data with role-specific information
$user_query = $conn->query("SELECT u.*, 
    CASE 
        WHEN u.role = 'Donor' THEN d.blood_type
        WHEN u.role = 'Seeker' THEN s.required_blood_type
        ELSE NULL 
    END as additional_info,
    CASE 
        WHEN u.role = 'Donor' THEN d.availability_status
        WHEN u.role = 'Seeker' THEN s.seeker_id
        ELSE NULL 
    END as status_info
    FROM users u
    LEFT JOIN donors d ON u.user_id = d.user_id
    LEFT JOIN seekers s ON u.user_id = s.user_id
    WHERE u.user_id = $user_id");

if ($user_query->num_rows === 0) {
    header("Location: manage_users.php?error=User not found");
    exit;
}

$user = $user_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-user-edit"></i> Edit User: <?= htmlspecialchars($user['name']) ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
                            </div>
                        <?php endif; ?>

    <form action="../../process/update_user_process.php" method="POST">
    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
    <input type="hidden" name="current_role" value="<?= $user['role'] ?>">
    
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Contact Number</label>
        <input type="text" class="form-control" name="contact_no" value="<?= htmlspecialchars($user['contact_no']) ?>" required>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select class="form-control" name="role" required>
            <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
            <option value="Staff" <?= $user['role'] == 'Staff' ? 'selected' : '' ?>>Staff</option>
            <option value="Donor" <?= $user['role'] == 'Donor' ? 'selected' : '' ?>>Donor</option>
            <option value="Seeker" <?= $user['role'] == 'Seeker' ? 'selected' : '' ?>>Seeker</option>
        </select>
    </div>

    <!-- Donor Fields -->
    <div id="donorFields" style="display: <?= $user['role'] == 'Donor' ? 'block' : 'none' ?>;">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Blood Type</label>
                <select class="form-control" name="blood_type">
                    <option value="A+" <?= $user['additional_info'] == 'A+' ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= $user['additional_info'] == 'A-' ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= $user['additional_info'] == 'B+' ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= $user['additional_info'] == 'B-' ? 'selected' : '' ?>>B-</option>
                    <option value="AB+" <?= $user['additional_info'] == 'AB+' ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= $user['additional_info'] == 'AB-' ? 'selected' : '' ?>>AB-</option>
                    <option value="O+" <?= $user['additional_info'] == 'O+' ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= $user['additional_info'] == 'O-' ? 'selected' : '' ?>>O-</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Availability Status</label>
                <select class="form-control" name="availability_status">
                    <option value="1" <?= $user['status_info'] == '1' ? 'selected' : '' ?>>Available</option>
                    <option value="0" <?= $user['status_info'] == '0' ? 'selected' : '' ?>>Not Available</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Seeker Fields -->
    <div id="seekerFields" style="display: <?= $user['role'] == 'Seeker' ? 'block' : 'none' ?>;">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Required Blood Type</label>
                <select class="form-control" name="required_blood_type">
                    <option value="A+" <?= $user['additional_info'] == 'A+' ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= $user['additional_info'] == 'A-' ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= $user['additional_info'] == 'B+' ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= $user['additional_info'] == 'B-' ? 'selected' : '' ?>>B-</option>
                    <option value="AB+" <?= $user['additional_info'] == 'AB+' ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= $user['additional_info'] == 'AB-' ? 'selected' : '' ?>>AB-</option>
                    <option value="O+" <?= $user['additional_info'] == 'O+' ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= $user['additional_info'] == 'O-' ? 'selected' : '' ?>>O-</option>
                </select>
            </div>
        </div>
    </div>
    
        <div class="mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="reset_password" id="reset_password">
            <label class="form-check-label" for="reset_password">Reset password to default (123456)</label>
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="manage_users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <button type="submit" class="btn btn-primary">Update User</button>
    </div>
</form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic form fields based on role selection
        document.querySelector('select[name="role"]').addEventListener('change', function() {
            const role = this.value;
            const donorFields = document.getElementById('donorFields');
            const seekerFields = document.getElementById('seekerFields');
            
            // Hide all role-specific fields
            if (donorFields) donorFields.style.display = 'none';
            if (seekerFields) seekerFields.style.display = 'none';
            
            // Show relevant fields
            if (role === 'Donor' && donorFields) {
                donorFields.style.display = 'flex';
            } else if (role === 'Seeker' && seekerFields) {
                seekerFields.style.display = 'flex';
            }
        });
    </script>
</body>
</html>