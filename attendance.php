<?php
session_start();
require_once 'includes/json_helper.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'sys_admin', 'school_admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if user is admin - show different interface
$isAdmin = in_array($_SESSION['user']['role'], ['sys_admin', 'school_admin']);

if ($isAdmin) {
    // Admin view - show all teachers and their classes
    $allUsers = getData('users');
    $allLearners = getData('learners');
    $weeklyRegisters = getData('weekly_registers');
    
    // Get all teachers with their assigned grades
    $teachers = array_filter($allUsers, function($user) {
        return $user['role'] === 'teacher' && !empty($user['grade_assigned']);
    });
    
    // Calculate attendance statistics for each teacher/class
    $teacherStats = [];
    foreach ($teachers as $teacher) {
        $class = $teacher['grade_assigned'];
        $classLearners = array_filter($allLearners, function($learner) use ($class) {
            return $learner['class'] === $class;
        });
        
        // Get current week register for this class
        $currentWeek = getCurrentWeek();
        $classWeekRegisters = getWeekRegisters($class);
        $currentRegister = getCurrentWeekRegister($classWeekRegisters, $currentWeek);
        
        // Calculate average attendance for current week
        $avgAttendance = 0;
        if ($currentRegister) {
            $stats = getWeeklyStats($currentRegister, $classLearners);
            $totalPossible = $stats['Days'] * count($classLearners);
            if ($totalPossible > 0) {
                $avgAttendance = round(($stats['Present'] / $totalPossible) * 100, 1);
            }
        }
        
        $teacherStats[] = [
            'teacher' => $teacher,
            'class' => $class,
            'learner_count' => count($classLearners),
            'current_register' => $currentRegister,
            'avg_attendance' => $avgAttendance,
            'week_stats' => $currentRegister ? getWeeklyStats($currentRegister, $classLearners) : null
        ];
    }
    
} else {
    // Teacher view - existing logic
    $allLearners = getData('learners');
    $teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';

    // Check if teacher has an assigned grade
    if (empty($teacherGrade)) {
        header("Location: dashboard.php?error=no_grade_assigned");
        exit();
    }

    // FILTER: If user is a teacher, only show their class. Admins see all.
    $learners = array_filter($allLearners, function($l) use ($teacherGrade) {
        return $l['class'] === $teacherGrade;
    });

    // Get current week info
    $currentWeek = getCurrentWeek();
    $weekRegisters = getWeekRegisters($teacherGrade);
    $currentRegister = getCurrentWeekRegister($weekRegisters, $currentWeek);
}

$message = "";
$alertType = "";

// Handle week actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['new_week'])) {
        // Create new week register
        $newWeek = createNewWeekRegister($teacherGrade);
        if ($newWeek) {
            $message = "New weekly register created successfully!";
            $alertType = "success";
            logActivity("New week register created by " . $_SESSION['user']['name'] . " for class: " . $teacherGrade);
        }
        header("Location: attendance.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
    
    if (isset($_POST['save_attendance'])) {
        // Save daily attendance to current week register
        $dayOfWeek = date('l'); // Monday, Tuesday, etc.
        $date = date("Y-m-d");
        
        if ($currentRegister) {
            // Update existing register
            updateWeekRegister($currentRegister['id'], $dayOfWeek, $_POST['status'], $_SESSION['user']['name']);
            $message = "Attendance saved for " . $dayOfWeek . "!";
            $alertType = "success";
            logActivity("Attendance marked by " . $_SESSION['user']['name'] . " for " . $teacherGrade . " on " . $dayOfWeek);
        } else {
            // Create new register for this week if it doesn't exist
            $newRegister = createNewWeekRegister($teacherGrade);
            if ($newRegister) {
                updateWeekRegister($newRegister['id'], $dayOfWeek, $_POST['status'], $_SESSION['user']['name']);
                $message = "New weekly register created and attendance saved!";
                $alertType = "success";
                logActivity("New week register created and attendance marked by " . $_SESSION['user']['name'] . " for " . $teacherGrade);
            }
        }
        header("Location: attendance.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
    
    if (isset($_POST['archive_week'])) {
        // Archive current week and create new one
        if ($currentRegister) {
            archiveWeekRegister($currentRegister['id']);
            $newWeek = createNewWeekRegister($teacherGrade);
            $message = "Week archived and new register created!";
            $alertType = "info";
            logActivity("Week archived by " . $_SESSION['user']['name'] . " for class: " . $teacherGrade);
        }
        header("Location: attendance.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
}

// Handle URL messages
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $alertType = $_GET['type'] ?? 'info';
}

// Helper functions
function getCurrentWeek() {
    return date('Y-W'); // Year-Week number (e.g., 2024-15)
}

function getCurrentWeekRegister($weekRegisters, $currentWeek) {
    foreach ($weekRegisters as $register) {
        if ($register['week_number'] === $currentWeek && !$register['archived']) {
            return $register;
        }
    }
    return null;
}

function getWeekRegisters($grade) {
    $weekData = getData('weekly_registers');
    return array_filter($weekData, function($register) use ($grade) {
        return $register['class'] === $grade;
    });
}

function createNewWeekRegister($grade) {
    $weekData = getData('weekly_registers');
    $currentWeek = getCurrentWeek();
    
    // Check if week already exists
    foreach ($weekData as $register) {
        if ($register['week_number'] === $currentWeek && $register['class'] === $grade) {
            return null; // Week already exists
        }
    }
    
    $newRegister = [
        'id' => time(),
        'week_number' => $currentWeek,
        'class' => $grade,
        'created_date' => date('Y-m-d'),
        'created_by' => $_SESSION['user']['name'],
        'archived' => false,
        'attendance' => [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ]
    ];
    
    $weekData[] = $newRegister;
    saveData('weekly_registers', $weekData);
    return $newRegister;
}

function updateWeekRegister($registerId, $dayOfWeek, $attendanceData, $markedBy) {
    $weekData = getData('weekly_registers');
    
    foreach ($weekData as &$register) {
        if ($register['id'] == $registerId) {
            $register['attendance'][$dayOfWeek] = [];
            foreach ($attendanceData as $learnerId => $status) {
                $register['attendance'][$dayOfWeek][] = [
                    'learner_id' => $learnerId,
                    'status' => $status,
                    'marked_by' => $markedBy,
                    'date' => date('Y-m-d')
                ];
            }
            $register['last_updated'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveData('weekly_registers', $weekData);
}

function archiveWeekRegister($registerId) {
    $weekData = getData('weekly_registers');
    
    foreach ($weekData as &$register) {
        if ($register['id'] == $registerId) {
            $register['archived'] = true;
            $register['archived_date'] = date('Y-m-d H:i:s');
            $register['archived_by'] = $_SESSION['user']['name'];
            break;
        }
    }
    
    saveData('weekly_registers', $weekData);
}

// Refresh current register after any operations
$weekRegisters = getWeekRegisters($teacherGrade);
$currentRegister = getCurrentWeekRegister($weekRegisters, $currentWeek);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Attendance Register | LearnTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h4>LearnTrack</h4></div>
        <div class="list-group">
            <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>

            <?php if($_SESSION['user']['role'] == 'teacher'): ?>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action active">Mark Attendance</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Grading System</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
            <?php endif; ?>

            <?php if($_SESSION['user']['role'] == 'sys_admin' || $_SESSION['user']['role'] == 'school_admin'): ?>
                <a href="attendance.php" class="list-group-item list-group-item-action active">Attendance Overview</a>
                <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                    <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
                    <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                <?php if ($isAdmin): ?>
                    Administrator: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                    <span class="badge bg-danger">Admin View</span>
                <?php else: ?>
                    Teacher: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                    Assigned Class: <span class="badge bg-warning text-dark"><?= $teacherGrade ?></span>
                <?php endif; ?>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <?php if ($isAdmin): ?>
                <!-- Admin View -->
                <h2 class="mb-4">
                    <i class="fas fa-users-cog me-2"></i>Attendance Overview - All Classes
                </h2>

                <?php if($message): ?>
                    <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i><?= $message ?>
                    </div>
                <?php endif; ?>

                <!-- Admin Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?= count($teachers) ?></h3>
                                <small>Total Teachers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?= array_sum(array_column($teacherStats, 'learner_count')) ?></h3>
                                <small>Total Students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($teacherStats, fn($s) => $s['current_register'])) ?></h3>
                                <small>Active Registers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-warning text-white">
                            <div class="card-body text-center">
                                <h3>
                                    <?php 
                                    $avgAttendance = array_filter($teacherStats, fn($s) => $s['avg_attendance'] > 0);
                                    if (!empty($avgAttendance)) {
                                        echo round(array_sum(array_column($avgAttendance, 'avg_attendance')) / count($avgAttendance), 1) . '%';
                                    } else {
                                        echo '0%';
                                    }
                                    ?>
                                </h3>
                                <small>Avg Attendance</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teacher Classes Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Classes & Attendance
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Teacher Name</th>
                                        <th>Class</th>
                                        <th class="text-center">Students</th>
                                        <th class="text-center">Week Status</th>
                                        <th class="text-center">Avg Attendance</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teacherStats)): ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">No teachers found with assigned classes.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($teacherStats as $stat): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($stat['teacher']['name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($stat['teacher']['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($stat['class']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $stat['learner_count'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($stat['current_register']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <br><small class="text-muted">Week <?= substr($stat['current_register']['week_number'], -2) ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Register</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($stat['avg_attendance'] > 0): ?>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?= $stat['avg_attendance'] >= 80 ? 'bg-success' : ($stat['avg_attendance'] >= 60 ? 'bg-warning' : 'bg-danger') ?>" 
                                                             style="width: <?= $stat['avg_attendance'] ?>%">
                                                            <?= $stat['avg_attendance'] ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No Data</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($stat['current_register']): ?>
                                                        <a href="view_history.php?view=week&week=<?= $stat['current_register']['id'] ?>" 
                                                           class="btn btn-outline-primary" title="View Week Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-info" 
                                                            onclick="alert('Class details feature coming soon!')"
                                                            title="View Class Details">
                                                        <i class="fas fa-users"></i>
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

            <?php else: ?>
                <!-- Teacher View (Existing) -->
                <?php if($message): ?>
                    <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i><?= $message ?>
                    </div>
                <?php endif; ?>

                <!-- Week Info Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Week <?= date('W', strtotime($currentRegister['created_date'] ?? 'now')) ?> - 
                                    <?= date('F j, Y', strtotime('monday this week')) ?> to <?= date('F j, Y', strtotime('friday this week')) ?>
                                </h5>
                                <?php if ($currentRegister): ?>
                                    <small class="text-muted">Created: <?= $currentRegister['created_date'] ?> by <?= $currentRegister['created_by'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="btn-group">
                                <?php if (!$currentRegister): ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="new_week" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Create New Week
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="archive_week" class="btn btn-warning" 
                                                onclick="return confirm('Archive this week and create a new register?')">
                                            <i class="fas fa-archive me-2"></i>Archive Week
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($currentRegister): ?>
                    <!-- Weekly Attendance Table -->
                    <form method="POST">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>Students for <?= $teacherGrade ?>
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
                                                <th class="text-center">Today</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($learners)): ?>
                                                <tr><td colspan="7" class="text-center py-5 text-muted">No students found for this class.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($learners as $l): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($l['first_name'] . " " . $l['last_name']) ?></strong></td>
                                                    
                                                    <!-- Monday -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $monStatus = getAttendanceStatus($currentRegister, 'Monday', $l['id']);
                                                        if ($monStatus): ?>
                                                            <span class="badge bg-<?= $monStatus === 'Present' ? 'success' : 'danger' ?>"><?= $monStatus ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Tuesday -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $tueStatus = getAttendanceStatus($currentRegister, 'Tuesday', $l['id']);
                                                        if ($tueStatus): ?>
                                                            <span class="badge bg-<?= $tueStatus === 'Present' ? 'success' : 'danger' ?>"><?= $tueStatus ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Wednesday -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $wedStatus = getAttendanceStatus($currentRegister, 'Wednesday', $l['id']);
                                                        if ($wedStatus): ?>
                                                            <span class="badge bg-<?= $wedStatus === 'Present' ? 'success' : 'danger' ?>"><?= $wedStatus ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Thursday -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $thuStatus = getAttendanceStatus($currentRegister, 'Thursday', $l['id']);
                                                        if ($thuStatus): ?>
                                                            <span class="badge bg-<?= $thuStatus === 'Present' ? 'success' : 'danger' ?>"><?= $thuStatus ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Friday -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $friStatus = getAttendanceStatus($currentRegister, 'Friday', $l['id']);
                                                        if ($friStatus): ?>
                                                            <span class="badge bg-<?= $friStatus === 'Present' ? 'success' : 'danger' ?>"><?= $friStatus ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Today's Attendance -->
                                                    <td class="text-center">
                                                        <?php 
                                                        $today = date('l');
                                                        $todayStatus = getAttendanceStatus($currentRegister, $today, $l['id']);
                                                        if ($todayStatus): ?>
                                                            <span class="badge bg-<?= $todayStatus === 'Present' ? 'success' : 'danger' ?>"><?= $todayStatus ?></span>
                                                        <?php else: ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <input type="radio" class="btn-check" name="status[<?= $l['id'] ?>]" id="p<?= $l['id'] ?>" value="Present" checked>
                                                                <label class="btn btn-outline-success" for="p<?= $l['id'] ?>"><i class="fas fa-check"></i></label>
                                                                <input type="radio" class="btn-check" name="status[<?= $l['id'] ?>]" id="a<?= $l['id'] ?>" value="Absent">
                                                                <label class="btn btn-outline-danger" for="a<?= $l['id'] ?>"><i class="fas fa-times"></i></label>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php if (!empty($learners) && !isWeekend()): ?>
                            <div class="card-footer bg-white text-end py-3">
                                <button type="submit" name="save_attendance" class="btn btn-primary px-5">
                                    <i class="fas fa-save me-2"></i>Save Today's Register
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- No current week register -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Active Weekly Register</h5>
                            <p class="text-muted">Create a new weekly register to start marking attendance.</p>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="new_week" class="btn btn-primary px-4">
                                    <i class="fas fa-plus me-2"></i>Create New Week Register
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Week Statistics -->
                <?php if ($currentRegister): ?>
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
                                    <h4 class="text-success"><?= getWeeklyStats($currentRegister, $learners)['Present'] ?></h4>
                                    <small class="text-muted">Total Present</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-danger"><?= getWeeklyStats($currentRegister, $learners)['Absent'] ?></h4>
                                    <small class="text-muted">Total Absent</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info"><?= getWeeklyStats($currentRegister, $learners)['Days'] ?></h4>
                                    <small class="text-muted">Days Marked</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary"><?= count($learners) ?></h4>
                                    <small class="text-muted">Total Students</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>

<?php
// Helper functions for the view
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

function getWeeklyStats($register, $learners = null) {
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
    
    // If $learners is provided, it's the old format call - return specific stat
    if (is_string($learners)) {
        return $stats[$learners] ?? 0;
    }
    
    // Otherwise, return the full stats array
    return $stats;
}

function isWeekend() {
    $day = date('w');
    return $day == 0 || $day == 6; // Sunday or Saturday
}
?>