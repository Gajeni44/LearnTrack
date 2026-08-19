<?php
session_start();
require_once 'includes/json_helper.php';

// If not logged in, kick them back to login page
if (!isset($_SESSION['user'])) { 
    header("Location: index.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnTrack | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h4>LearnTrack</h4>
        </div>
        <div class="list-group">
            <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>

            <?php if ($_SESSION['user']['role'] == 'school_admin'): ?>
                <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role'] == 'sys_admin'): ?>
                <a href="register_schools.php" class="list-group-item list-group-item-action">Register Schools</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
                <a href="manage_roles.php" class="list-group-item list-group-item-action">Manage Roles</a>
                <a href="backup_restore.php" class="list-group-item list-group-item-action">Backup & Restore</a>
                <a href="security.php" class="list-group-item list-group-item-action">Security</a>
                <a href="contact_details.php" class="list-group-item list-group-item-action">Contact Details</a>
                <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role'] == 'teacher'): ?>
                <a href="teacher_view_classes.php" class="list-group-item list-group-item-action">View Classes</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="teacher_view_profiles.php" class="list-group-item list-group-item-action">View Learner Profiles</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
                <a href="marks.php" class="list-group-item list-group-item-action">Capture Marks</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role'] == 'principal'): ?>
                <a href="transfer_letter.php" class="list-group-item list-group-item-action">Transfer Letter</a>
                <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action">Attendance Reports</a>
                <a href="principal_results.php" class="list-group-item list-group-item-action">Results</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="principal_settings.php" class="list-group-item list-group-item-action">System Settings</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                Welcome, <strong><?php echo $_SESSION['user']['name']; ?></strong> 
                <span class="badge bg-primary ms-2"><?php echo strtoupper($_SESSION['user']['role']); ?></span>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card p-5 shadow-sm border-0">
                        <h2 class="fw-bold">Welcome to the LearnTrack Portal</h2>
                        <p class="text-muted">Use the navigation menu on the left to manage school operations efficiently.</p>
                        <hr>
                        
                        <div class="row mt-4">
                            <?php 
                            $allLearners = getData('learners');
                            $allUsers = getData('users');
                            $allMarks = getData('marks');
                            $weeklyRegisters = getData('weekly_registers');
                            
                            if ($_SESSION['user']['role'] === 'teacher') {
                                $teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';
                                $myLearners = array_filter($allLearners, function($l) use ($teacherGrade) {
                                    return $l['class'] === $teacherGrade;
                                });
                                $learnerCount = count($myLearners);
                                $learnerText = "Your Students";
                            } elseif ($_SESSION['user']['role'] === 'school_admin') {
                                // School Admin Dashboard Statistics
                                $teachers = array_filter($allUsers, function($user) {
                                    return $user['role'] === 'teacher';
                                });
                                $activeRegisters = array_filter($weeklyRegisters, function($register) {
                                    return !$register['archived'];
                                });
                                $avgScore = 0;
                                if (!empty($allMarks)) {
                                    $totalScore = array_sum(array_column($allMarks, 'score'));
                                    $avgScore = round($totalScore / count($allMarks), 1);
                                }
                                ?>
                                
                                <!-- School Admin Statistics Cards -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Total Teachers</h6>
                                        <h3 class="mb-0"><?php echo count($teachers); ?></h3>
                                        <small>Managing classes</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Total Students</h6>
                                        <h3 class="mb-0"><?php echo count($allLearners); ?></h3>
                                        <small>Enrolled in school</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Active Registers</h6>
                                        <h3 class="mb-0"><?php echo count($activeRegisters); ?></h3>
                                        <small>Weekly attendance</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Average Score</h6>
                                        <h3 class="mb-0"><?php echo $avgScore; ?>%</h3>
                                        <small>Across all subjects</small>
                                    </div>
                                </div>
                                
                                <!-- Additional School Admin Info -->
                                <div class="col-12 mt-4">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0 fw-bold">
                                                <i class="fas fa-chart-bar me-2"></i>School Performance Overview
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-muted">Recent Activity</h6>
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-users text-primary me-2"></i><?php echo count($teachers); ?> Teachers active</li>
                                                        <li><i class="fas fa-graduation-cap text-success me-2"></i><?php echo count($allLearners); ?> Students enrolled</li>
                                                        <li><i class="fas fa-chart-line text-info me-2"></i><?php echo count($allMarks); ?> Grades recorded</li>
                                                    </ul>
                                                </div>
                            <?php 
                            } elseif ($_SESSION['user']['role'] === 'sys_admin') {
                                // System Admin Platform Statistics
                                $schools = getData('schools');
                                $systemLogs = getData('system_logs');
                                $contactDetails = getData('contact_details');
                                $roles = getData('roles');
                                
                                $teachers = array_filter($allUsers, function($user) {
                                    return $user['role'] === 'teacher';
                                });
                                $schoolAdmins = array_filter($allUsers, function($user) {
                                    return $user['role'] === 'school_admin';
                                });
                                $activeRegisters = array_filter($weeklyRegisters, function($register) {
                                    return !$register['archived'];
                                });
                                $avgScore = 0;
                                if (!empty($allMarks)) {
                                    $totalScore = array_sum(array_column($allMarks, 'score'));
                                    $avgScore = round($totalScore / count($allMarks), 1);
                                }
                                
                                // Calculate system status
                                $systemStatus = 'healthy';
                                $systemStatusColor = 'success';
                                $recentLogs = array_slice(array_reverse($systemLogs), 0, 10);
                                $errorCount = count(array_filter($recentLogs, function($log) {
                                    return stripos($log['action'], 'error') !== false || stripos($log['action'], 'failed') !== false;
                                }));
                                if ($errorCount > 5) {
                                    $systemStatus = 'warning';
                                    $systemStatusColor = 'warning';
                                }
                                ?>
                                
                                <!-- System Admin Platform Statistics Cards -->
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-primary text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Active Schools</h6>
                                        <h3 class="mb-0"><?php echo count($schools); ?></h3>
                                        <small>Registered</small>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-success text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Total Users</h6>
                                        <h3 class="mb-0"><?php echo count($allUsers); ?></h3>
                                        <small>All roles</small>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-info text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Teachers</h6>
                                        <h3 class="mb-0"><?php echo count($teachers); ?></h3>
                                        <small>Active</small>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-warning text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Students</h6>
                                        <h3 class="mb-0"><?php echo count($allLearners); ?></h3>
                                        <small>Enrolled</small>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-secondary text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">System Status</h6>
                                        <h3 class="mb-0 text-uppercase"><?php echo $systemStatus; ?></h3>
                                        <small class="text-<?php echo $systemStatusColor; ?>">● Operational</small>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <div class="card bg-danger text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Alerts</h6>
                                        <h3 class="mb-0"><?php echo $errorCount; ?></h3>
                                        <small>Recent issues</small>
                                    </div>
                                </div>
                                
                                <!-- Platform Overview -->
                                <div class="col-12 mt-4">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0 fw-bold">
                                                <i class="fas fa-globe me-2"></i>Platform Overview
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h6 class="text-muted">Platform Statistics</h6>
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-school text-primary me-2"></i><?php echo count($schools); ?> Schools registered</li>
                                                        <li><i class="fas fa-users text-success me-2"></i><?php echo count($allUsers); ?> Total users</li>
                                                        <li><i class="fas fa-chalkboard-teacher text-info me-2"></i><?php echo count($teachers); ?> Teachers</li>
                                                        <li><i class="fas fa-user-shield text-warning me-2"></i><?php echo count($schoolAdmins); ?> School Admins</li>
                                                        <li><i class="fas fa-graduation-cap text-danger me-2"></i><?php echo count($allLearners); ?> Students enrolled</li>
                                                        <li><i class="fas fa-chart-line text-secondary me-2"></i><?php echo count($allMarks); ?> Grades recorded</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted">System Health</h6>
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-server text-primary me-2"></i>System Status: <span class="badge bg-<?php echo $systemStatusColor; ?>"><?php echo ucfirst($systemStatus); ?></span></li>
                                                        <li><i class="fas fa-database text-success me-2"></i>Data Files: 9 active</li>
                                                        <li><i class="fas fa-history text-info me-2"></i>System Logs: <?php echo count($systemLogs); ?> entries</li>
                                                        <li><i class="fas fa-user-cog text-warning me-2"></i>Roles Configured: <?php echo count($roles); ?></li>
                                                        <li><i class="fas fa-calendar text-danger me-2"></i>Active Registers: <?php echo count($activeRegisters); ?></li>
                                                        <li><i class="fas fa-chart-bar text-secondary me-2"></i>Avg Score: <?php echo $avgScore; ?>%</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted">Contact Information</h6>
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-envelope text-primary me-2"></i><?php echo htmlspecialchars($contactDetails['support_email'] ?? 'N/A'); ?></li>
                                                        <li><i class="fas fa-phone text-success me-2"></i><?php echo htmlspecialchars($contactDetails['support_phone'] ?? 'N/A'); ?></li>
                                                        <li><i class="fas fa-building text-info me-2"></i><?php echo htmlspecialchars(substr($contactDetails['office_address'] ?? 'N/A', 0, 30)) . '...'; ?></li>
                                                        <li><i class="fas fa-clock text-warning me-2"></i><?php echo htmlspecialchars($contactDetails['business_hours'] ?? 'N/A'); ?></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted">Quick Actions</h6>
                                                    <div class="d-grid gap-2">
                                                        <a href="register_schools.php" class="btn btn-outline-primary btn-sm mb-2">
                                                            <i class="fas fa-school me-1"></i>Register School
                                                        </a>
                                                        <a href="announcements.php" class="btn btn-outline-success btn-sm mb-2">
                                                            <i class="fas fa-bullhorn me-1"></i>Send Announcement
                                                        </a>
                                                        <a href="manage_users.php" class="btn btn-outline-info btn-sm mb-2">
                                                            <i class="fas fa-user-cog me-1"></i>Manage Users
                                                        </a>
                                                        <a href="backup_restore.php" class="btn btn-outline-warning btn-sm mb-2">
                                                            <i class="fas fa-database me-1"></i>Create Backup
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            } elseif ($_SESSION['user']['role'] === 'principal') {
                                // Principal Dashboard Statistics
                                $teachers = array_filter($allUsers, function($user) {
                                    return $user['role'] === 'teacher';
                                });
                                $schoolAdmins = array_filter($allUsers, function($user) {
                                    return $user['role'] === 'school_admin';
                                });
                                $activeRegisters = array_filter($weeklyRegisters, function($register) {
                                    return !$register['archived'];
                                });
                                $avgScore = 0;
                                if (!empty($allMarks)) {
                                    $totalScore = array_sum(array_column($allMarks, 'marks'));
                                    $avgScore = round($totalScore / count($allMarks), 1);
                                }

                                // Calculate attendance rate
                                $attendance = getData('attendance');
                                $totalAttendance = count($attendance);
                                $presentCount = count(array_filter($attendance, function($r) { return $r['status'] === 'present'; }));
                                $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;
                                ?>

                                <!-- Principal Statistics Cards -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Total Teachers</h6>
                                        <h3 class="mb-0"><?php echo count($teachers); ?></h3>
                                        <small>Staff members</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Total Students</h6>
                                        <h3 class="mb-0"><?php echo count($allLearners); ?></h3>
                                        <small>Enrolled in school</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Attendance Rate</h6>
                                        <h3 class="mb-0"><?php echo $attendanceRate; ?>%</h3>
                                        <small>Overall attendance</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning text-white p-3 shadow-sm border-0">
                                        <h6 class="text-uppercase small fw-bold">Average Score</h6>
                                        <h3 class="mb-0"><?php echo $avgScore; ?>%</h3>
                                        <small>Academic performance</small>
                                    </div>
                                </div>

                                <!-- Principal Overview -->
                                <div class="col-12 mt-4">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0 fw-bold">
                                                <i class="fas fa-chart-bar me-2"></i>School Leadership Overview
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-muted">School Statistics</h6>
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-chalkboard-teacher text-primary me-2"></i><?php echo count($teachers); ?> Teachers active</li>
                                                        <li><i class="fas fa-user-shield text-success me-2"></i><?php echo count($schoolAdmins); ?> School Admin(s)</li>
                                                        <li><i class="fas fa-graduation-cap text-info me-2"></i><?php echo count($allLearners); ?> Students enrolled</li>
                                                        <li><i class="fas fa-chart-line text-warning me-2"></i><?php echo count($allMarks); ?> Grades recorded</li>
                                                        <li><i class="fas fa-calendar-check text-danger me-2"></i><?php echo count($activeRegisters); ?> Active registers</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted">Quick Actions</h6>
                                                    <div class="d-grid gap-2">
                                                        <a href="transfer_letter.php" class="btn btn-outline-primary btn-sm mb-2">
                                                            <i class="fas fa-file-alt me-1"></i>Generate Transfer Letter
                                                        </a>
                                                        <a href="principal_attendance_reports.php" class="btn btn-outline-success btn-sm mb-2">
                                                            <i class="fas fa-chart-bar me-1"></i>View Attendance Reports
                                                        </a>
                                                        <a href="principal_results.php" class="btn btn-outline-info btn-sm mb-2">
                                                            <i class="fas fa-chart-line me-1"></i>View Academic Results
                                                        </a>
                                                        <a href="principal_settings.php" class="btn btn-outline-warning btn-sm mb-2">
                                                            <i class="fas fa-cog me-1"></i>System Settings
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            } else {
                                $learnerCount = count($allLearners);
                                $learnerText = "Active learners";
                            }
                            ?>

                            <?php if ($_SESSION['user']['role'] == 'sys_admin'): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-primary text-white p-3 shadow-sm border-0">
                                    <h6 class="text-uppercase small fw-bold">User Statistics</h6>
                                    <h3 class="mb-0"><?php echo count(getData('users')); ?> Users</h3>
                                    <small>Registered in system</small>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['user']['role'] == 'teacher'): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-success text-white p-3 shadow-sm border-0">
                                    <h6 class="text-uppercase small fw-bold">Enrollment</h6>
                                    <h3 class="mb-0"><?php echo $learnerCount; ?> Students</h3>
                                    <small><?php echo $learnerText; ?></small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>