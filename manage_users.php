<?php
session_start();
require_once 'includes/json_helper.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

$users = getData('users');
$schools = getData('schools');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $newUser = [
        "id" => time(),
        "name" => $_POST['name'],
        "email" => $_POST['email'],
        "password" => $_POST['password'],
        "role" => $_POST['role'],
        "school_id" => $_POST['school_id']
    ];
    $users[] = $newUser;
    saveData('users', $users);
    logActivity("New user created: " . $_POST['name'] . " with role: " . $_POST['role'] . " by " . $_SESSION['user']['name']);
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | LearnTrack</title>
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
            
            <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                <a href="register_schools.php" class="list-group-item list-group-item-action">Register Schools</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="manage_users.php" class="list-group-item list-group-item-action active">Manage Users</a>
                <a href="manage_roles.php" class="list-group-item list-group-item-action">Manage Roles</a>
                <a href="backup_restore.php" class="list-group-item list-group-item-action">Backup & Restore</a>
                <a href="security.php" class="list-group-item list-group-item-action">Security</a>
                <a href="contact_details.php" class="list-group-item list-group-item-action">Contact Details</a>
                <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
            <?php endif; ?>
            
            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                Logged in as: <strong><?= $_SESSION['user']['name'] ?></strong> 
                <span class="badge bg-primary ms-2"><?= strtoupper($_SESSION['user']['role']) ?></span>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">User Management</h2>

            <div class="card p-4 mb-4 shadow-sm border-0">
                <h5 class="card-title mb-3 fw-bold">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" id="roleSelect" onchange="toggleFields()">
                            <option value="school_admin" selected>School Admin</option>
                            <option value="principal">Principal</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="schoolDiv">
                        <label class="form-label">Assign to School</label>
                        <select name="school_id" class="form-select" required>
                            <option value="">Select School</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 text-end mt-3">
                        <button type="submit" name="add_user" class="btn btn-primary px-4">
                            <i class="fas fa-user-plus me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-users me-2"></i>Registered Users
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
                                    <th>School</th>
                                    <th>Assigned Grade</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php
                                        $roleClass = match($u['role']) {
                                            'sys_admin' => 'bg-danger',
                                            'school_admin' => 'bg-warning',
                                            'principal' => 'bg-info',
                                            'teacher' => 'bg-primary',
                                            'parent' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $roleClass ?>"><?= strtoupper($u['role']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (isset($u['school_id']) && $u['school_id']): ?>
                                            <?php
                                            $schoolName = 'N/A';
                                            foreach ($schools as $school) {
                                                if ($school['id'] == $u['school_id']) {
                                                    $schoolName = htmlspecialchars($school['name']);
                                                    break;
                                                }
                                            }
                                            echo $schoolName;
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $u['grade_assigned'] ?? 'N/A' ?></strong></td>
                                    <td>
                                        <?php if ($u['role'] !== 'sys_admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function toggleFields() {
            var role = document.getElementById("roleSelect").value;
            var schoolDiv = document.getElementById("schoolDiv");

            // School assignment is always required for school_admin and principal
            schoolDiv.style.display = "block";
        }

        // Initialize field display on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields();
        });
    </script>
</body>
</html>