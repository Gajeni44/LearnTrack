<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only System Admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

// Create contact details data file if it doesn't exist
$contactDetails = getData('contact_details');
if (empty($contactDetails)) {
    $contactDetails = [
        'support_email' => 'support@learntrack.com',
        'support_phone' => '+27 12 345 6789',
        'office_address' => '123 Education Street, Pretoria, South Africa',
        'emergency_email' => 'emergency@learntrack.com',
        'emergency_phone' => '+27 12 345 6790',
        'business_hours' => 'Monday - Friday: 8:00 AM - 5:00 PM',
        'updated_date' => date("Y-m-d")
    ];
    saveData('contact_details', $contactDetails);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_contact'])) {
        $contactDetails = [
            'support_email' => $_POST['support_email'],
            'support_phone' => $_POST['support_phone'],
            'office_address' => $_POST['office_address'],
            'emergency_email' => $_POST['emergency_email'],
            'emergency_phone' => $_POST['emergency_phone'],
            'business_hours' => $_POST['business_hours'],
            'updated_date' => date("Y-m-d")
        ];
        saveData('contact_details', $contactDetails);
        logActivity("Contact details updated by " . $_SESSION['user']['name']);
        
        header("Location: contact_details.php?updated=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Details | LearnTrack</title>
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
            <a href="register_schools.php" class="list-group-item list-group-item-action">Register Schools</a>
            <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="manage_roles.php" class="list-group-item list-group-item-action">Manage Roles</a>
            <a href="backup_restore.php" class="list-group-item list-group-item-action">Backup & Restore</a>
            <a href="security.php" class="list-group-item list-group-item-action">Security</a>
            <a href="contact_details.php" class="list-group-item list-group-item-action active">Contact Details</a>
            <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                System Administrator: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                <span class="badge bg-primary">System Admin</span>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">
                <i class="fas fa-address-card me-2"></i>Contact Details
            </h2>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Contact details have been successfully updated!
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Support Contact Form -->
                <div class="col-md-6 mb-4">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="card-header bg-white py-3 mb-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-edit me-2"></i>Update Contact Details
                            </h5>
                        </div>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Support Email</label>
                                <input type="email" name="support_email" class="form-control" 
                                       value="<?= htmlspecialchars($contactDetails['support_email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Support Phone</label>
                                <input type="text" name="support_phone" class="form-control" 
                                       value="<?= htmlspecialchars($contactDetails['support_phone']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Office Address</label>
                                <textarea name="office_address" class="form-control" rows="3" required><?= htmlspecialchars($contactDetails['office_address']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Emergency Email</label>
                                <input type="email" name="emergency_email" class="form-control" 
                                       value="<?= htmlspecialchars($contactDetails['emergency_email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Emergency Phone</label>
                                <input type="text" name="emergency_phone" class="form-control" 
                                       value="<?= htmlspecialchars($contactDetails['emergency_phone']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Business Hours</label>
                                <input type="text" name="business_hours" class="form-control" 
                                       value="<?= htmlspecialchars($contactDetails['business_hours']) ?>" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_contact" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Update Contact Details
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Contact Details Display -->
                <div class="col-md-6 mb-4">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="card-header bg-white py-3 mb-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-info-circle me-2"></i>Current Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-envelope me-2"></i>Support Contact
                                </h6>
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($contactDetails['support_email']) ?></p>
                                <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($contactDetails['support_phone']) ?></p>
                            </div>
                            <div class="mb-4">
                                <h6 class="fw-bold text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact
                                </h6>
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($contactDetails['emergency_email']) ?></p>
                                <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($contactDetails['emergency_phone']) ?></p>
                            </div>
                            <div class="mb-4">
                                <h6 class="fw-bold text-info">
                                    <i class="fas fa-building me-2"></i>Office Information
                                </h6>
                                <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($contactDetails['office_address']) ?></p>
                                <p class="mb-0"><strong>Business Hours:</strong> <?= htmlspecialchars($contactDetails['business_hours']) ?></p>
                            </div>
                            <div class="alert alert-info border-0">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-2"></i>Last Updated: <?= htmlspecialchars($contactDetails['updated_date']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
