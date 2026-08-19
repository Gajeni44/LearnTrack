<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only System Admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

$users = getData('users');

// Define available permissions
$allPermissions = [
    'view_dashboard' => 'View Dashboard',
    'manage_teachers' => 'Manage Teachers',
    'manage_learners' => 'Manage Learners',
    'mark_attendance' => 'Mark Attendance',
    'view_attendance_history' => 'View Attendance History',
    'manage_grades' => 'Manage Grades',
    'view_academic_performance' => 'View Academic Performance',
    'manage_announcements' => 'Manage Announcements',
    'manage_users' => 'Manage Users',
    'manage_roles' => 'Manage Roles',
    'backup_restore' => 'Backup & Restore',
    'manage_security' => 'Manage Security',
    'view_system_logs' => 'View System Logs',
    'manage_schools' => 'Manage Schools'
];

// Define default permissions for each role
$defaultPermissions = [
    'sys_admin' => array_keys($allPermissions),
    'school_admin' => ['view_dashboard', 'manage_teachers', 'manage_learners', 'mark_attendance', 'view_attendance_history', 'manage_grades', 'view_academic_performance', 'manage_announcements'],
    'principal' => ['view_dashboard', 'manage_teachers', 'manage_learners', 'mark_attendance', 'view_attendance_history', 'manage_grades', 'view_academic_performance', 'manage_announcements'],
    'teacher' => ['view_dashboard', 'manage_learners', 'mark_attendance', 'view_attendance_history', 'manage_grades']
];

// Create roles data file if it doesn't exist
$roles = getData('roles');
if (empty($roles)) {
    $roles = [
        'sys_admin' => [
            'name' => 'System Administrator',
            'permissions' => $defaultPermissions['sys_admin']
        ],
        'school_admin' => [
            'name' => 'School Administrator',
            'permissions' => $defaultPermissions['school_admin']
        ],
        'principal' => [
            'name' => 'Principal',
            'permissions' => $defaultPermissions['principal']
        ],
        'teacher' => [
            'name' => 'Teacher',
            'permissions' => $defaultPermissions['teacher']
        ]
    ];
    saveData('roles', $roles);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_permissions'])) {
        $roleKey = $_POST['role'];
        $selectedPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        $roles[$roleKey]['permissions'] = $selectedPermissions;
        saveData('roles', $roles);
        logActivity("Role permissions updated: " . $roleKey . " by " . $_SESSION['user']['name']);
        
        header("Location: manage_roles.php?updated=1");
        exit();
    }
    
    if (isset($_POST['reset_permissions'])) {
        $roleKey = $_POST['role'];
        $roles[$roleKey]['permissions'] = $defaultPermissions[$roleKey];
        saveData('roles', $roles);
        logActivity("Role permissions reset: " . $roleKey . " by " . $_SESSION['user']['name']);
        
        header("Location: manage_roles.php?reset=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles | LearnTrack</title>
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
            <a href="manage_roles.php" class="list-group-item list-group-item-action active">Manage Roles</a>
            <a href="backup_restore.php" class="list-group-item list-group-item-action">Backup & Restore</a>
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
                <i class="fas fa-user-shield me-2"></i>Manage Roles & Permissions
            </h2>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Role permissions have been successfully updated!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['reset'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-undo me-2"></i> Role permissions have been reset to default!
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($roles as $roleKey => $roleData): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-user-tag me-2"></i><?= htmlspecialchars($roleData['name']) ?>
                                </h5>
                                <span class="badge bg-primary"><?= count($roleData['permissions']) ?> Permissions</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="role" value="<?= $roleKey ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Permissions</label>
                                    <div class="row">
                                        <?php foreach ($allPermissions as $permKey => $permName): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permissions[]" value="<?= $permKey ?>" 
                                                       id="<?= $roleKey ?>_<?= $permKey ?>"
                                                       <?= in_array($permKey, $roleData['permissions']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="<?= $roleKey ?>_<?= $permKey ?>">
                                                    <?= $permName ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="update_permissions" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Permissions
                                    </button>
                                    <button type="submit" name="reset_permissions" class="btn btn-outline-secondary"
                                            onclick="return confirm('Are you sure you want to reset permissions to default?')">
                                        <i class="fas fa-undo me-2"></i>Reset to Default
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Role Statistics -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar me-2"></i>Role Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($roles as $roleKey => $roleData): ?>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?= count(array_filter($users, function($u) use ($roleKey) { return $u['role'] === $roleKey; })) ?></h4>
                                <small class="text-muted"><?= htmlspecialchars($roleData['name']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
