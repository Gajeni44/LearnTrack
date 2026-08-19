<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a Principal
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: dashboard.php");
    exit();
}

$attendance = getData('attendance');
$learners = getData('learners');
$users = getData('users');

// Get the school for the current principal
$userSchoolId = $_SESSION['user']['school_id'] ?? null;

// Filter attendance to only show learners from this school
$filteredAttendance = [];
if ($userSchoolId) {
    foreach ($attendance as $record) {
        // Check if the learner belongs to this school
        $learnerBelongsToSchool = false;
        foreach ($learners as $learner) {
            if ($learner['id'] == $record['learner_id']) {
                // For now, assume all learners belong to the principal's school
                // In a real system, you'd check school_id in learner record
                $learnerBelongsToSchool = true;
                break;
            }
        }
        if ($learnerBelongsToSchool) {
            $filteredAttendance[] = $record;
        }
    }
} else {
    $filteredAttendance = $attendance;
}

// Get unique grades for filtering
$grades = [];
foreach ($learners as $learner) {
    if (!in_array($learner['class'], $grades)) {
        $grades[] = $learner['class'];
    }
}
sort($grades);

// Get unique dates for filtering
$dates = [];
foreach ($filteredAttendance as $record) {
    if (!in_array($record['date'], $dates)) {
        $dates[] = $record['date'];
    }
}
rsort($dates);

// Apply filters
$filterLearner = $_GET['learner_id'] ?? '';
$filterGrade = $_GET['grade'] ?? '';
$filterDate = $_GET['date'] ?? '';

$filteredResults = $filteredAttendance;
if ($filterLearner) {
    $filteredResults = array_filter($filteredResults, function($record) use ($filterLearner) {
        return $record['learner_id'] == $filterLearner;
    });
}
if ($filterGrade) {
    $filteredResults = array_filter($filteredResults, function($record) use ($filterGrade, $learners) {
        foreach ($learners as $learner) {
            if ($learner['id'] == $record['learner_id'] && $learner['class'] == $filterGrade) {
                return true;
            }
        }
        return false;
    });
}
if ($filterDate) {
    $filteredResults = array_filter($filteredResults, function($record) use ($filterDate) {
        return $record['date'] == $filterDate;
    });
}

// Calculate statistics
$totalRecords = count($filteredResults);
$presentCount = count(array_filter($filteredResults, function($r) { return $r['status'] === 'present'; }));
$absentCount = count(array_filter($filteredResults, function($r) { return $r['status'] === 'absent'; }));
$lateCount = count(array_filter($filteredResults, function($r) { return $r['status'] === 'late'; }));

$attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports | LearnTrack</title>
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
        <a href="transfer_letter.php" class="list-group-item list-group-item-action">Transfer Letter</a>
        <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action active">Attendance Reports</a>
        <a href="principal_results.php" class="list-group-item list-group-item-action">Results</a>
        <a href="principal_settings.php" class="list-group-item list-group-item-action">System Settings</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
    </div>
</div>

<nav class="navbar navbar-custom no-print">
    <div class="container-fluid">
        <span class="navbar-text ms-auto">
            Principal: <strong><?= $_SESSION['user']['name'] ?></strong> |
            <span class="badge bg-primary">Principal</span>
        </span>
    </div>
</nav>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-chart-bar me-2"></i>Attendance Reports
        </h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Records</h6>
                        <h3 class="mb-0"><?= $totalRecords ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Present</h6>
                        <h3 class="mb-0 text-success"><?= $presentCount ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Absent</h6>
                        <h3 class="mb-0 text-danger"><?= $absentCount ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Attendance Rate</h6>
                        <h3 class="mb-0 text-primary"><?= $attendanceRate ?>%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-filter me-2"></i>Filter Records
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Learner</label>
                        <select class="form-select" name="learner_id">
                            <option value="">All Learners</option>
                            <?php foreach ($learners as $learner): ?>
                                <option value="<?= $learner['id'] ?>" <?= $filterLearner == $learner['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grade/Class</label>
                        <select class="form-select" name="grade">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= htmlspecialchars($grade) ?>" <?= $filterGrade == $grade ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($grade) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <select class="form-select" name="date">
                            <option value="">All Dates</option>
                            <?php foreach ($dates as $date): ?>
                                <option value="<?= htmlspecialchars($date) ?>" <?= $filterDate == $date ? 'selected' : '' ?>>
                                    <?= date('M j, Y', strtotime($date)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                    </div>
                    <div class="col-md-12">
                        <a href="principal_attendance_reports.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Attendance Records
                </h5>
                <button class="btn btn-outline-primary btn-sm no-print" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Report
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Learner Name</th>
                                <th>Grade/Class</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filteredResults)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No attendance records found.</td></tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($filteredResults) as $record): ?>
                                    <?php
                                    $learnerName = 'Unknown';
                                    $learnerClass = 'N/A';
                                    foreach ($learners as $learner) {
                                        if ($learner['id'] == $record['learner_id']) {
                                            $learnerName = $learner['first_name'] . ' ' . $learner['last_name'];
                                            $learnerClass = $learner['class'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($learnerName) ?></strong></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($learnerClass) ?></span></td>
                                        <td>
                                            <span class="badge <?= $record['status'] === 'present' ? 'bg-success' : ($record['status'] === 'absent' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= ucfirst($record['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($record['remarks'] ?? '-') ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
