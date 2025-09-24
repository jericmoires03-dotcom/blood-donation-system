
    <?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'process_donation') {
        $donor_name = $_POST['donor_name'];
        $blood_type = $_POST['blood_type'];
        $quantity = $_POST['quantity'];
        $safe_for_use = $_POST['safe_for_use'];
        
        // Get donor ID from name
        $stmt = $conn->prepare("SELECT d.donor_id FROM donors d JOIN users u ON d.user_id = u.user_id WHERE u.name = ?");
        $stmt->bind_param("s", $donor_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = "Error: Donor '$donor_name' does not exist.";
            $message_type = 'error';
        } else {
            $donor_row = $result->fetch_assoc();
            $donor_id = $donor_row['donor_id'];
            // Check if blood unit already exists for this donor
            $stmt = $conn->prepare("SELECT unit_id FROM blood_units WHERE donor_id = ?");
            $stmt->bind_param("i", $donor_id);
            $stmt->execute();
            $existing_unit = $stmt->get_result();
            
            if ($safe_for_use === 'yes') {
                if ($existing_unit->num_rows > 0) {
                    // Update existing blood unit
                    $stmt = $conn->prepare("UPDATE blood_units SET collection_date = NOW(), blood_type = ?, quantity = ?, test_results = 'Safe for use', available_status = 1 WHERE donor_id = ?");
                    $stmt->bind_param("sdi", $blood_type, $quantity, $donor_id);
                } else {
                    // Create new blood unit
                    $stmt = $conn->prepare("INSERT INTO blood_units (donor_id, collection_date, blood_type, quantity, test_results, available_status) VALUES (?, NOW(), ?, ?, 'Safe for use', 1)");
                    $stmt->bind_param("isd", $donor_id, $blood_type, $quantity);
                }
                $stmt->execute();
                
                // Update donor availability status
                $stmt = $conn->prepare("UPDATE donors SET availability_status = 1, last_donation_date = NOW() WHERE donor_id = ?");
                $stmt->bind_param("i", $donor_id);
                $stmt->execute();
                
                $message = "Blood donation processed successfully and added to inventory!";
                $message_type = 'success';
            } else {
                if ($existing_unit->num_rows > 0) {
                    // Update existing blood unit as discarded
                    $stmt = $conn->prepare("UPDATE blood_units SET collection_date = NOW(), blood_type = ?, quantity = ?, test_results = 'Failed safety screening - Discarded', available_status = 0 WHERE donor_id = ?");
                    $stmt->bind_param("sdi", $blood_type, $quantity, $donor_id);
                } else {
                    // Create new discarded blood unit
                    $stmt = $conn->prepare("INSERT INTO blood_units (donor_id, collection_date, blood_type, quantity, test_results, available_status) VALUES (?, NOW(), ?, ?, 'Failed safety screening - Discarded', 0)");
                    $stmt->bind_param("isd", $donor_id, $blood_type, $quantity);
                }
                $stmt->execute();
                
                $message = "Blood donation discarded due to safety concerns.";
                $message_type = 'error';
            }
        }
    }
    
    if ($action === 'request_blood') {
        $seeker_id = $_POST['seeker_id'];
        $blood_type = $_POST['blood_type'];
        $quantity = $_POST['quantity'];
        $urgency = $_POST['urgency'];
        $hospital = $_POST['hospital'];
        $contact = $_POST['contact'];
        $reason = $_POST['reason'];
        
        $stmt = $conn->prepare("INSERT INTO blood_requests (seeker_id, required_blood_type, quantity, units_needed, request_date, fulfilled_status, reason) VALUES (?, ?, ?, ?, NOW(), 0, ?)");
        $stmt->bind_param("isdis", $seeker_id, $blood_type, $quantity, $quantity, $reason);
        
        if ($stmt->execute()) {
            $message = "Blood request submitted successfully!";
            $message_type = 'success';
            
            // Redirect to match_requests.php for seekers
            if ($_SESSION['role'] === 'Seeker') {
                $_SESSION['success_message'] = $message;
                header("Location: match_requests.php");
                exit;
            }
        } else {
            $message = "Failed to submit blood request.";
            $message_type = 'error';
        }
    }
    
    if ($action === 'update_status') {
        $request_id = $_POST['request_id'];
        $status = $_POST['status'];
        
        $fulfilled_status = ($status === 'fulfilled') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE blood_requests SET fulfilled_status = ? WHERE request_id = ?");
        $stmt->bind_param("ii", $fulfilled_status, $request_id);
        
        if ($stmt->execute()) {
            $message = "Request status updated successfully!";
            $message_type = 'success';
            
            // Redirect based on user role
            if ($_SESSION['role'] === 'Admin') {
                $_SESSION['success_message'] = $message;
                header("Location: admin.php");
                exit;
            }
        } else {
            $message = "Failed to update request status.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Requests - Blood Donation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            font-size: 24px;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-submit, .btn-update {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            color: white;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        
        .btn-update {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        .suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
        }
        
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .suggestion-item:hover {
            background: #f5f5f5;
        }
        
        .form-group {
            position: relative;
        }
        
        .validation-message {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .validation-success {
            color: #28a745;
        }
        
        .validation-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php
    $dashboard_url = ($_SESSION['role'] === 'Admin') ? 'admin.php' : 
                    (($_SESSION['role'] === 'Staff') ? 'staff.php' : 'seeker.php');
    ?>
    <a href="<?= $dashboard_url ?>" class="back-btn">← Back to Dashboard</a>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>" style="grid-column: 1 / -1;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff'): ?>
        <div class="form-card">
            <h2>Donor Lookup</h2>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Donor ID</th>
                            <th>Name</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $donors_query = $conn->query("SELECT d.donor_id, u.name, d.blood_type, u.contact_no, 0 as quantity FROM donors d JOIN users u ON d.user_id = u.user_id WHERE d.donor_id NOT IN (SELECT donor_id FROM blood_units) ORDER BY d.donor_id");
                        while ($donor = $donors_query->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?= $donor['donor_id'] ?></strong></td>
                            <td><?= htmlspecialchars($donor['name']) ?></td>
                            <td><span class="badge bg-danger"><?= $donor['blood_type'] ?></span></td>
                            <td><?= $donor['quantity'] ?> units</td>
                            <td><?= htmlspecialchars($donor['contact_no']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="form-card">
            <h2>Process Donated Blood</h2>
            <form method="POST">
                <input type="hidden" name="action" value="process_donation">
                
                <div class="form-group">
                    <label for="donor_name">Donor Name:</label>
                    <input type="text" id="donor_name" name="donor_name" autocomplete="off" required>
                    <div id="donor_suggestions" class="suggestions"></div>
                    <div id="donor_validation" class="validation-message"></div>
                </div>
                
                <div class="form-group">
                    <label for="blood_type_donation">Blood Type:</label>
                    <select id="blood_type_donation" name="blood_type" required>
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
                
                <div class="form-group">
                    <label for="quantity_donation">Quantity (units):</label>
                    <input type="number" id="quantity_donation" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="safe_for_use">Safe for Use?</label>
                    <select id="safe_for_use" name="safe_for_use" required>
                        <option value="">Select Status</option>
                        <option value="yes">Yes - Safe</option>
                        <option value="no">No - Discard</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Process Donor's Blood</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'Seeker' || $_SESSION['role'] === 'Admin'): ?>
        <div class="form-card">
            <h2>Blood Request Form</h2>
            <form method="POST">
                <input type="hidden" name="action" value="request_blood">
                
                <div class="form-group">
                    <label for="seeker_id">Seeker ID:</label>
                    <input type="number" id="seeker_id" name="seeker_id" required>
                </div>
                
                <div class="form-group">
                    <label for="blood_type">Blood Type:</label>
                    <select id="blood_type" name="blood_type" required>
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
                
                <div class="form-group">
                    <label for="quantity">Quantity (units):</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="units_needed">Units Needed:</label>
                    <input type="number" id="units_needed" name="units_needed" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <textarea id="reason" name="reason" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>
        <?php endif; ?>
        

        
       
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const donorInput = document.getElementById('donor_name');
    const suggestions = document.getElementById('donor_suggestions');
    
    if (donorInput) {
        donorInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 1) {
                suggestions.style.display = 'none';
                return;
            }
            
            fetch('search_donors.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    const validation = document.getElementById('donor_validation');
                    suggestions.innerHTML = '';
                    
                    if (data.length > 0) {
                        const exactMatch = data.find(donor => donor.name.toLowerCase() === query.toLowerCase());
                        if (exactMatch) {
                            validation.textContent = '✓ Donor found';
                            validation.className = 'validation-message validation-success';
                        } else {
                            validation.textContent = '';
                        }
                        
                        data.forEach(donor => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = donor.name;
                            item.addEventListener('click', function() {
                                donorInput.value = donor.name;
                                document.getElementById('blood_type_donation').value = donor.blood_type;
                                document.getElementById('quantity_donation').value = donor.quantity;
                                suggestions.style.display = 'none';
                                validation.textContent = '✓ Donor found';
                                validation.className = 'validation-message validation-success';
                            });
                            suggestions.appendChild(item);
                        });
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                        validation.textContent = '✗ Donor not found';
                        validation.className = 'validation-message validation-error';
                    }
                });
        });
        
        document.addEventListener('click', function(e) {
            if (!donorInput.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.style.display = 'none';
            }
        });
    }
});
</script>
</body>
</html> 