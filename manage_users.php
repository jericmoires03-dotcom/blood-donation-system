<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Update the query to use proper JOINs and latest data
$users = $conn->query("SELECT u.*, 
    d.blood_type as donor_blood_type,
    s.required_blood_type as seeker_blood_type,
    s.location as seeker_location,
    u.registration_date as joined_date
    FROM users u
    LEFT JOIN donors d ON u.user_id = d.user_id
    LEFT JOIN seekers s ON u.user_id = s.user_id
    ORDER BY u.user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dataTables_filter {
            float: right;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .dataTables_filter label {
            font-weight: 600;
            color: white;
            margin: 0;
        }
        .dataTables_filter input {
            margin-left: 10px;
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 25px;
            width: 280px;
            transition: all 0.3s ease;
            background: white;
            font-size: 14px;
        }
        .dataTables_filter input:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 0 0.3rem rgba(255, 193, 7, 0.4);
            transform: scale(1.02);
        }
        .dataTables_filter input::placeholder {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-users"></i> User Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
                                <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin.php' : 'staff.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Registered Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="usersTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Additional Info</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $user['user_id'] ?></strong></td>
                                <td>
                                    <i class="fas fa-user"></i> <?= $user['name'] ?>
                                </td>
                                <td><?= $user['email'] ?></td>
                                <td><?= $user['contact_no'] ?></td>
                                <td>
                                    <?php
                                    $roleColors = [
                                        'Admin' => 'danger',
                                        'Staff' => 'warning',
                                        'Donor' => 'success',
                                        'Seeker' => 'info'
                                    ];
                                    $color = isset($roleColors[$user['role']]) ? $roleColors[$user['role']] : 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= $user['role'] ?></span>
                                </td>
                                <td>
                                   
    <?php 
    $blood_type = '';
    if ($user['role'] == 'Donor' && $user['donor_blood_type']) {
        $blood_type = $user['donor_blood_type'];
    } elseif ($user['role'] == 'Seeker' && $user['seeker_blood_type']) {
        $blood_type = $user['seeker_blood_type'];
    }
    
    if ($blood_type): ?>
        <span class="badge bg-danger"><?= $blood_type ?></span>
    <?php else: ?>
        <?php if ($user['role'] == 'Donor'): ?>
            <span class="text-muted">No blood type set</span>
        <?php elseif ($user['role'] == 'Seeker'): ?>
            <span class="text-muted">No required type set</span>
        <?php else: ?>
            <span class="text-muted">N/A</span>
        <?php endif; ?>
    <?php endif; ?>
</td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td><?= isset($user['joined_date']) ? date('M d, Y', strtotime($user['joined_date'])) : 'N/A' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="editUser(<?= $user['user_id'] ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-info" 
                                                onclick="viewUser(<?= $user['user_id'] ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteUser(<?= $user['user_id'] ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                              <form method="POST" action="../../process/admin_add_user.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact_no" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="Donor">Donor</option>
                                    <option value="Seeker">Seeker</option>
                                    <option value="Staff">Staff</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add User</button>
                    </div>
                </form>  
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true
            });
        });

        function editUser(userId) {
            // Implement edit functionality
            window.location.href = `edit_user.php?id=${userId}`;
        }

        function viewUser(userId) {
            // Implement view functionality
            window.location.href = `view_user.php?id=${userId}`;
        }

       function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = `../../process/delete_user.php?id=${userId}`;
    }
}
    </script>
</body>
</html>