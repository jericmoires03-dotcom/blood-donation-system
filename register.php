<?php
session_start();
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            header("Location: config/dashboard/admin.php");
            break;
        case 'Staff':
            header("Location: config/dashboard/staff.php");
            break;
        case 'Donor':
            header("Location: config/dashboard/donor.php");
            break;
        case 'Seeker':
            header("Location: config/dashboard/seeker.php");
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="logo.png" style="border-radius: 50%;">
    <title>Register - Kidapawan City Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 50%, #90caf9 100%);
            min-height: 100vh;
        }
        .registration-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php" style="color: #dc3545;">
                <img src="logo.png" alt="Blood Center Logo" height="60" class="me-3" style="border-radius: 50%; object-fit: cover;">
                <span style="font-size: 1.4rem;"><i class="fas fa-heart text-danger"></i> Kidapawan City Blood Center</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php" style="color: #333;">Home</a>
                <a class="nav-link btn btn-danger ms-2 text-white" href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Create Account</h2>
                </div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<div class="registration-form">
    <form method="POST" action="process/simple_register.php"></form>

                    <div class="registration-form">
                    <form method="POST" action="process/simple_register.php">
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" name="name" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Contact Number</label>
        <input type="text" class="form-control" name="contact_no" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <select class="form-control" name="role" required>
            <option value="">Select Role</option>
            <option value="Donor">Blood Donor</option>
            <option value="Seeker">Blood Seeker</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" id="password" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" required>
        <div class="invalid-feedback" id="password-error"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea class="form-control" name="address" rows="2" required></textarea>
    </div>

    <button type="submit" class="btn btn-success btn-lg w-100 btn-custom">
        <i class="fas fa-user-plus"></i> Create Account
    </button>
</form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const errorDiv = document.getElementById('password-error');
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
                errorDiv.textContent = 'Passwords do not match';
            } else {
                this.classList.remove('is-invalid');
                errorDiv.textContent = '';
            }
        });
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
            }
        });
    </script>
</body>
</html>