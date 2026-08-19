<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a Principal
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: dashboard.php");
    exit();
}

$users = getData('users');
$schools = getData('schools');

// Get the school for the current principal
$userSchoolId = $_SESSION['user']['school_id'] ?? null;
$school = null;

if ($userSchoolId) {
    foreach ($schools as $s) {
        if ($s['id'] == $userSchoolId) {
            $school = $s;
            break;
        }
    }
}

// Get principal settings (stored in user data or separate settings file)
$principalSettings = $_SESSION['user']['settings'] ?? [
    'email_notifications' => true,
    'sms_notifications' => false,
    'attendance_alerts' => true,
    'performance_alerts' => true,
    'signature' => ''
];

$message = "";
$alertType = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update principal settings
        $newSettings = [
            'email_notifications' => isset($_POST['email_notifications']),
            'sms_notifications' => isset($_POST['sms_notifications']),
            'attendance_alerts' => isset($_POST['attendance_alerts']),
            'performance_alerts' => isset($_POST['performance_alerts']),
            'signature' => $_POST['signature']
        ];

        // Update user settings in users.json
        foreach ($users as &$user) {
            if ($user['id'] == $_SESSION['user']['id']) {
                $user['settings'] = $newSettings;
                break;
            }
        }

        saveData('users', $users);
        $_SESSION['user']['settings'] = $newSettings;

        $message = "Settings updated successfully!";
        $alertType = "success";
        logActivity("Principal settings updated by " . $_SESSION['user']['name']);

        header("Location: principal_settings.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }

    if (isset($_POST['update_password'])) {
        // Update password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Verify current password
        foreach ($users as &$user) {
            if ($user['id'] == $_SESSION['user']['id']) {
                if ($user['password'] !== $currentPassword) {
                    $message = "Current password is incorrect!";
                    $alertType = "danger";
                } elseif ($newPassword !== $confirmPassword) {
                    $message = "New passwords do not match!";
                    $alertType = "danger";
                } elseif (strlen($newPassword) < 6) {
                    $message = "Password must be at least 6 characters!";
                    $alertType = "danger";
                } else {
                    $user['password'] = $newPassword;
                    saveData('users', $users);
                    $_SESSION['user']['password'] = $newPassword;

                    $message = "Password updated successfully!";
                    $alertType = "success";
                    logActivity("Password updated by " . $_SESSION['user']['name']);
                }
                break;
            }
        }

        header("Location: principal_settings.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
}

// Handle URL messages
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $alertType = $_GET['type'] ?? 'info';
}

// Refresh settings from session
$principalSettings = $_SESSION['user']['settings'] ?? $principalSettings;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | LearnTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h4>LearnTrack</h4>
    </div>
    <div class="list-group">
        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
        <a href="transfer_letter.php" class="list-group-item list-group-item-action">Transfer Letter</a>
        <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action">Attendance Reports</a>
        <a href="principal_results.php" class="list-group-item list-group-item-action">Results</a>
        <a href="principal_settings.php" class="list-group-item list-group-item-action active">System Settings</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
    </div>
</div>

<nav class="navbar navbar-custom">
    <div class="container-fluid">
        <span class="navbar-text ms-auto">
            Principal: <strong><?= $_SESSION['user']['name'] ?></strong> |
            <span class="badge bg-primary">Principal</span>
        </span>
    </div>
</nav>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-cog me-2"></i>System Settings
        </h2>

        <?php if($message): ?>
            <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Notification Settings -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-bell me-2"></i>Notification Preferences
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" <?= $principalSettings['email_notifications'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="emailNotifications">
                                        <strong>Email Notifications</strong>
                                        <small class="d-block text-muted">Receive notifications via email</small>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sms_notifications" id="smsNotifications" <?= $principalSettings['sms_notifications'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="smsNotifications">
                                        <strong>SMS Notifications</strong>
                                        <small class="d-block text-muted">Receive notifications via SMS</small>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="attendance_alerts" id="attendanceAlerts" <?= $principalSettings['attendance_alerts'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="attendanceAlerts">
                                        <strong>Attendance Alerts</strong>
                                        <small class="d-block text-muted">Get alerts for attendance issues</small>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="performance_alerts" id="performanceAlerts" <?= $principalSettings['performance_alerts'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="performanceAlerts">
                                        <strong>Performance Alerts</strong>
                                        <small class="d-block text-muted">Get alerts for academic performance</small>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Digital Signature</label>
                                <textarea class="form-control" name="signature" rows="3" placeholder="Enter your signature text for documents"><?= htmlspecialchars($principalSettings['signature']) ?></textarea>
                                <small class="text-muted">This signature will appear on official documents</small>
                            </div>
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password Change -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" name="update_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-user me-2"></i>Account Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Principal Details</h6>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> <?= htmlspecialchars($_SESSION['user']['name']) ?></li>
                            <li><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user']['email']) ?></li>
                            <li><strong>Role:</strong> Principal</li>
                            <li><strong>School ID:</strong> <?= $userSchoolId ?></li>
                        </ul>
                    </div>
                    <?php if ($school): ?>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Assigned School</h6>
                        <ul class="list-unstyled">
                            <li><strong>School Name:</strong> <?= htmlspecialchars($school['name']) ?></li>
                            <li><strong>EMIS Number:</strong> <?= htmlspecialchars($school['emis_number'] ?? 'N/A') ?></li>
                            <li><strong>Address:</strong> <?= htmlspecialchars($school['address']) ?></li>
                            <li><strong>Telephone:</strong> <?= htmlspecialchars($school['telephone']) ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
