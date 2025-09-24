<?php

session_start();
require_once '../config/db.php';

echo "<h2>Blood Request System Test</h2>";


echo "<h3>Test 1: Check blood_requests table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'blood_requests'");
if ($result->num_rows > 0) {
    echo "✓ blood_requests table exists<br>";
    
   
    $columns = $conn->query("DESCRIBE blood_requests");
    echo "<strong>Table structure:</strong><br>";
    while ($col = $columns->fetch_assoc()) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
} else {
    echo "✗ blood_requests table does not exist<br>";
}


echo "<h3>Test 2: Check blood_units table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'blood_units'");
if ($result->num_rows > 0) {
    echo "✓ blood_units table exists<br>";
} else {
    echo "✗ blood_units table does not exist<br>";
}


echo "<h3>Test 3: Check seekers table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'seekers'");
if ($result->num_rows > 0) {
    echo "✓ seekers table exists<br>";
} else {
    echo "✗ seekers table does not exist<br>";
}


echo "<h3>Test 4: Check notifications table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows > 0) {
    echo "✓ notifications table exists<br>";
    
    
    $date_columns = $conn->query("SHOW COLUMNS FROM notifications WHERE Field IN ('created_at', 'sent_date')");
    echo "<strong>Date columns:</strong><br>";
    while ($col = $date_columns->fetch_assoc()) {
        echo "- " . $col['Field'] . "<br>";
    }
} else {
    echo "✗ notifications table does not exist<br>";
}


echo "<h3>Test 5: Recent blood requests</h3>";
$recent = $conn->query("SELECT * FROM blood_requests ORDER BY request_date DESC LIMIT 5");
if ($recent && $recent->num_rows > 0) {
    echo "Recent requests found:<br>";
    while ($req = $recent->fetch_assoc()) {
        echo "- Request #" . $req['request_id'] . " - " . $req['required_blood_type'] . " - " . $req['request_date'] . "<br>";
    }
} else {
    echo "No recent requests found<br>";
}

echo "<br><a href='../config/dashboard/seeker.php'>Back to Seeker Dashboard</a>";
?>