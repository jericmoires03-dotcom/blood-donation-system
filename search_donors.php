<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

require_once '../db.php';

header('Content-Type: application/json');

if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
    
    if (strlen($query) > 0) {
        $stmt = $conn->prepare("SELECT u.name, d.blood_type, 0 as quantity FROM donors d JOIN users u ON d.user_id = u.user_id WHERE u.name LIKE ? AND d.donor_id NOT IN (SELECT donor_id FROM blood_units) ORDER BY u.name LIMIT 10");
        $search_term = $query . '%';
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $donors = [];
        while ($row = $result->fetch_assoc()) {
            $donors[] = [
                'name' => $row['name'],
                'blood_type' => $row['blood_type'],
                'quantity' => $row['quantity']
            ];
        }
        
        echo json_encode($donors);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>