<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only System Admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'sys_admin') {
    header("Location: dashboard.php");
    exit();
}

$schools = getData('schools');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_school'])) {
        // Handle logo upload
        $logoPath = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $uploadDir = 'uploads/school_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $logoName = time() . '_' . basename($_FILES['logo']['name']);
            $logoPath = $uploadDir . $logoName;
            move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);
        }

        $newSchool = [
            "id" => time(),
            "name" => $_POST['name'],
            "emis_number" => $_POST['emis_number'],
            "address" => $_POST['address'],
            "telephone" => $_POST['telephone'],
            "email" => $_POST['email'],
            "principal_name" => $_POST['principal_name'],
            "slogan" => $_POST['slogan'],
            "logo" => $logoPath,
            "created_date" => date("Y-m-d"),
            "status" => "active"
        ];
        $schools[] = $newSchool;
        saveData('schools', $schools);
        logActivity("New school registered: " . $_POST['name'] . " by " . $_SESSION['user']['name']);
        
        header("Location: register_schools.php?success=1");
        exit();
    }
    
    if (isset($_POST['edit_school'])) {
        foreach ($schools as &$school) {
            if ($school['id'] == $_POST['id']) {
                // Handle logo upload if new file provided
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $uploadDir = 'uploads/school_logos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $logoName = time() . '_' . basename($_FILES['logo']['name']);
                    $logoPath = $uploadDir . $logoName;
                    move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);
                    $school['logo'] = $logoPath;
                }

                $school['name'] = $_POST['name'];
                $school['emis_number'] = $_POST['emis_number'];
                $school['address'] = $_POST['address'];
                $school['telephone'] = $_POST['telephone'];
                $school['email'] = $_POST['email'];
                $school['principal_name'] = $_POST['principal_name'];
                $school['slogan'] = $_POST['slogan'];
                break;
            }
        }
        saveData('schools', $schools);
        logActivity("School updated: " . $_POST['name'] . " by " . $_SESSION['user']['name']);
        
        header("Location: register_schools.php?updated=1");
        exit();
    }
    
    if (isset($_POST['delete_school'])) {
        $schools = array_filter($schools, function($school) {
            return $school['id'] != $_POST['id'];
        });
        saveData('schools', array_values($schools));
        logActivity("School deleted by " . $_SESSION['user']['name']);
        
        header("Location: register_schools.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Schools | LearnTrack</title>
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
            <a href="register_schools.php" class="list-group-item list-group-item-action active">Register Schools</a>
            <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="manage_roles.php" class="list-group-item list-group-item-action">Manage Roles</a>
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
                <i class="fas fa-school me-2"></i>Register Schools
            </h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> School has been successfully registered!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-edit me-2"></i> School information has been successfully updated!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-trash me-2"></i> School has been successfully deleted!
                </div>
            <?php endif; ?>

            <!-- Add New School Form -->
            <div class="card p-4 mb-4 shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>Add New School
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#schoolForm">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="collapse show" id="schoolForm">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">School Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">EMIS Number</label>
                                <input type="text" name="emis_number" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telephone</label>
                                <input type="text" name="telephone" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Principal Name</label>
                                <input type="text" name="principal_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Slogan</label>
                            <input type="text" name="slogan" class="form-control" placeholder="Enter school motto or slogan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted">Upload school logo (PNG, JPG, JPEG, GIF)</small>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="add_school" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Register School
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Registered Schools List -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-list me-2"></i>Registered Schools
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($schools)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-school fa-3x mb-3"></i>
                            <p>No schools have been registered yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>School Name</th>
                                        <th>EMIS Number</th>
                                        <th>Address</th>
                                        <th>Telephone</th>
                                        <th>Email</th>
                                        <th>Principal</th>
                                        <th>Slogan</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schools as $school): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($school['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($school['emis_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($school['address']) ?></td>
                                        <td><?= htmlspecialchars($school['telephone']) ?></td>
                                        <td><?= htmlspecialchars($school['email']) ?></td>
                                        <td><?= htmlspecialchars($school['principal_name']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($school['slogan'] ?? '-') ?></small></td>
                                        <td>
                                            <span class="badge <?= $school['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($school['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal" 
                                                        onclick="setEditData(<?= $school['id'] ?>, '<?= htmlspecialchars($school['name']) ?>', '<?= htmlspecialchars($school['emis_number'] ?? '') ?>', '<?= htmlspecialchars($school['address']) ?>', '<?= htmlspecialchars($school['telephone']) ?>', '<?= htmlspecialchars($school['email']) ?>', '<?= htmlspecialchars($school['principal_name']) ?>', '<?= htmlspecialchars($school['slogan'] ?? '') ?>', '<?= htmlspecialchars($school['logo'] ?? '') ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?= $school['id'] ?>">
                                                    <button type="submit" name="delete_school" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this school?')">
                                                        <i class="fas fa-trash"></i>
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
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit School Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">School Name</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">EMIS Number</label>
                            <input type="text" name="emis_number" id="editEmis" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="editAddress" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telephone</label>
                            <input type="text" name="telephone" id="editTelephone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Principal Name</label>
                            <input type="text" name="principal_name" id="editPrincipal" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Slogan</label>
                            <input type="text" name="slogan" id="editSlogan" class="form-control" placeholder="Enter school motto or slogan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Logo</label>
                            <input type="file" name="logo" id="editLogo" class="form-control" accept="image/*">
                            <small class="text-muted">Upload new logo (PNG, JPG, JPEG, GIF) - leave empty to keep current</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_school" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update School
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function setEditData(id, name, emis, address, telephone, email, principal, slogan, logo) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmis').value = emis;
            document.getElementById('editAddress').value = address;
            document.getElementById('editTelephone').value = telephone;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPrincipal').value = principal;
            document.getElementById('editSlogan').value = slogan;
            // Note: File input cannot be set via JavaScript for security reasons
            // The logo file upload will be handled separately
        }
    </script>
</body>
</html>
