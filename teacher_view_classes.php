<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a Teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: dashboard.php");
    exit();
}

$learners = getData('learners');
$attendance = getData('attendance');

// Get teacher's assigned grade
$teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';

// Filter learners for teacher's assigned grade
$teacherLearners = array_filter($learners, function($l) use ($teacherGrade) {
    return $l['class'] === $teacherGrade;
});

// Get unique dates for attendance
$attendanceDates = [];
foreach ($attendance as $record) {
    $learnerBelongsToTeacher = false;
    foreach ($teacherLearners as $learner) {
        if ($learner['id'] == $record['learner_id']) {
            $learnerBelongsToTeacher = true;
            break;
        }
    }
    if ($learnerBelongsToTeacher && !in_array($record['date'], $attendanceDates)) {
        $attendanceDates[] = $record['date'];
    }
}
rsort($attendanceDates);

// Calculate attendance statistics
$totalLearners = count($teacherLearners);
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;

foreach ($teacherLearners as $learner) {
    $learnerAttendance = array_filter($attendance, function($r) use ($learner) {
        return $r['learner_id'] == $learner['id'];
    });
    if (!empty($learnerAttendance)) {
        $latestRecord = end($learnerAttendance);
        if ($latestRecord['status'] === 'present') $presentCount++;
        elseif ($latestRecord['status'] === 'absent') $absentCount++;
        elseif ($latestRecord['status'] === 'late') $lateCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Classes | LearnTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
    <div class="sidebar-header">
        <h4>LearnTrack</h4>
    </div>
    <div class="list-group">
        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
        <a href="teacher_view_classes.php" class="list-group-item list-group-item-action active">View Classes</a>
        <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
        <a href="teacher_view_profiles.php" class="list-group-item list-group-item-action">View Learner Profiles</a>
        <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
        <a href="marks.php" class="list-group-item list-group-item-action">Capture Marks</a>
        <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
    </div>
</div>

<nav class="navbar navbar-custom no-print">
    <div class="container-fluid">
        <span class="navbar-text ms-auto">
            Teacher: <strong><?= $_SESSION['user']['name'] ?></strong> |
            <span class="badge bg-primary">Teacher</span>
        </span>
    </div>
</nav>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-chalkboard-teacher me-2"></i>My Classes
        </h2>

        <!-- Class Information Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($teacherGrade) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Total Students</h6>
                            <h3 class="mb-0"><?= $totalLearners ?></h3>
                            <small>Enrolled</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Present Today</h6>
                            <h3 class="mb-0"><?= $presentCount ?></h3>
                            <small>Latest attendance</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Absent Today</h6>
                            <h3 class="mb-0"><?= $absentCount ?></h3>
                            <small>Latest attendance</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Late Today</h6>
                            <h3 class="mb-0"><?= $lateCount ?></h3>
                            <small>Latest attendance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class List -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-users me-2"></i>Class List
                </h5>
                <div class="btn-group btn-group-sm no-print">
                    <button class="btn btn-outline-primary" onclick="downloadClassList()">
                        <i class="fas fa-download me-1"></i>Download CSV
                    </button>
                    <button class="btn btn-outline-success" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="classListTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Parent/Guardian</th>
                                <th>Parent Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teacherLearners)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No learners enrolled in this class yet.</td></tr>
                            <?php else: ?>
                                <?php $count = 1; foreach ($teacherLearners as $learner): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td><strong><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($learner['parent_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($learner['parent_phone'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= $learner['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($learner['status'] ?? 'active') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="teacher_view_profiles.php?learner_id=<?= $learner['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Register -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-calendar-check me-2"></i>Attendance Register
                </h5>
                <div class="btn-group btn-group-sm no-print">
                    <select class="form-select form-select-sm" id="dateSelect" onchange="loadAttendanceRegister()">
                        <option value="">Select Date</option>
                        <?php foreach ($attendanceDates as $date): ?>
                            <option value="<?= htmlspecialchars($date) ?>"><?= date('M j, Y', strtotime($date)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-success" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print Register
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="attendanceRegisterContent">
                    <p class="text-muted">Select a date to view the attendance register.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
<script>
function downloadClassList() {
    const table = document.getElementById('classListTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (const row of rows) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        for (const col of cols) {
            if (col.cellIndex < 5) { // Skip actions column
                rowData.push(col.innerText);
            }
        }
        csv.push(rowData.join(','));
    }

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = '<?= $teacherGrade ?>_class_list.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

function loadAttendanceRegister() {
    const date = document.getElementById('dateSelect').value;
    if (!date) {
        document.getElementById('attendanceRegisterContent').innerHTML = '<p class="text-muted">Select a date to view the attendance register.</p>';
        return;
    }

    // This would typically make an AJAX call to get attendance data
    // For now, we'll show a placeholder
    document.getElementById('attendanceRegisterContent').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Attendance register for ${new Date(date).toLocaleDateString()} would be displayed here.
            <br><small>This feature requires additional implementation to load attendance data by date.</small>
        </div>
    `;
}
</script>
</body>
</html>
