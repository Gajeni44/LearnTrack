<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only System Admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

$backupDir = 'backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_backup'])) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . 'backup_' . $timestamp . '.json';
    
    $backupData = [
        'timestamp' => $timestamp,
        'created_by' => $_SESSION['user']['name'],
        'users' => getData('users'),
        'learners' => getData('learners'),
        'marks' => getData('marks'),
        'attendance' => getData('attendance'),
        'weekly_registers' => getData('weekly_registers'),
        'announcements' => getData('announcements'),
        'system_logs' => getData('system_logs'),
        'schools' => getData('schools'),
        'roles' => getData('roles')
    ];
    
    file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
    logActivity("Backup created: " . $backupFile . " by " . $_SESSION['user']['name']);
    
    header("Location: backup_restore.php?backup=1");
    exit();
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_backup'])) {
    $backupFile = $_POST['backup_file'];
    if (file_exists($backupFile)) {
        $backupData = json_decode(file_get_contents($backupFile), true);
        
        saveData('users', $backupData['users']);
        saveData('learners', $backupData['learners']);
        saveData('marks', $backupData['marks']);
        saveData('attendance', $backupData['attendance']);
        saveData('weekly_registers', $backupData['weekly_registers']);
        saveData('announcements', $backupData['announcements']);
        saveData('system_logs', $backupData['system_logs']);
        saveData('schools', $backupData['schools']);
        saveData('roles', $backupData['roles']);
        
        logActivity("Backup restored: " . $backupFile . " by " . $_SESSION['user']['name']);
        
        header("Location: backup_restore.php?restore=1");
        exit();
    }
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_backup'])) {
    $backupFile = $_POST['backup_file'];
    if (file_exists($backupFile)) {
        unlink($backupFile);
        logActivity("Backup deleted: " . $backupFile . " by " . $_SESSION['user']['name']);
        
        header("Location: backup_restore.php?deleted=1");
        exit();
    }
}

// Get list of backup files
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $backupDir . $file;
            $backups[] = [
                'file' => $file,
                'path' => $filePath,
                'size' => filesize($filePath),
                'date' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }
    }
    rsort($backups); // Show newest first
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore | LearnTrack</title>
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
            <a href="backup_restore.php" class="list-group-item list-group-item-action active">Backup & Restore</a>
            <a href="security.php" class="list-group-item list-group-item-action">Security</a>
            <a href="contact_details.php" class="list-group-item list-group-item-action">Contact Details</a>
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
                <i class="fas fa-database me-2"></i>Backup & Restore
            </h2>
            
            <?php if (isset($_GET['backup'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Backup has been successfully created!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['restore'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-undo me-2"></i> System has been successfully restored from backup!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-trash me-2"></i> Backup has been successfully deleted!
                </div>
            <?php endif; ?>

            <!-- Create Backup Section -->
            <div class="card p-4 mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3 mb-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>Create New Backup
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Create a complete backup of all system data including users, learners, marks, attendance, and settings.</p>
                    <form method="POST">
                        <button type="submit" name="create_backup" class="btn btn-primary px-4">
                            <i class="fas fa-download me-2"></i>Create Backup
                        </button>
                    </form>
                </div>
            </div>

            <!-- Available Backups -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-history me-2"></i>Available Backups
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-database fa-3x mb-3"></i>
                            <p>No backups available. Create your first backup above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Backup File</th>
                                        <th>Date Created</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($backup['file']) ?></strong></td>
                                        <td><?= htmlspecialchars($backup['date']) ?></td>
                                        <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['path']) ?>">
                                                    <button type="submit" name="restore_backup" class="btn btn-outline-primary" 
                                                            onclick="return confirm('Are you sure you want to restore from this backup? This will replace all current data!')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['path']) ?>">
                                                    <button type="submit" name="delete_backup" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this backup?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Backup Statistics -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar me-2"></i>Backup Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?= count($backups) ?></h4>
                                <small class="text-muted">Total Backups</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success">
                                    <?php 
                                    $totalSize = array_sum(array_column($backups, 'size'));
                                    echo number_format($totalSize / 1024 / 1024, 2) . ' MB';
                                    ?>
                                </h4>
                                <small class="text-muted">Total Size</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info">
                                    <?php 
                                    $latestBackup = !empty($backups) ? $backups[0]['date'] : 'N/A';
                                    echo $latestBackup;
                                    ?>
                                </h4>
                                <small class="text-muted">Latest Backup</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning">
                                    <?php 
                                    $oldestBackup = !empty($backups) ? end($backups)['date'] : 'N/A';
                                    echo $oldestBackup;
                                    ?>
                                </h4>
                                <small class="text-muted">Oldest Backup</small>
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
