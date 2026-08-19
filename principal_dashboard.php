<?php
session_start();
require_once 'includes/json_helper.php';

// Security check: Only Principal allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: dashboard.php");
    exit();
}

$allLearners = getData('learners');
$allUsers = getData('users');
$marksData = getData('marks');
$attendanceData = getData('attendance');
$announcements = getData('announcements');

// Get principal's assigned school (assuming principal has school_id in their profile)
$principalSchool = $_SESSION['user']['school_id'] ?? null;

// Filter data for principal's school
$schoolLearners = $principalSchool ? array_filter($allLearners, function($l) use ($principalSchool) {
    return ($l['school_id'] ?? null) === $principalSchool;
}) : $allLearners;

$schoolTeachers = $principalSchool ? array_filter($allUsers, function($u) use ($principalSchool) {
    return $u['role'] === 'teacher' && ($u['school_id'] ?? null) === $principalSchool;
}) : array_filter($allUsers, function($u) { return $u['role'] === 'teacher'; });

$schoolMarks = $principalSchool ? array_filter($marksData, function($m) use ($principalSchool) {
    // Filter marks by teacher's school
    $teacher = array_filter($allUsers, function($u) use ($m, $principalSchool) {
        return $u['id'] == $m['teacher_id'] && ($u['school_id'] ?? null) === $principalSchool;
    });
    return !empty($teacher);
}) : $marksData;

// Calculate statistics
$totalLearners = count($schoolLearners);
$totalTeachers = count($schoolTeachers);
$totalMarks = count($schoolMarks);

// Calculate average score
$avgScore = 0;
if (!empty($schoolMarks)) {
    $totalScore = array_sum(array_column($schoolMarks, 'score'));
    $avgScore = round($totalScore / count($schoolMarks), 1);
}

// Calculate pass rate
$passedCount = 0;
foreach ($schoolMarks as $mark) {
    if ($mark['score'] >= 50) $passedCount++;
}
$passRate = !empty($schoolMarks) ? round(($passedCount / count($schoolMarks)) * 100, 1) : 0;

// Get unique grades
$grades = [];
foreach ($schoolLearners as $learner) {
    if (!in_array($learner['class'], $grades)) {
        $grades[] = $learner['class'];
    }
}
sort($grades);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard | LearnTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h4>LearnTrack</h4>
        </div>
        <div class="list-group">
            <a href="principal_dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
            <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
            <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
            <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
            <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
            <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
            <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                Principal: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                <span class="badge bg-success">Principal</span>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">
                <i class="fas fa-user-tie me-2"></i>Principal Dashboard
            </h2>

            <!-- Welcome Section -->
            <div class="card p-4 mb-4 shadow-sm border-0 bg-gradient-primary">
                <div class="card-body text-white">
                    <h4 class="mb-2">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</h4>
                    <p class="mb-0">Here's an overview of your school's performance and activities.</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0 fw-bold"><?= $totalLearners ?></h5>
                                <small class="text-muted">Total Learners</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chalkboard-teacher fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0 fw-bold"><?= $totalTeachers ?></h5>
                                <small class="text-muted">Total Teachers</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0 fw-bold"><?= $avgScore ?>%</h5>
                                <small class="text-muted">Average Score</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card p-4 shadow-sm border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-graduation-cap fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0 fw-bold"><?= $passRate ?>%</h5>
                                <small class="text-muted">Pass Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Grade Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-layer-group me-2"></i>Grade Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($grades)): ?>
                                <div class="text-center py-4 text-muted">No grade data available</div>
                            <?php else: ?>
                                <?php foreach ($grades as $grade): ?>
                                <?php 
                                $gradeLearners = count(array_filter($schoolLearners, function($l) use ($grade) {
                                    return $l['class'] === $grade;
                                }));
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?= htmlspecialchars($grade) ?></span>
                                        <span><?= $gradeLearners ?> learners</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?= $totalLearners > 0 ? ($gradeLearners / $totalLearners * 100) : 0 ?>%">
                                            <?= $totalLearners > 0 ? round($gradeLearners / $totalLearners * 100, 1) : 0 ?>%
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-4 text-muted">No announcements available</div>
                            <?php else: ?>
                                <?php 
                                $recentAnnouncements = array_slice(array_reverse($announcements), 0, 5);
                                foreach ($recentAnnouncements as $announcement): 
                                ?>
                                <div class="alert alert-light border mb-2">
                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($announcement['title']) ?></h6>
                                    <small class="text-muted"><?= date('d M Y', strtotime($announcement['date'])) ?></small>
                                    <p class="mb-0 mt-1"><?= htmlspecialchars(substr($announcement['message'], 0, 100)) ?>...</p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="manage_teachers.php" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-user-plus me-2"></i>Add Teacher
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage_learners.php" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-user-graduate me-2"></i>Add Learner
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="announcements.php" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-bullhorn me-2"></i>New Announcement
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="marks.php" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Performance Overview -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-line me-2"></i>Academic Performance Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-success"><?= $totalMarks ?></h4>
                                <small class="text-muted">Total Grades Recorded</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-primary"><?= $passedCount ?></h4>
                                <small class="text-muted">Passed (50%+)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-danger"><?= $totalMarks - $passedCount ?></h4>
                                <small class="text-muted">Failed (<50%)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
