<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

$id = $_GET['id'];
$data = $conn->query("SELECT * FROM blood_units WHERE unit_id = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Blood Unit - Blood Bank System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #991b1b;
            --accent-color: #fef2f2;
        }

        body {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
        }

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: none;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-edit fa-2x"></i>
                        </div>
                        <div>
                            <h1 class="h2 mb-0">Update Blood Unit</h1>
                            <p class="mb-0 opacity-75">Modify blood unit information</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="inventory.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-tint me-2 text-danger"></i>
                            Blood Unit ID: <?= $data['unit_id'] ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                   <form method="POST" action="../../process/update_blood_unit_process.php">
    <input type="hidden" name="unit_id" value="<?= $data['unit_id'] ?>">

    <div class="mb-4">
        <label for="test_results" class="form-label fw-semibold">
            <i class="fas fa-flask me-2"></i>Test Results
        </label>
        <textarea 
            class="form-control" 
            id="test_results" 
            name="test_results" 
            rows="4" 
            placeholder="Enter test results..."
        ><?= htmlspecialchars($data['test_results']) ?></textarea>
    </div>

    <div class="mb-4">
        <label for="available_status" class="form-label fw-semibold">
            <i class="fas fa-check-circle me-2"></i>Availability Status
        </label>
        <select class="form-select" id="available_status" name="available_status">
            <option value="1" <?= $data['available_status'] ? 'selected' : '' ?>>
                Available
            </option>
            <option value="0" <?= !$data['available_status'] ? 'selected' : '' ?>>
                Used/Unavailable
            </option>
        </select>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="inventory.php" class="btn btn-outline-secondary me-md-2">
            <i class="fas fa-times me-2"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Update Blood Unit
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