<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// SMS Notification Function
function sendSMSNotification($phone, $message) {
    // Simulate SMS sending - replace with actual SMS API
    $success = false;
    if (!empty($phone) && !empty($message)) {
        // Simulate successful SMS sending when both parameters are provided
        $success = true; // Replace with actual SMS API call
    }
    return $success;
}

// Get users for notifications
$donors = $conn->query("SELECT u.user_id, u.name, u.email, u.contact_no, d.blood_type 
    FROM users u JOIN donors d ON u.user_id = d.user_id 
    WHERE u.role = 'Donor'");

$seekers = $conn->query("SELECT u.user_id, u.name, u.email, u.contact_no, s.required_blood_type 
    FROM users u JOIN seekers s ON u.user_id = s.user_id 
    WHERE u.role = 'Seeker'");

// Get recent notifications
$recent_notifications = $conn->query("SELECT n.*, u.name 
    FROM notifications n 
    JOIN users u ON n.user_id = u.user_id 
    ORDER BY n.created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications - Blood Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="background: linear-gradient(135deg, #28a745, #20c997); min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Blood Center</h4>
                        <small class="text-white-50">Staff Portal</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="staff.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="send_notifications.php">
                                <i class="fas fa-bell"></i> Send Notifications
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-bell"></i> Send Notifications</h1>
                </div>

                <!-- SMS Notification Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="fas fa-sms"></i> SMS Notifications</h5>
                            </div>
                            <div class="card-body">
                                <form action="../../process/send_notification_process.php" method="POST">
                                    <input type="hidden" name="notification_type" value="sms">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Recipient Type</label>
                                        <select name="recipient_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="all_donors">All Donors</option>
                                            <option value="all_seekers">All Seekers</option>
                                            <option value="blood_type">By Blood Type</option>
                                            <option value="specific_user">Specific User</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="bloodTypeDiv" style="display:none;">
                                        <label class="form-label">Blood Type</label>
                                        <select name="blood_type" class="form-select">
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

                                    <div class="mb-3" id="specificUserDiv" style="display:none;">
                                        <label class="form-label">Select User</label>
                                        <select name="user_id" class="form-select">
                                            <option value="">Choose User</option>
                                            <?php 
                                            $all_users = $conn->query("SELECT user_id, name, contact_no FROM users WHERE role IN ('Donor', 'Seeker')");
                                            while ($user = $all_users->fetch_assoc()): 
                                            ?>
                                            <option value="<?= $user['user_id'] ?>"><?= $user['name'] ?> (<?= $user['contact_no'] ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">SMS Message</label>
                                        <textarea name="message" class="form-control" rows="3" maxlength="160" placeholder="Enter SMS message (max 160 characters)" required></textarea>
                                        <small class="text-muted">Character count: <span id="charCount">0</span>/160</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send SMS
                                    </button>
                                    <div id="smsStatus" class="mt-2"></div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-envelope"></i> Email Notifications</h5>
                            </div>
                            <div class="card-body">
                                <form action="../../process/send_notification_process.php" method="POST">
                                    <input type="hidden" name="notification_type" value="email">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Recipient Type</label>
                                        <select name="recipient_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="all_donors">All Donors</option>
                                            <option value="all_seekers">All Seekers</option>
                                            <option value="blood_type">By Blood Type</option>
                                            <option value="specific_user">Specific User</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <input type="text" name="subject" class="form-control" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea name="message" class="form-control" rows="4" required></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-envelope"></i> Send Email
                                    </button>
                                    <div id="emailStatus" class="mt-2"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <button class="btn btn-warning w-100" onclick="sendUrgentAlert()">
                                            <i class="fas fa-exclamation-triangle"></i><br>
                                            Urgent Blood Need
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-info w-100" onclick="sendDonationReminder()">
                                            <i class="fas fa-calendar-check"></i><br>
                                            Donation Reminder
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-success w-100" onclick="sendThankYou()">
                                            <i class="fas fa-heart"></i><br>
                                            Thank You Message
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-primary w-100" onclick="sendAppointmentConfirm()">
                                            <i class="fas fa-check-circle"></i><br>
                                            Appointment Confirm
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Recent Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Recipient</th>
                                        <th>Message</th>
                                        <th>Type</th>
                                        <th>Sent At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($notification = $recent_notifications->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($notification['name']) ?></td>
                                        <td><?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...</td>
                                        <td>
                                            <span class="badge bg-<?= $notification['type'] == 'success' ? 'success' : 'info' ?>">
                                                <?= ucfirst($notification['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide fields based on recipient type
        document.querySelectorAll('select[name="recipient_type"]').forEach(select => {
            select.addEventListener('change', function() {
                const bloodTypeDiv = document.getElementById('bloodTypeDiv');
                const specificUserDiv = document.getElementById('specificUserDiv');
                
                if (bloodTypeDiv) {
                    bloodTypeDiv.style.display = this.value === 'blood_type' ? 'block' : 'none';
                }
                if (specificUserDiv) {
                    specificUserDiv.style.display = this.value === 'specific_user' ? 'block' : 'none';
                }
            });
        });

        // Character counter for SMS
        document.querySelector('textarea[name="message"]').addEventListener('input', function() {
            const charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = this.value.length;
            }
        });

        // Handle SMS form submission
        document.querySelectorAll('form[action="../../process/send_notification_process.php"]').forEach(form => {
            if (form.querySelector('input[name="notification_type"]').value === 'sms') {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const statusDiv = document.getElementById('smsStatus');
                    statusDiv.innerHTML = '<div class="alert alert-info">Sending SMS...</div>';
                    
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            this.reset();
                        } else {
                            statusDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Parse error:', error);
                        statusDiv.innerHTML = '<div class="alert alert-danger">❌ Failed to process response. Please try again.</div>';
                    });
                });
            }
            
            // Handle Email form submission
            if (form.querySelector('input[name="notification_type"]').value === 'email') {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const statusDiv = document.getElementById('emailStatus');
                    statusDiv.innerHTML = '<div class="alert alert-info">Sending Email...</div>';
                    
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            this.reset();
                        } else {
                            statusDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Parse error:', error);
                        statusDiv.innerHTML = '<div class="alert alert-danger">❌ Failed to process response. Please try again.</div>';
                    });
                });
            }
        });

        // Quick action functions
        function sendUrgentAlert() {
            if (confirm('Send urgent blood need alert to all donors?')) {
                window.location.href = '../../process/send_quick_notification.php?type=urgent';
            }
        }

        function sendDonationReminder() {
            if (confirm('Send donation reminder to all eligible donors?')) {
                window.location.href = '../../process/send_quick_notification.php?type=reminder';
            }
        }

        function sendThankYou() {
            if (confirm('Send thank you message to recent donors?')) {
                window.location.href = '../../process/send_quick_notification.php?type=thankyou';
            }
        }

        function sendAppointmentConfirm() {
            if (confirm('Send appointment confirmation to scheduled donors?')) {
                window.location.href = '../../process/send_quick_notification.php?type=appointment';
            }
        }
    </script>
</body>
</html>