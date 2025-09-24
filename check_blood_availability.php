<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blood_type'])) {
    $blood_type = $_POST['blood_type'];
    
    // Blood compatibility logic
    $compatibleTypes = [];
    switch ($blood_type) {
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
    
    // Check available blood units
    $unitsQuery = $conn->query("SELECT bu.*, d.blood_type, u.name as donor_name, u.contact_no 
        FROM blood_units bu
        JOIN donors d ON bu.donor_id = d.donor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE d.blood_type IN ($typesStr) 
        AND bu.available_status = 1 
        AND bu.test_results = 'Safe for use'
        ORDER BY CASE d.blood_type WHEN '$blood_type' THEN 1 ELSE 2 END");
    
    if ($unitsQuery->num_rows > 0) {
        echo '<div class="alert alert-success mb-3">
                <i class="fas fa-check-circle"></i> Available blood units found for ' . $blood_type . '
              </div>';
        echo '<div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Unit ID</th>
                            <th>Blood Type</th>
                            <th>Donor</th>
                            <th>Contact</th>
                            <th>Match</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while ($unit = $unitsQuery->fetch_assoc()) {
            $matchType = ($unit['blood_type'] === $blood_type) ? 'Exact Match' : 'Compatible';
            $badgeClass = ($unit['blood_type'] === $blood_type) ? 'bg-success' : 'bg-warning';
            
            echo '<tr>
                    <td>#' . $unit['unit_id'] . '</td>
                    <td><span class="badge bg-danger">' . $unit['blood_type'] . '</span></td>
                    <td>' . htmlspecialchars($unit['donor_name']) . '</td>
                    <td>' . $unit['contact_no'] . '</td>
                    <td><span class="badge ' . $badgeClass . '">' . $matchType . '</span></td>
                  </tr>';
        }
        
        echo '</tbody></table></div>';
    } else {
        // Check for available donors
        $donorsQuery = $conn->query("SELECT d.*, u.name, u.contact_no 
            FROM donors d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.blood_type IN ($typesStr) 
            AND d.availability_status = 1");
        
        if ($donorsQuery->num_rows > 0) {
            echo '<div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i> No blood units available, but compatible donors found
                  </div>';
            echo '<div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Donor ID</th>
                                <th>Name</th>
                                <th>Blood Type</th>
                                <th>Contact</th>
                                <th>Match</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            while ($donor = $donorsQuery->fetch_assoc()) {
                $matchType = ($donor['blood_type'] === $blood_type) ? 'Exact Match' : 'Compatible';
                $badgeClass = ($donor['blood_type'] === $blood_type) ? 'bg-success' : 'bg-warning';
                
                echo '<tr>
                        <td>#' . $donor['donor_id'] . '</td>
                        <td>' . htmlspecialchars($donor['name']) . '</td>
                        <td><span class="badge bg-danger">' . $donor['blood_type'] . '</span></td>
                        <td>' . $donor['contact_no'] . '</td>
                        <td><span class="badge ' . $badgeClass . '">' . $matchType . '</span></td>
                      </tr>';
            }
            
            echo '</tbody></table></div>';
            echo '<div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Contact these donors for fresh blood donation
                  </div>';
        } else {
            echo '<div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> No compatible blood units or donors available for ' . $blood_type . '
                  </div>';
            echo '<div class="alert alert-info">
                    <i class="fas fa-bell"></i> We will notify you when compatible blood becomes available
                  </div>';
            
            // Add notification to database
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $message = "No compatible blood available for $blood_type. You will be notified when blood becomes available.";
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, sent_date) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $user_id, $message);
                $stmt->execute();
            }
        }
    }
} else {
    echo '<div class="alert alert-danger">Invalid request</div>';
}
?>