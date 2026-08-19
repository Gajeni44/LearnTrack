<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is an Admin OR a Teacher
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['sys_admin', 'school_admin', 'teacher'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize learners data
$allLearners = getData('learners');

// Filter learners for teachers - show only their assigned grade
if ($_SESSION['user']['role'] === 'teacher') {
    $teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';
    $learners = array_filter($allLearners, function($l) use ($teacherGrade) {
        return $l['class'] === $teacherGrade;
    });
} else {
    $learners = $allLearners;
}

// Handle Adding a New Learner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_learner'])) {
    $newLearner = [
        "id" => time(),
        "first_name" => $_POST['first_name'],
        "last_name" => $_POST['last_name'],
        "class" => $_POST['class'],
        "parent_name" => $_POST['parent_name'],
        "parent_phone" => $_POST['parent_phone'],
        "parent_email" => $_POST['parent_email'],
        "parent_address" => $_POST['parent_address'],
        "relationship" => $_POST['relationship'],
        "status" => "active"
    ];

    $learners[] = $newLearner;
    saveData('learners', $learners);
    logActivity("Registered new learner: " . $_POST['first_name'] . " " . $_POST['last_name']);

    header("Location: manage_learners.php?success=1");
    exit();
}

// Handle Editing a Learner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_learner'])) {
    foreach ($learners as &$learner) {
        if ($learner['id'] == $_POST['learner_id']) {
            $learner['first_name'] = $_POST['first_name'];
            $learner['last_name'] = $_POST['last_name'];
            $learner['class'] = $_POST['class'];
            $learner['parent_name'] = $_POST['parent_name'];
            $learner['parent_phone'] = $_POST['parent_phone'];
            $learner['parent_email'] = $_POST['parent_email'];
            $learner['parent_address'] = $_POST['parent_address'];
            $learner['relationship'] = $_POST['relationship'];
            $learner['status'] = $_POST['status'];
            break;
        }
    }
    saveData('learners', $learners);
    logActivity("Updated learner: " . $_POST['first_name'] . " " . $_POST['last_name']);

    header("Location: manage_learners.php?updated=1");
    exit();
}

// Handle Deleting a Learner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_learner'])) {
    $learners = array_filter($learners, function($l) {
        return $l['id'] != $_POST['learner_id'];
    });
    saveData('learners', array_values($learners));
    logActivity("Deleted learner ID: " . $_POST['learner_id']);
    
    header("Location: manage_learners.php?deleted=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Learners | LearnTrack</title>
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

            <?php if($_SESSION['user']['role'] == 'school_admin'): ?>
                <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action active">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <?php endif; ?>

            <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action active">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
                <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
            <?php endif; ?>

            <?php if($_SESSION['user']['role'] == 'teacher'): ?>
                <a href="manage_learners.php" class="list-group-item list-group-item-action active">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Grading System</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                Logged in as: <strong><?php echo $_SESSION['user']['name']; ?></strong> 
                <span class="badge bg-primary ms-2"><?php echo strtoupper($_SESSION['user']['role']); ?></span>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Student Management</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Student has been successfully registered!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-edit me-2"></i> Student information has been successfully updated!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-trash me-2"></i> Student has been successfully removed!
                </div>
            <?php endif; ?>

            <div class="card p-4 mb-4 shadow-sm border-0">
                <h5 class="card-title mb-3 fw-bold">Register New Learner</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <?php if ($_SESSION['user']['role'] === 'teacher'): ?>
                    <div class="col-md-2">
                        <label class="form-label">Grade/Class</label>
                        <input type="text" name="class" class="form-control" value="<?= $_SESSION['user']['grade_assigned'] ?>" readonly>
                    </div>
                    <?php else: ?>
                    <div class="col-md-2">
                        <label class="form-label">Grade/Class</label>
                        <select name="class" class="form-select">
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label">Parent/Guardian Name</label>
                        <input type="text" name="parent_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Parent Phone</label>
                        <input type="tel" name="parent_phone" class="form-control" placeholder="e.g. 0821234567" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Parent Email</label>
                        <input type="email" name="parent_email" class="form-control" placeholder="e.g. parent@example.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <select name="relationship" class="form-select">
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Parent Address</label>
                        <input type="text" name="parent_address" class="form-control" placeholder="Enter parent/guardian address">
                    </div>
                    <div class="col-md-12 text-end mt-3">
                        <button type="submit" name="add_learner" class="btn btn-success px-4">Save Student</button>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Enrolled Students List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Class</th>
                                    <th>Parent/Guardian Name</th>
                                    <th>Parent Phone</th>
                                    <th>Parent Email</th>
                                    <th>Relationship</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($learners)): ?>
                                    <tr><td colspan="9" class="text-center py-4 text-muted">No learners registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($learners as $l): ?>
                                    <tr>
                                        <td><small class="text-muted"><?php echo $l['id']; ?></small></td>
                                        <td><strong><?php echo $l['first_name'] . " " . $l['last_name']; ?></strong></td>
                                        <td><span class="badge bg-primary"><?php echo $l['class']; ?></span></td>
                                        <td><?php echo $l['parent_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $l['parent_phone'] ?? 'N/A'; ?></td>
                                        <td><?php echo $l['parent_email'] ?? 'N/A'; ?></td>
                                        <td><?php echo $l['relationship'] ?? 'N/A'; ?></td>
                                        <td>
                                            <span class="badge <?= $l['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($l['status'] ?? 'active') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                                        onclick="setEditData(<?= $l['id'] ?>, '<?= htmlspecialchars($l['first_name']) ?>', '<?= htmlspecialchars($l['last_name']) ?>', '<?= htmlspecialchars($l['class']) ?>', '<?= htmlspecialchars($l['parent_name'] ?? '') ?>', '<?= htmlspecialchars($l['parent_phone'] ?? '') ?>', '<?= htmlspecialchars($l['parent_email'] ?? '') ?>', '<?= htmlspecialchars($l['parent_address'] ?? '') ?>', '<?= htmlspecialchars($l['relationship'] ?? '') ?>', '<?= htmlspecialchars($l['status'] ?? 'active') ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="learner_id" value="<?= $l['id'] ?>">
                                                    <button type="submit" name="delete_learner" class="btn btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to remove this student?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="learner_id" id="editLearnerId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="editLastName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade/Class</label>
                                <?php if ($_SESSION['user']['role'] === 'teacher'): ?>
                                    <input type="text" name="class" id="editClass" class="form-control" value="<?= $_SESSION['user']['grade_assigned'] ?>" readonly>
                                <?php else: ?>
                                    <select name="class" id="editClass" class="form-select">
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option>
                                        <option value="Grade 5">Grade 5</option>
                                        <option value="Grade 6">Grade 6</option>
                                        <option value="Grade 7">Grade 7</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" id="editParentName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Phone</label>
                                <input type="tel" name="parent_phone" id="editParentPhone" class="form-control" placeholder="e.g. 0821234567" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Email</label>
                                <input type="email" name="parent_email" id="editParentEmail" class="form-control" placeholder="e.g. parent@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Relationship</label>
                                <select name="relationship" id="editRelationship" class="form-select">
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Parent Address</label>
                                <input type="text" name="parent_address" id="editParentAddress" class="form-control" placeholder="Enter parent/guardian address">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" id="editStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_learner" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function setEditData(learnerId, firstName, lastName, className, parentName, parentPhone, parentEmail, parentAddress, relationship, status) {
            document.getElementById('editLearnerId').value = learnerId;
            document.getElementById('editFirstName').value = firstName;
            document.getElementById('editLastName').value = lastName;
            document.getElementById('editClass').value = className;
            document.getElementById('editParentName').value = parentName;
            document.getElementById('editParentPhone').value = parentPhone;
            document.getElementById('editParentEmail').value = parentEmail;
            document.getElementById('editParentAddress').value = parentAddress;
            document.getElementById('editRelationship').value = relationship;
            document.getElementById('editStatus').value = status;
        }
    </script>
</body>
</html>