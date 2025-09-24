<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $conn->query("UPDATE notifications SET is_read = 1 WHERE notification_id = $notification_id AND user_id = {$_SESSION['user_id']}");
    header("Location: notifications.php");
    exit;
}

// Get all notifications for admin
$notifications = $conn->query("SELECT * FROM notifications 
    WHERE user_id = {$_SESSION['user_id']} 
    ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-bell me-2"></i>Notifications</h2>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notification = $notifications->fetch_assoc()): ?>
                                <div class="alert alert-<?= $notification['type'] == 'info' ? 'info' : ($notification['type'] == 'warning' ? 'warning' : 'success') ?> 
                                    <?= $notification['is_read'] ? 'alert-dismissible' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted">
                                                <?= date('F j, Y g:i A', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?mark_read=<?= $notification['notification_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                Mark as Read
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No notifications yet</h5>
                                <p class="text-muted">You'll see notifications here when there are updates.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>