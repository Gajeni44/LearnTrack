<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a School Admin or Principal
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['school_admin', 'principal'])) {
    header("Location: dashboard.php");
    exit();
}

$schools = getData('schools');
$users = getData('users');

// Get the school for the current school admin or principal
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

$message = "";
$alertType = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_school'])) {
        if ($school) {
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

            foreach ($schools as &$s) {
                if ($s['id'] == $school['id']) {
                    $s['name'] = $_POST['name'];
                    $s['emis_number'] = $_POST['emis_number'];
                    $s['address'] = $_POST['address'];
                    $s['telephone'] = $_POST['telephone'];
                    $s['email'] = $_POST['email'];
                    $s['website'] = $_POST['website'];
                    $s['principal_name'] = $_POST['principal_name'];
                    $s['slogan'] = $_POST['slogan'];
                    if (isset($logoPath)) {
                        $s['logo'] = $logoPath;
                    }
                    break;
                }
            }

            saveData('schools', $schools);
            logActivity("School profile updated: " . $_POST['name'] . " by " . $_SESSION['user']['name']);

            $message = "School profile updated successfully!";
            $alertType = "success";

            // Refresh school data
            foreach ($schools as $s) {
                if ($s['id'] == $userSchoolId) {
                    $school = $s;
                    break;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Profile | LearnTrack</title>
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
        <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
        <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
        <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
        <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
        <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
        <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
        <a href="school_profile.php" class="list-group-item list-group-item-action active">School Profile</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
    </div>
</div>

<nav class="navbar navbar-custom">
    <div class="container-fluid">
        <span class="navbar-text ms-auto">
            School Administrator: <strong><?= $_SESSION['user']['name'] ?></strong> |
            <span class="badge bg-primary">School Admin</span>
        </span>
    </div>
</nav>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-school me-2"></i>School Profile
        </h2>

        <?php if($message): ?>
            <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($school): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-edit me-2"></i>Edit School Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">School Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($school['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">EMIS Number</label>
                        <input type="text" name="emis_number" class="form-control" value="<?= htmlspecialchars($school['emis_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($school['address']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telephone</label>
                        <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($school['telephone']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($school['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($school['website'] ?? '') ?>" placeholder="https://example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Principal Name</label>
                        <input type="text" name="principal_name" class="form-control" value="<?= htmlspecialchars($school['principal_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">School Slogan</label>
                        <input type="text" name="slogan" class="form-control" value="<?= htmlspecialchars($school['slogan'] ?? '') ?>" placeholder="Enter school motto or slogan">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">School Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small class="text-muted">Upload new logo (PNG, JPG, JPEG, GIF) - leave empty to keep current</small>
                        <?php if (!empty($school['logo'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">Current logo:</small>
                                <br>
                                <img src="<?= htmlspecialchars($school['logo']) ?>" alt="School Logo" style="max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-12 text-end mt-3">
                        <button type="submit" name="update_school" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Update School Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- School Information Display -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-info-circle me-2"></i>School Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Basic Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>School Name:</strong> <?= htmlspecialchars($school['name']) ?></li>
                            <li><strong>EMIS Number:</strong> <?= htmlspecialchars($school['emis_number'] ?? 'N/A') ?></li>
                            <li><strong>Slogan:</strong> <?= htmlspecialchars($school['slogan'] ?? 'N/A') ?></li>
                            <li><strong>Principal:</strong> <?= htmlspecialchars($school['principal_name']) ?></li>
                            <li><strong>Status:</strong> <span class="badge bg-success"><?= ucfirst($school['status']) ?></span></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Contact Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>Address:</strong> <?= htmlspecialchars($school['address']) ?></li>
                            <li><strong>Telephone:</strong> <?= htmlspecialchars($school['telephone']) ?></li>
                            <li><strong>Email:</strong> <?= htmlspecialchars($school['email']) ?></li>
                            <li><strong>Website:</strong> <?= !empty($school['website']) ? '<a href="' . htmlspecialchars($school['website']) . '" target="_blank">' . htmlspecialchars($school['website']) . '</a>' : 'N/A' ?></li>
                            <li><strong>Created:</strong> <?= date('M j, Y', strtotime($school['created_date'])) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No school assigned to your account. Please contact the system administrator.
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
