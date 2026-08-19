<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a School Admin or System Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['school_admin', 'sys_admin'])) {
    header("Location: dashboard.php");
    exit();
}

$users = getData('users');
$learners = getData('learners');

// Get all teachers
$teachers = array_filter($users, function($user) {
    return $user['role'] === 'teacher';
});

$message = "";
$alertType = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        $newTeacher = [
            'id' => time(),
            'name' => $_POST['name'],
            'teacher_id' => $_POST['teacher_id'],
            'employee_number' => $_POST['employee_number'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'password' => $_POST['password'],
            'role' => 'teacher',
            'grade_assigned' => $_POST['grade_assigned'],
            'subjects' => isset($_POST['subjects']) ? $_POST['subjects'] : [],
            'status' => 'active'
        ];

        $users[] = $newTeacher;
        saveData('users', $users);

        $message = "Teacher added successfully!";
        $alertType = "success";
        logActivity("Teacher " . $_POST['name'] . " added by " . $_SESSION['user']['name']);

        header("Location: manage_teachers.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
    
    if (isset($_POST['update_teacher'])) {
        foreach ($users as &$user) {
            if ($user['id'] == $_POST['id']) {
                $user['name'] = $_POST['name'];
                $user['teacher_id'] = $_POST['teacher_id'];
                $user['employee_number'] = $_POST['employee_number'];
                $user['email'] = $_POST['email'];
                $user['phone'] = $_POST['phone'];
                $user['grade_assigned'] = $_POST['grade_assigned'];
                $user['subjects'] = isset($_POST['subjects']) ? $_POST['subjects'] : [];
                $user['status'] = $_POST['status'];
                if (!empty($_POST['password'])) {
                    $user['password'] = $_POST['password'];
                }
                break;
            }
        }

        saveData('users', $users);

        $message = "Teacher updated successfully!";
        $alertType = "success";
        logActivity("Teacher " . $_POST['name'] . " updated by " . $_SESSION['user']['name']);

        header("Location: manage_teachers.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
    
    if (isset($_POST['delete_teacher'])) {
        foreach ($users as $key => $user) {
            if ($user['id'] == $_POST['id']) {
                unset($users[$key]);
                break;
            }
        }
        
        $users = array_values($users);
        saveData('users', $users);
        
        $message = "Teacher deleted successfully!";
        $alertType = "warning";
        logActivity("Teacher deleted by " . $_SESSION['user']['name']);
        
        header("Location: manage_teachers.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
}

// Handle URL messages
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $alertType = $_GET['type'] ?? 'info';
}

// Get unique grades for dropdown
$grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | LearnTrack</title>
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
        <a href="manage_teachers.php" class="list-group-item list-group-item-action active">Manage Teachers</a>
        <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
        <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
        <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
        <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
        <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
        
        <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
        <?php endif; ?>
        
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
            <i class="fas fa-chalkboard-teacher me-2"></i>Manage Teachers
        </h2>

        <?php if($message): ?>
            <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Add Teacher Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                <i class="fas fa-plus me-2"></i>Add New Teacher
            </button>
        </div>

        <!-- Teachers Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Teachers List
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Teacher Name</th>
                                <th>Teacher ID</th>
                                <th>Employee Number</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subjects</th>
                                <th>Assigned Grade</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">No teachers found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($teacher['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($teacher['teacher_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($teacher['employee_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></td>
                                    <td><small><?= !empty($teacher['subjects']) ? implode(', ', $teacher['subjects']) : 'N/A' ?></small></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($teacher['grade_assigned']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $teacher['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($teacher['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editTeacher(<?= htmlspecialchars(json_encode($teacher)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteTeacher(<?= $teacher['id'] ?>, '<?= htmlspecialchars($teacher['name']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teacher ID</label>
                            <input type="text" class="form-control" name="teacher_id" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control" name="employee_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subjects (hold Ctrl to select multiple)</label>
                        <select class="form-select" name="subjects[]" multiple>
                            <option value="Mathematics">Mathematics</option>
                            <option value="English">English</option>
                            <option value="Science">Science</option>
                            <option value="Social Studies">Social Studies</option>
                            <option value="Physical Education">Physical Education</option>
                            <option value="Art">Art</option>
                            <option value="Music">Music</option>
                            <option value="Computer Studies">Computer Studies</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Grade</label>
                        <select class="form-select" name="grade_assigned" required>
                            <option value="">Select Grade</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= htmlspecialchars($grade) ?>"><?= htmlspecialchars($grade) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="editTeacherIdHidden">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="editTeacherName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teacher ID</label>
                            <input type="text" class="form-control" name="teacher_id" id="editTeacherId" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control" name="employee_number" id="editEmployeeNumber" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="editPhone" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editTeacherEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password" id="editTeacherPassword">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subjects (hold Ctrl to select multiple)</label>
                        <select class="form-select" name="subjects[]" id="editSubjects" multiple>
                            <option value="Mathematics">Mathematics</option>
                            <option value="English">English</option>
                            <option value="Science">Science</option>
                            <option value="Social Studies">Social Studies</option>
                            <option value="Physical Education">Physical Education</option>
                            <option value="Art">Art</option>
                            <option value="Music">Music</option>
                            <option value="Computer Studies">Computer Studies</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Grade</label>
                        <select class="form-select" name="grade_assigned" id="editTeacherGrade" required>
                            <option value="">Select Grade</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= htmlspecialchars($grade) ?>"><?= htmlspecialchars($grade) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_teacher" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
<script>
function editTeacher(teacher) {
    document.getElementById('editTeacherIdHidden').value = teacher.id;
    document.getElementById('editTeacherName').value = teacher.name;
    document.getElementById('editTeacherId').value = teacher.teacher_id || '';
    document.getElementById('editEmployeeNumber').value = teacher.employee_number || '';
    document.getElementById('editPhone').value = teacher.phone || '';
    document.getElementById('editTeacherEmail').value = teacher.email;
    document.getElementById('editTeacherGrade').value = teacher.grade_assigned;
    document.getElementById('editTeacherPassword').value = '';
    document.getElementById('editStatus').value = teacher.status || 'active';

    // Handle subjects multi-select
    const subjectsSelect = document.getElementById('editSubjects');
    Array.from(subjectsSelect.options).forEach(option => {
        option.selected = teacher.subjects && teacher.subjects.includes(option.value);
    });

    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
}

function deleteTeacher(id, name) {
    if (confirm('Are you sure you want to delete teacher ' + name + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="id" value="' + id + '"><input type="hidden" name="delete_teacher" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
