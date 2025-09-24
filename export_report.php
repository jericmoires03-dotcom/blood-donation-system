<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

$format = $_GET['format'] ?? 'csv';

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="blood_center_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Report Header
    fputcsv($output, ['Blood Center Comprehensive Report - ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // System Statistics
    fputcsv($output, ['SYSTEM STATISTICS']);
    $stats = [
        ['Total Blood Units', $conn->query("SELECT COUNT(*) FROM blood_units")->fetch_row()[0]],
        ['Available Units', $conn->query("SELECT COUNT(*) FROM blood_units WHERE available_status = 1")->fetch_row()[0]],
        ['Total Donors', $conn->query("SELECT COUNT(*) FROM donors")->fetch_row()[0]],
        ['Total Seekers', $conn->query("SELECT COUNT(*) FROM seekers")->fetch_row()[0]],
        ['Pending Requests', $conn->query("SELECT COUNT(*) FROM blood_requests WHERE fulfilled_status = 0")->fetch_row()[0]],
        ['Fulfilled Requests', $conn->query("SELECT COUNT(*) FROM blood_requests WHERE fulfilled_status = 1")->fetch_row()[0]]
    ];
    
    foreach ($stats as $stat) {
        fputcsv($output, $stat);
    }
    
    fputcsv($output, []);
    
    // Blood Type Distribution
    fputcsv($output, ['BLOOD TYPE INVENTORY']);
    fputcsv($output, ['Blood Type', 'Total Units', 'Available Units', 'Used Units', 'Availability %']);
    
    $stock_query = "SELECT d.blood_type, COUNT(*) as total_units, SUM(bu.available_status) as available_units, COUNT(*) - SUM(bu.available_status) as used_units FROM blood_units bu JOIN donors d ON bu.donor_id = d.donor_id GROUP BY d.blood_type ORDER BY d.blood_type";
    $stock_result = $conn->query($stock_query);
    
    while ($row = $stock_result->fetch_assoc()) {
        $availability_percent = ($row['available_units'] / $row['total_units']) * 100;
        fputcsv($output, [
            $row['blood_type'],
            $row['total_units'],
            $row['available_units'],
            $row['used_units'],
            number_format($availability_percent, 1) . '%'
        ]);
    }
    
    fputcsv($output, []);
    
    // Donors List
    fputcsv($output, ['DONORS LIST']);
    fputcsv($output, ['Name', 'Blood Type', 'Phone', 'Email', 'Total Donations', 'Available Units']);
    
    $donors_query = "SELECT u.name, d.blood_type, u.phone, u.email, COUNT(bu.unit_id) as donation_count, SUM(bu.available_status) as available_units FROM donors d JOIN users u ON d.user_id = u.user_id LEFT JOIN blood_units bu ON d.donor_id = bu.donor_id GROUP BY d.donor_id ORDER BY donation_count DESC";
    $donors_result = $conn->query($donors_query);
    
    while ($row = $donors_result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['blood_type'],
            $row['phone'],
            $row['email'],
            $row['donation_count'] ?? 0,
            $row['available_units'] ?? 0
        ]);
    }
    
    fputcsv($output, []);
    
    // Blood Requests
    fputcsv($output, ['BLOOD REQUESTS']);
    fputcsv($output, ['Seeker Name', 'Blood Type', 'Units Needed', 'Request Date', 'Status', 'Phone']);
    
    $requests_query = "SELECT u.name, br.required_blood_type, br.units_needed, br.request_date, CASE WHEN br.fulfilled_status = 1 THEN 'Fulfilled' ELSE 'Pending' END as status, u.phone FROM blood_requests br JOIN seekers s ON br.seeker_id = s.seeker_id JOIN users u ON s.user_id = u.user_id ORDER BY br.request_date DESC";
    $requests_result = $conn->query($requests_query);
    
    while ($row = $requests_result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['required_blood_type'],
            $row['units_needed'],
            $row['request_date'],
            $row['status'],
            $row['phone']
        ]);
    }
    
    fclose($output);
    exit;
}
?>