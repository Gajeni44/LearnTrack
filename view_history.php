<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is an Admin OR a Teacher
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['sys_admin', 'school_admin', 'teacher'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize data from JSON
$weeklyRegisters = getData('weekly_registers');
$learners = getData('learners');

// Filter learners for teachers - get only their assigned grade students
if ($_SESSION['user']['role'] === 'teacher') {
    $teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';
    $myLearners = array_filter($learners, function($l) use ($teacherGrade) {
        return $l['class'] === $teacherGrade;
    });
    
    // Filter weekly registers to show only assigned grade
    $weeklyRegisters = array_filter($weeklyRegisters, function($register) use ($teacherGrade) {
        return $register['class'] === $teacherGrade;
    });
} else {
    $myLearners = $learners;
}

// Handle URL parameters
$view = $_GET['view'] ?? 'months';
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedWeek = $_GET['week'] ?? '';

// Helper functions
function getLearnerName($id, $learners) {
    foreach ($learners as $l) {
        if ($l['id'] == $id) return $l['first_name'] . " " . $l['last_name'];
    }
    return "Unknown Student";
}

function getAvailableMonths($weeklyRegisters) {
    $months = [];
    foreach ($weeklyRegisters as $register) {
        if ($register['archived']) {
            $date = new DateTime($register['created_date']);
            $monthKey = $date->format('Y-m');
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = $date->format('F Y');
            }
        }
    }
    krsort($months); // Most recent first
    return $months;
}

function getWeeksInMonth($weeklyRegisters, $selectedMonth) {
    $weeks = [];
    foreach ($weeklyRegisters as $register) {
        if ($register['archived']) {
            $date = new DateTime($register['created_date']);
            $monthKey = $date->format('Y-m');
            if ($monthKey === $selectedMonth) {
                $weeks[] = $register;
            }
        }
    }
    // Sort by week number
    usort($weeks, function($a, $b) {
        return $b['week_number'] <=> $a['week_number'];
    });
    return $weeks;
}

function getWeekDetails($weeklyRegisters, $weekId) {
    foreach ($weeklyRegisters as $register) {
        if ($register['id'] == $weekId) {
            return $register;
        }
    }
    return null;
}

function getAttendanceStatus($register, $day, $learnerId) {
    if (!isset($register['attendance'][$day])) {
        return null;
    }
    
    foreach ($register['attendance'][$day] as $attendance) {
        if ($attendance['learner_id'] == $learnerId) {
            return $attendance['status'];
        }
    }
    return null;
}

function getWeeklyStats($register, $learners) {
    $stats = ['Present' => 0, 'Absent' => 0, 'Days' => 0];
    
    foreach ($register['attendance'] as $day => $attendances) {
        if (!empty($attendances)) {
            $stats['Days']++;
            foreach ($attendances as $attendance) {
                if (isset($stats[$attendance['status']])) {
                    $stats[$attendance['status']]++;
                }
            }
        }
    }
    
    return $stats;
}

// Get data based on view
$availableMonths = getAvailableMonths($weeklyRegisters);
$weeksInMonth = [];
$weekDetails = null;

if ($view === 'weeks' && $selectedMonth) {
    $weeksInMonth = getWeeksInMonth($weeklyRegisters, $selectedMonth);
}

if ($view === 'week' && $selectedWeek) {
    $weekDetails = getWeekDetails($weeklyRegisters, $selectedWeek);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History | LearnTrack</title>
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
            <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
            <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
            <a href="view_history.php" class="list-group-item list-group-item-action active">Attendance History</a>
            <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
            <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
        <?php endif; ?>

        <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
            <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
            <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
            <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
            <a href="view_history.php" class="list-group-item list-group-item-action active">Attendance History</a>
            <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
            <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
        <?php endif; ?>

        <?php if($_SESSION['user']['role'] == 'teacher'): ?>
            <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
            <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
            <a href="marks.php" class="list-group-item list-group-item-action">Grading System</a>
            <a href="view_history.php" class="list-group-item list-group-item-action active">Attendance History</a>
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
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="view_history.php?view=months" class="text-decoration-none">Months</a>
                </li>
                <?php if ($view === 'weeks' && $selectedMonth): ?>
                    <li class="breadcrumb-item active"><?= date('F Y', strtotime($selectedMonth . '-01')) ?></li>
                <?php endif; ?>
                <?php if ($view === 'week' && $weekDetails): ?>
                    <li class="breadcrumb-item">
                        <a href="view_history.php?view=weeks&month=<?= date('Y-m', strtotime($weekDetails['created_date'])) ?>" class="text-decoration-none">
                            <?= date('F Y', strtotime($weekDetails['created_date'])) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Week <?= substr($weekDetails['week_number'], -2) ?></li>
                <?php endif; ?>
            </ol>
        </nav>

        <!-- Months View -->
        <?php if ($view === 'months'): ?>
            <h2 class="mb-4">
                <i class="fas fa-calendar-alt me-2"></i>Attendance History - Months
            </h2>

            <?php if (empty($availableMonths)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Archived Months Found</h5>
                        <p class="text-muted">Archive weekly registers to see them here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($availableMonths as $monthKey => $monthName): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0"><?= $monthName ?></h5>
                                        <small class="text-muted">Click to view weeks</small>
                                    </div>
                                </div>
                                <a href="view_history.php?view=weeks&month=<?= $monthKey ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>View Weeks
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- Weeks View -->
        <?php elseif ($view === 'weeks'): ?>
            <h2 class="mb-4">
                <i class="fas fa-calendar-week me-2"></i>Weeks in <?= date('F Y', strtotime($selectedMonth . '-01')) ?>
            </h2>

            <?php if (empty($weeksInMonth)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Weeks Found</h5>
                        <p class="text-muted">No archived weeks found for this month.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($weeksInMonth as $week): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title">Week <?= substr($week['week_number'], -2) ?></h5>
                                        <small class="text-muted">
                                            <?= date('M j', strtotime('monday this week', strtotime($week['created_date']))) ?> - 
                                            <?= date('M j, Y', strtotime('friday this week', strtotime($week['created_date']))) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-info">Archived</span>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-3">
                                        <small class="text-muted">Present</small>
                                        <div class="fw-bold text-success"><?= getWeeklyStats($week, $myLearners)['Present'] ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">Absent</small>
                                        <div class="fw-bold text-danger"><?= getWeeklyStats($week, $myLearners)['Absent'] ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">Days</small>
                                        <div class="fw-bold text-info"><?= getWeeklyStats($week, $myLearners)['Days'] ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">Students</small>
                                        <div class="fw-bold text-primary"><?= count($myLearners) ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Created: <?= $week['created_date'] ?> by <?= $week['created_by'] ?></small>
                                    <a href="view_history.php?view=week&week=<?= $week['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- Week Detail View -->
        <?php elseif ($view === 'week' && $weekDetails): ?>
            <h2 class="mb-4">
                <i class="fas fa-calendar-check me-2"></i>Week <?= substr($weekDetails['week_number'], -2) ?> Details
            </h2>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <?= date('F j, Y', strtotime('monday this week', strtotime($weekDetails['created_date']))) ?> to 
                                <?= date('F j, Y', strtotime('friday this week', strtotime($weekDetails['created_date']))) ?>
                            </h5>
                            <small class="text-muted">
                                Created: <?= $weekDetails['created_date'] ?> by <?= $weekDetails['created_by'] ?> | 
                                Archived: <?= $weekDetails['archived_date'] ?> by <?= $weekDetails['archived_by'] ?>
                            </small>
                        </div>
                        <span class="badge bg-warning text-dark">Archived</span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Students for <?= $weekDetails['class'] ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student Name</th>
                                    <th class="text-center">Mon</th>
                                    <th class="text-center">Tue</th>
                                    <th class="text-center">Wed</th>
                                    <th class="text-center">Thu</th>
                                    <th class="text-center">Fri</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myLearners)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No students found for this class.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($myLearners as $l): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($l['first_name'] . " " . $l['last_name']) ?></strong></td>
                                        
                                        <!-- Monday -->
                                        <td class="text-center">
                                            <?php 
                                            $monStatus = getAttendanceStatus($weekDetails, 'Monday', $l['id']);
                                            if ($monStatus): ?>
                                                <span class="badge bg-<?= $monStatus === 'Present' ? 'success' : 'danger' ?>"><?= $monStatus ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Tuesday -->
                                        <td class="text-center">
                                            <?php 
                                            $tueStatus = getAttendanceStatus($weekDetails, 'Tuesday', $l['id']);
                                            if ($tueStatus): ?>
                                                <span class="badge bg-<?= $tueStatus === 'Present' ? 'success' : 'danger' ?>"><?= $tueStatus ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Wednesday -->
                                        <td class="text-center">
                                            <?php 
                                            $wedStatus = getAttendanceStatus($weekDetails, 'Wednesday', $l['id']);
                                            if ($wedStatus): ?>
                                                <span class="badge bg-<?= $wedStatus === 'Present' ? 'success' : 'danger' ?>"><?= $wedStatus ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Thursday -->
                                        <td class="text-center">
                                            <?php 
                                            $thuStatus = getAttendanceStatus($weekDetails, 'Thursday', $l['id']);
                                            if ($thuStatus): ?>
                                                <span class="badge bg-<?= $thuStatus === 'Present' ? 'success' : 'danger' ?>"><?= $thuStatus ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Friday -->
                                        <td class="text-center">
                                            <?php 
                                            $friStatus = getAttendanceStatus($weekDetails, 'Friday', $l['id']);
                                            if ($friStatus): ?>
                                                <span class="badge bg-<?= $friStatus === 'Present' ? 'success' : 'danger' ?>"><?= $friStatus ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Week Statistics -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Week Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success"><?= getWeeklyStats($weekDetails, $myLearners)['Present'] ?></h4>
                                <small class="text-muted">Total Present</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-danger"><?= getWeeklyStats($weekDetails, $myLearners)['Absent'] ?></h4>
                                <small class="text-muted">Total Absent</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info"><?= getWeeklyStats($weekDetails, $myLearners)['Days'] ?></h4>
                                <small class="text-muted">Days Marked</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?= count($myLearners) ?></h4>
                                <small class="text-muted">Total Students</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>