<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only System Admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

$users = getData('users');
$systemLogs = getData('system_logs');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['toggle_account'])) {
        foreach ($users as &$user) {
            if ($user['id'] == $_POST['user_id']) {
                $user['status'] = $user['status'] === 'active' ? 'disabled' : 'active';
                break;
            }
        }
        saveData('users', $users);
        logActivity("Account status toggled for user ID: " . $_POST['user_id'] . " by " . $_SESSION['user']['name']);
        
        header("Location: security.php?updated=1");
        exit();
    }
    
    if (isset($_POST['reset_password'])) {
        foreach ($users as &$user) {
            if ($user['id'] == $_POST['user_id']) {
                $user['password'] = $_POST['new_password'];
                break;
            }
        }
        saveData('users', $users);
        logActivity("Password reset for user ID: " . $_POST['user_id'] . " by " . $_SESSION['user']['name']);
        
        header("Location: security.php?password_reset=1");
        exit();
    }
    
    if (isset($_POST['delete_account'])) {
        $users = array_filter($users, function($user) {
            return $user['id'] != $_POST['user_id'];
        });
        saveData('users', array_values($users));
        logActivity("Account deleted for user ID: " . $_POST['user_id'] . " by " . $_SESSION['user']['name']);
        
        header("Location: security.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security | LearnTrack</title>
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
            <a href="security.php" class="list-group-item list-group-item-action active">Security</a>
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
                <i class="fas fa-shield-alt me-2"></i>Security Management
            </h2>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Account status has been successfully updated!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['password_reset'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-key me-2"></i> Password has been successfully reset!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-trash me-2"></i> Account has been successfully deleted!
                </div>
            <?php endif; ?>

            <!-- Account Management -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-users-cog me-2"></i>Account Management
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="badge bg-primary"><?= strtoupper($user['role']) ?></span></td>
                                    <td>
                                        <span class="badge <?= ($user['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($user['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#passwordModal" 
                                                    onclick="setPasswordData(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                                                <i class="fas fa-key"></i> Reset Password
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="toggle_account" class="btn btn-outline-warning" 
                                                        onclick="return confirm('Are you sure you want to toggle this account status?')">
                                                    <i class="fas fa-power-off"></i> Toggle
                                                </button>
                                            </form>
                                            <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_account" class="btn btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this account?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Security Statistics -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar me-2"></i>Security Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?= count($users) ?></h4>
                                <small class="text-muted">Total Users</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success">
                                    <?php 
                                    $activeUsers = count(array_filter($users, function($u) { return ($u['status'] ?? 'active') === 'active'; }));
                                    echo $activeUsers;
                                    ?>
                                </h4>
                                <small class="text-muted">Active Accounts</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-danger">
                                    <?php 
                                    $disabledUsers = count(array_filter($users, function($u) { return ($u['status'] ?? 'active') === 'disabled'; }));
                                    echo $disabledUsers;
                                    ?>
                                </h4>
                                <small class="text-muted">Disabled Accounts</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info"><?= count($systemLogs) ?></h4>
                                <small class="text-muted">System Logs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Security Events -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-history me-2"></i>Recent Security Events
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($systemLogs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-history fa-3x mb-3"></i>
                            <p>No security events recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentLogs = array_slice(array_reverse($systemLogs), 0, 10);
                                    foreach ($recentLogs as $log): 
                                    ?>
                                    <tr>
                                        <td><small class="text-muted"><?= htmlspecialchars($log['timestamp']) ?></small></td>
                                        <td><?= htmlspecialchars($log['user']) ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="passwordUserId">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" id="passwordUserName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function setPasswordData(userId, userName) {
            document.getElementById('passwordUserId').value = userId;
            document.getElementById('passwordUserName').value = userName;
        }
    </script>
</body>
</html>
