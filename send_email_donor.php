<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

try {
   
    $required_fields = ['donor_email', 'donor_name', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $mail = new PHPMailer(true);

   
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'cindyloupherracaza11@gmail.com'; 
    $mail->Password = 'ufqr rjya dsop krdu'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

  
    $mail->setFrom('cindyloupherracaza11@gmail.com', 'Blood Donation System');
    $mail->addAddress($_POST['donor_email'], $_POST['donor_name']);

   
    $mail->isHTML(true);
    $mail->Subject = $_POST['subject'];
    
    
    $emailBody = $_POST['message'];
    
    
    if (isset($_POST['includeRequestDetails']) && $_POST['includeRequestDetails'] === 'on') {
        $emailBody .= "<br><br><strong>Request Details:</strong><br>";
        $emailBody .= "Blood Type: " . $_POST['blood_type'] . "<br>";
        $emailBody .= "Units Needed: " . $_POST['units_needed'] . "<br>";
        $emailBody .= "Urgency Level: " . $_POST['urgency_level'] . "<br>";
        $emailBody .= "Request ID: " . $_POST['request_id'];
    }

    $mail->Body = nl2br($emailBody);
    $mail->AltBody = strip_tags($emailBody);

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully to ' . $_POST['donor_name']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Failed to send email: {$e->getMessage()}"
    ]);
}