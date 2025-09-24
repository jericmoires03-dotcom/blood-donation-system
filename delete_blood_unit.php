<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unit_id'])) {
    $unit_id = intval($_POST['unit_id']);
    
    $stmt = $conn->prepare("DELETE FROM blood_units WHERE unit_id = ?");
    $stmt->bind_param("i", $unit_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blood unit deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete blood unit']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>