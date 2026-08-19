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
$marks = getData('marks');

// Get teacher's assigned grade
$teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';

// Filter learners for teacher's assigned grade
$teacherLearners = array_filter($learners, function($l) use ($teacherGrade) {
    return $l['class'] === $teacherGrade;
});

// Get specific learner if requested
$selectedLearner = null;
if (isset($_GET['learner_id'])) {
    foreach ($teacherLearners as $learner) {
        if ($learner['id'] == $_GET['learner_id']) {
            $selectedLearner = $learner;
            break;
        }
    }
}

// Get learner's attendance history
$learnerAttendance = [];
if ($selectedLearner) {
    $learnerAttendance = array_filter($attendance, function($r) use ($selectedLearner) {
        return $r['learner_id'] == $selectedLearner['id'];
    });
    $learnerAttendance = array_reverse($learnerAttendance);
}

// Get learner's marks
$learnerMarks = [];
if ($selectedLearner) {
    $learnerMarks = array_filter($marks, function($m) use ($selectedLearner) {
        return $m['learner_id'] == $selectedLearner['id'];
    });
}

// Calculate attendance statistics
$attendanceStats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'total' => 0
];
foreach ($learnerAttendance as $record) {
    $attendanceStats['total']++;
    if ($record['status'] === 'present') $attendanceStats['present']++;
    elseif ($record['status'] === 'absent') $attendanceStats['absent']++;
    elseif ($record['status'] === 'late') $attendanceStats['late']++;
}

// Calculate average marks
$averageMark = 0;
if (!empty($learnerMarks)) {
    $totalMarks = array_sum(array_column($learnerMarks, 'marks'));
    $averageMark = round($totalMarks / count($learnerMarks), 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Profiles | LearnTrack</title>
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
        <a href="teacher_view_classes.php" class="list-group-item list-group-item-action">View Classes</a>
        <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
        <a href="teacher_view_profiles.php" class="list-group-item list-group-item-action active">View Learner Profiles</a>
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
            <i class="fas fa-user-graduate me-2"></i>Learner Profiles
        </h2>

        <?php if (!$selectedLearner): ?>
        <!-- Learner Selection -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-users me-2"></i>Select Learner
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($teacherLearners)): ?>
                        <div class="col-12">
                            <p class="text-muted">No learners enrolled in your class yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teacherLearners as $learner): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold"><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></h6>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($learner['class']) ?></p>
                                        <a href="teacher_view_profiles.php?learner_id=<?= $learner['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Learner Profile Details -->
        <div class="mb-3 no-print">
            <a href="teacher_view_profiles.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to All Learners
            </a>
        </div>

        <!-- Personal Information -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-user me-2"></i>Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Student Details</h6>
                        <ul class="list-unstyled">
                            <li><strong>Full Name:</strong> <?= htmlspecialchars($selectedLearner['first_name'] . ' ' . $selectedLearner['last_name']) ?></li>
                            <li><strong>Grade/Class:</strong> <?= htmlspecialchars($selectedLearner['class']) ?></li>
                            <li><strong>Status:</strong> <span class="badge <?= $selectedLearner['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($selectedLearner['status']) ?></span></li>
                            <li><strong>Learner ID:</strong> <?= $selectedLearner['id'] ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Parent/Guardian Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> <?= htmlspecialchars($selectedLearner['parent_name'] ?? 'N/A') ?></li>
                            <li><strong>Relationship:</strong> <?= htmlspecialchars($selectedLearner['relationship'] ?? 'N/A') ?></li>
                            <li><strong>Phone:</strong> <?= htmlspecialchars($selectedLearner['parent_phone'] ?? 'N/A') ?></li>
                            <li><strong>Email:</strong> <?= htmlspecialchars($selectedLearner['parent_email'] ?? 'N/A') ?></li>
                            <li><strong>Address:</strong> <?= htmlspecialchars($selectedLearner['parent_address'] ?? 'N/A') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Performance -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line me-2"></i>Academic Performance
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Average Mark</h6>
                            <h3 class="mb-0"><?= $averageMark ?>%</h3>
                            <small>Across all subjects</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Subjects</h6>
                            <h3 class="mb-0"><?= count($learnerMarks) ?></h3>
                            <small>Grades recorded</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white p-3 shadow-sm border-0">
                            <h6 class="text-uppercase small fw-bold">Attendance Rate</h6>
                            <h3 class="mb-0"><?= $attendanceStats['total'] > 0 ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1) : 0 ?>%</h3>
                            <small>Present rate</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="fw-bold mb-3">Recent Marks</h6>
                    <?php if (empty($learnerMarks)): ?>
                        <p class="text-muted">No marks recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Term</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice(array_reverse($learnerMarks), 0, 5) as $mark): ?>
                                        <?php
                                        $grade = 'F';
                                        if ($mark['marks'] >= 80) $grade = 'A';
                                        elseif ($mark['marks'] >= 70) $grade = 'B';
                                        elseif ($mark['marks'] >= 60) $grade = 'C';
                                        elseif ($mark['marks'] >= 50) $grade = 'D';
                                        elseif ($mark['marks'] >= 40) $grade = 'E';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mark['subject']) ?></td>
                                            <td><?= htmlspecialchars($mark['term'] ?? 'N/A') ?></td>
                                            <td><strong><?= $mark['marks'] ?>%</strong></td>
                                            <td><span class="badge bg-secondary"><?= $grade ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-calendar-check me-2"></i>Attendance History
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-success"><?= $attendanceStats['present'] ?></h4>
                            <small class="text-muted">Present</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-danger"><?= $attendanceStats['absent'] ?></h4>
                            <small class="text-muted">Absent</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning"><?= $attendanceStats['late'] ?></h4>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4><?= $attendanceStats['total'] ?></h4>
                            <small class="text-muted">Total Records</small>
                        </div>
                    </div>
                </div>

                <?php if (empty($learnerAttendance)): ?>
                    <p class="text-muted">No attendance records found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($learnerAttendance, 0, 10) as $record): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                        <td>
                                            <span class="badge <?= $record['status'] === 'present' ? 'bg-success' : ($record['status'] === 'absent' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= ucfirst($record['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($record['remarks'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
