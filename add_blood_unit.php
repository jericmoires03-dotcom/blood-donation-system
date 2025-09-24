<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

// Get all donors for the dropdown
$donors = $conn->query("SELECT d.donor_id, u.name FROM donors d JOIN users u ON d.user_id = u.user_id ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blood Unit - Blood Bank Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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

        .form-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-floating > label {
            color: #64748b;
            font-weight: 500;
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

        .btn-secondary {
            background: #6b7280;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .blood-type-input {
            text-transform: uppercase;
            font-weight: 600;
            color: var(--primary-color);
        }

        .icon-wrapper {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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
                        <div class="icon-wrapper me-3">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <h1 class="h2 mb-0">Add Blood Unit</h1>
                            <p class="mb-0 opacity-75">Register new blood donation unit</p>
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
                <!-- Display Messages -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <div class="form-header">
                        <h4 class="mb-0 text-dark">
                            <i class="fas fa-flask text-primary me-2"></i>
                            Blood Unit Registration Form
                        </h4>
                        <p class="text-muted mb-0 mt-1">Fill in the details to register a new blood unit</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" action="../../process/add_blood_unit_process.php">
                            <div class="row g-4">
                                <!-- Donor Selection -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select name="donor_id" class="form-select" id="donorSelect" required>
                                            <option value="">-- Select Donor --</option>
                                            <?php while ($row = $donors->fetch_assoc()): ?>
                                                <option value="<?= $row['donor_id'] ?>">
                                                    <?= htmlspecialchars($row['name']) ?> (ID: <?= $row['donor_id'] ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <label for="donorSelect">
                                            <i class="fas fa-user me-2"></i>Select Donor
                                        </label>
                                    </div>
                                </div>

                                                          
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" 
                                               name="blood_type" 
                                               class="form-control blood-type-input" 
                                               id="bloodType" 
                                               placeholder="A+" 
                                               pattern="^(A|B|AB|O)[+-]$"
                                               title="Please enter valid blood type (A+, A-, B+, B-, AB+, AB-, O+, O-)"
                                               required>
                                        <label for="bloodType">
                                            <i class="fas fa-tint me-2"></i>Blood Type
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <small>Examples: A+, B-, AB+, O-</small>
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               name="quantity" 
                                               class="form-control" 
                                               id="quantity" 
                                               placeholder="450" 
                                               min="100" 
                                               max="1000" 
                                               step="50"
                                               required>
                                        <label for="quantity">
                                            <i class="fas fa-flask me-2"></i>Quantity (ml)
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <small>Standard donation: 450ml</small>
                                    </div>
                                </div> 

                                
                                

                            <!-- Submit Button -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="inventory.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Add Blood Unit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4 border-0 bg-light">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h6>Safe Collection</h6>
                                <small class="text-muted">All units follow safety protocols</small>
                            </div>
                            <div class="col-md-4">
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h6>Quality Assured</h6>
                                <small class="text-muted">Tested and verified units</small>
                            </div>
                            <div class="col-md-4">
                                <div class="text-info mb-2">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h6>Tracked Storage</h6>
                                <small class="text-muted">Proper storage conditions maintained</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       
        // Calculate expiry date (35 days from collection)
        function calculateExpiryDate() {
            const collectionDate = new Date(document.getElementById('collectionDate').value);
            const expiryDate = new Date(collectionDate);
            expiryDate.setDate(expiryDate.getDate() + 35);
            
            const year = expiryDate.getFullYear();
            const month = String(expiryDate.getMonth() + 1).padStart(2, '0');
            const day = String(expiryDate.getDate()).padStart(2, '0');
            
            document.getElementById('expiryDate').value = `${year}-${month}-${day}`;
        }

        // Auto-calculate expiry when collection date changes
        document.getElementById('collectionDate').addEventListener('change', calculateExpiryDate);
        
        // Calculate initial expiry date
        calculateExpiryDate();
    </script>
</body>
</html>