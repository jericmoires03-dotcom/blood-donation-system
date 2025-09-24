<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Check if is_read column exists, if not add it
$checkColumn = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0");
}

// Check if created_at column exists, if not use sent_date
$checkCreatedAt = $conn->query("SHOW COLUMNS FROM notifications LIKE 'created_at'");
$dateColumn = $checkCreatedAt->num_rows > 0 ? 'created_at' : 'sent_date';

// Mark notifications as read when viewed
$markRead = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$markRead->bind_param("i", $user_id);
$markRead->execute();

// Get all notifications for the user
$notifications = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY $dateColumn DESC LIMIT 50");
$notifications->bind_param("i", $user_id);
$notifications->execute();
$result = $notifications->get_result();

// Get unread count
$unreadCount = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadCount->bind_param("i", $user_id);
$unreadCount->execute();
$unread = $unreadCount->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - Blood Bank Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            border-left-color: #0d6efd;
            background-color: #f8f9ff;
        }
        .notification-item.danger {
            border-left-color: #dc3545;
        }
        .notification-item.warning {
            border-left-color: #ffc107;
        }
        .notification-item.success {
            border-left-color: #198754;
        }
        .notification-item.info {
            border-left-color: #0dcaf0;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .notification-icon.danger { background-color: #dc3545; }
        .notification-icon.warning { background-color: #ffc107; }
        .notification-icon.success { background-color: #198754; }
        .notification-icon.info { background-color: #0dcaf0; }
        .notification-icon.default { background-color: #6c757d; }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="bg-primary text-white py-3 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                        <div>
                            <h1 class="h2 mb-0">My Notifications</h1>
                            <p class="mb-0 opacity-75">Stay updated with important messages</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php 
                    switch ($role) {
                        case 'Admin':
                            $dashboardFile = 'admin.php';
                            break;
                        case 'Staff':
                            $dashboardFile = 'staff.php';
                            break;
                        case 'Donor':
                            $dashboardFile = 'donor.php';
                            break;
                        case 'Seeker':
                            $dashboardFile = 'seeker.php';
                            break;
                        default:
                            $dashboardFile = 'seeker.php';
                            break;
                    }
                    ?>
                    <a href="<?= $dashboardFile ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Notification Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                        <h5 class="card-title"><?= $result->num_rows ?></h5>
                        <p class="card-text text-muted">Total Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope-open fa-2x text-success mb-2"></i>
                        <h5 class="card-title"><?= $unread ?></h5>
                        <p class="card-text text-muted">Unread</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                        <h5 class="card-title"><?= $result->num_rows - $unread ?></h5>
                        <p class="card-text text-muted">Read</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Notifications</h5>
                <div>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="notificationActions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Actions
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="notificationActions">
                            <li><a class="dropdown-item" href="#" onclick="markAllRead()">
                                <i class="fas fa-check-double me-2"></i>Mark All Read
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="clearAll()">
                                <i class="fas fa-trash me-2"></i>Clear All
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($result->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($notification = $result->fetch_assoc()): ?>
                            <div class="list-group-item notification-item <?= isset($notification['type']) ? $notification['type'] : 'info' ?> <?= isset($notification['is_read']) && $notification['is_read'] ? '' : 'unread' ?>">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon <?= isset($notification['type']) ? $notification['type'] : 'default' ?> me-3">
                                        <?php
                                        $notifType = isset($notification['type']) ? $notification['type'] : 'default';
                                        switch ($notifType) {
                                            case 'danger':
                                                $icon = 'fas fa-exclamation-triangle';
                                                break;
                                            case 'warning':
                                                $icon = 'fas fa-exclamation-circle';
                                                break;
                                            case 'success':
                                                $icon = 'fas fa-check-circle';
                                                break;
                                            case 'info':
                                                $icon = 'fas fa-info-circle';
                                                break;
                                            default:
                                                $icon = 'fas fa-bell';
                                                break;
                                        }
                                        ?>
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php 
                                                    $displayDate = isset($notification[$dateColumn]) && $notification[$dateColumn] ? $notification[$dateColumn] : (isset($notification['sent_date']) && $notification['sent_date'] ? $notification['sent_date'] : date('Y-m-d H:i:s'));
                                                    echo date('M j, Y g:i A', strtotime($displayDate));
                                                    ?>
                                                </small>
                                            </div>
                                            
                                                <ul class="dropdown-menu">
                                                    <?php if (!(isset($notification['is_read']) ? $notification['is_read'] : 0)): ?>
                                                        <li><a class="dropdown-item" href="#" onclick="markAsRead(<?= $notification['notification_id'] ?>)">
                                                            <i class="fas fa-check me-2"></i>Mark as Read
                                                        </a></li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="deleteNotificationHandler(event, this)" data-notification-id="<?= $notification['notification_id'] ?>">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Notifications</h5>
                        <p class="text-muted">You don't have any notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(notificationId) {
            fetch('../../process/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to mark as read'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }

        function markAllRead() {
            if (!confirm('Mark all notifications as read?')) return;

            fetch('../../process/mark_all_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to mark all as read');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Failed to mark all notifications as read. Please try again.');
            });
        }

function deleteNotificationHandler(event, element) {
    event.preventDefault();
    const notificationId = element.getAttribute('data-notification-id');
    deleteNotification(notificationId);
}

function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        fetch('../../process/delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete notification'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

        function clearAll() {
            if (!confirm('Delete all notifications? This action cannot be undone.')) return;

            fetch('../../process/clear_all_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to clear notifications');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Failed to clear notifications. Please try again.');
            });
        }
    </script>
</body>
</html>