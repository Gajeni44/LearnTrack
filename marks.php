<?php
session_start();
require_once 'includes/json_helper.php';

// Helper function to get grade from score
function getGrade($score) {
    if ($score >= 80) return ['letter' => 'A', 'class' => 'bg-success'];
    if ($score >= 70) return ['letter' => 'B', 'class' => 'bg-primary'];
    if ($score >= 60) return ['letter' => 'C', 'class' => 'bg-info'];
    if ($score >= 50) return ['letter' => 'D', 'class' => 'bg-warning'];
    return ['letter' => 'F', 'class' => 'bg-danger'];
}

// Check if logged in AND if the user is a Teacher, School Admin, or System Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'school_admin', 'sys_admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if user is admin
$isAdmin = in_array($_SESSION['user']['role'], ['school_admin', 'sys_admin']);

if (!$isAdmin) {
    $teacherGrade = $_SESSION['user']['grade_assigned'] ?? '';
    
    // Check if teacher has an assigned grade
    if (empty($teacherGrade)) {
        header("Location: dashboard.php?error=no_grade_assigned");
        exit();
    }
}

$allLearners = getData('learners');
$marksData = getData('marks');
$users = getData('users');

// Define available subjects
$availableSubjects = [
    "Xitsonga HL",
    "Mathematical Literacy", 
    "Life Skills",
    "English FAL"
];

if ($isAdmin) {
    // Admin view - show all learners and marks
    $myLearners = $allLearners;
    $existingMarks = $marksData;
    
    // Get unique classes for admin filtering
    $classes = [];
    foreach ($allLearners as $learner) {
        if (!in_array($learner['class'], $classes)) {
            $classes[] = $learner['class'];
        }
    }
    sort($classes);
    
    // Filter by class if selected
    $selectedClass = $_GET['class_filter'] ?? 'all';
    if ($selectedClass !== 'all') {
        $myLearners = array_filter($myLearners, function($l) use ($selectedClass) {
            return $l['class'] === $selectedClass;
        });
    }
} else {
    // Teacher view - show only their class
    $myLearners = array_filter($allLearners, function($l) use ($teacherGrade) {
        return $l['class'] === $teacherGrade;
    });

    // Get existing marks for this teacher's class
    $myLearnerIds = array_map(function($l) { return $l['id']; }, $myLearners);
    $existingMarks = array_filter($marksData, function($mark) use ($myLearnerIds) {
        return in_array($mark['learner_id'], $myLearnerIds) && $mark['teacher_id'] == $_SESSION['user']['id'];
    });
}

// Filter by subject if selected
$selectedSubject = $_GET['subject_filter'] ?? 'all';
if ($selectedSubject !== 'all') {
    $existingMarks = array_filter($existingMarks, function($mark) use ($selectedSubject) {
        return $mark['subject'] === $selectedSubject;
    });
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_marks'])) {
        // Remove existing marks for this subject and term to prevent duplicates
        $marksData = array_filter($marksData, function($mark) {
            return !($mark['subject'] == $_POST['subject'] && 
                     $mark['term'] == $_POST['term'] && 
                     $mark['teacher_id'] == $_SESSION['user']['id']);
        });
        
        // Add new marks
        foreach ($_POST['mark'] as $learner_id => $score) {
            $marksData[] = [
                "learner_id" => $learner_id,
                "subject" => $_POST['subject'],
                "score" => $score,
                "term" => $_POST['term'],
                "date" => date("Y-m-d"),
                "teacher_id" => $_SESSION['user']['id']
            ];
        }
        saveData('marks', array_values($marksData));
        logActivity("Grades submitted by " . $_SESSION['user']['name'] . " for " . $_POST['subject']);
        
        header("Location: marks.php?success=1");
        exit();
    }
    
    if (isset($_POST['edit_mark'])) {
        foreach ($marksData as &$mark) {
            if ($mark['learner_id'] == $_POST['learner_id'] && 
                $mark['subject'] == $_POST['subject'] && 
                $mark['term'] == $_POST['term']) {
                $mark['score'] = $_POST['score'];
                break;
            }
        }
        saveData('marks', $marksData);
        logActivity("Grade updated by " . $_SESSION['user']['name']);
        
        header("Location: marks.php?updated=1");
        exit();
    }
    
    if (isset($_POST['delete_mark'])) {
        $marksData = array_filter($marksData, function($mark) {
            return !($mark['learner_id'] == $_POST['learner_id'] && 
                     $mark['subject'] == $_POST['subject'] && 
                     $mark['term'] == $_POST['term']);
        });
        saveData('marks', array_values($marksData));
        logActivity("Grade deleted by " . $_SESSION['user']['name']);
        
        header("Location: marks.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isAdmin ? 'Academic Performance | LearnTrack' : 'Grading System | LearnTrack' ?></title>
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
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action active">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
            <?php endif; ?>

            <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                <a href="manage_teachers.php" class="list-group-item list-group-item-action">Manage Teachers</a>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Attendance Overview</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
                <a href="marks.php" class="list-group-item list-group-item-action active">Academic Performance</a>
                <a href="announcements.php" class="list-group-item list-group-item-action">Announcements</a>
                <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
                <a href="system_logs.php" class="list-group-item list-group-item-action">System Logs</a>
            <?php endif; ?>

            <?php if($_SESSION['user']['role'] == 'teacher'): ?>
                <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
                <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
                <a href="marks.php" class="list-group-item list-group-item-action active">Grading System</a>
                <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-5">Logout</a>
        </div>
    </div>

    <nav class="navbar navbar-custom">
        <div class="container-fluid">
            <span class="navbar-text ms-auto">
                <?php if ($isAdmin): ?>
                    School Administrator: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                    <span class="badge bg-primary">School Admin</span>
                <?php else: ?>
                    Teacher: <strong><?= $_SESSION['user']['name'] ?></strong> | 
                    Assigned Class: <span class="badge bg-warning text-dark"><?= $teacherGrade ?></span>
                <?php endif; ?>
            </span>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">
                <?php if ($isAdmin): ?>
                    <i class="fas fa-chart-line me-2"></i>Academic Performance Overview
                <?php else: ?>
                    <i class="fas fa-graduation-cap me-2"></i>Grading System: <?= $teacherGrade ?>
                <?php endif; ?>
            </h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> Grades have been successfully submitted!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fas fa-edit me-2"></i> Grade has been successfully updated!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-trash me-2"></i> Grade has been successfully deleted!
                </div>
            <?php endif; ?>

            <?php if (!$isAdmin): ?>
            <!-- Capture New Grades Section (Teacher Only) -->
            <div class="card p-4 mb-4 shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>Capture New Grades
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#captureForm">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="collapse show" id="captureForm">
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Subject</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($availableSubjects as $subject): ?>
                                        <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Term</label>
                                <select name="term" class="form-select">
                                    <option>Term 1</option>
                                    <option>Term 2</option>
                                </select>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold">Students for <?= $teacherGrade ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Score (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($myLearners)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-muted">No students found for this class.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($myLearners as $l): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($l['first_name'] . " " . $l['last_name']) ?></strong></td>
                                                <td>
                                                    <input type="number" name="mark[<?= $l['id'] ?>]" class="form-control" min="0" max="100" required>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($myLearners)): ?>
                            <div class="card-footer bg-white text-end py-3">
                                <button type="submit" name="save_marks" class="btn btn-primary px-5">
                                    <i class="fas fa-save me-2"></i>Submit Grades
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Grades Section -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Existing Grades
                        </h5>
                        <div class="d-flex align-items-center">
                            <label class="form-label me-2 mb-0">Filter by Subject:</label>
                            <select class="form-select form-select-sm" style="width: 200px;" onchange="window.location.href='marks.php?subject_filter=' + this.value">
                                <option value="all" <?= $selectedSubject === 'all' ? 'selected' : '' ?>>All Subjects</option>
                                <?php foreach ($availableSubjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject) ?>" <?= $selectedSubject === $subject ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($existingMarks)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No grades have been submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Term</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Sort by date (newest first)
                                    usort($existingMarks, function($a, $b) {
                                        return strtotime($b['date']) - strtotime($a['date']);
                                    });
                                    
                                    foreach ($existingMarks as $mark): 
                                        $learner = array_filter($myLearners, function($l) use ($mark) {
                                            return $l['id'] == $mark['learner_id'];
                                        });
                                        $learner = reset($learner);
                                        $grade = getGrade($mark['score']);
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($learner ? ($learner['first_name'] . " " . $learner['last_name']) : 'Unknown Student') ?></strong></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($mark['subject']) ?></span></td>
                                        <td><?= htmlspecialchars($mark['term']) ?></td>
                                        <td>
                                            <span class="fw-bold"><?= $mark['score'] ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $grade['class'] ?>"><?= $grade['letter'] ?></span>
                                        </td>
                                        <td><small class="text-muted"><?= date("d M Y", strtotime($mark['date'])) ?></small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal" 
                                                        onclick="setEditData(<?= $mark['learner_id'] ?>, '<?= htmlspecialchars($mark['subject']) ?>', '<?= htmlspecialchars($mark['term']) ?>', <?= $mark['score'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="learner_id" value="<?= $mark['learner_id'] ?>">
                                                    <input type="hidden" name="subject" value="<?= htmlspecialchars($mark['subject']) ?>">
                                                    <input type="hidden" name="term" value="<?= htmlspecialchars($mark['term']) ?>">
                                                    <button type="submit" name="delete_mark" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this grade?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <!-- Admin View - Academic Performance Overview -->
            <!-- Filter Section -->
            <div class="card p-4 mb-4 shadow-sm border-0">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Class</label>
                        <select class="form-select" onchange="window.location.href='marks.php?class_filter=' + this.value + '&subject_filter=<?= $selectedSubject ?>'">
                            <option value="all" <?= $selectedClass === 'all' ? 'selected' : '' ?>>All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= htmlspecialchars($class) ?>" <?= $selectedClass === $class ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filter by Subject</label>
                        <select class="form-select" onchange="window.location.href='marks.php?class_filter=<?= $selectedClass ?>&subject_filter=' + this.value">
                            <option value="all" <?= $selectedSubject === 'all' ? 'selected' : '' ?>>All Subjects</option>
                            <?php foreach ($availableSubjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>" <?= $selectedSubject === $subject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Academic Performance Overview -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Academic Performance Records
                        </h5>
                        <div class="text-muted">
                            Showing <?= count($existingMarks) ?> records
                            <?php if ($selectedClass !== 'all'): ?> for <?= htmlspecialchars($selectedClass) ?><?php endif; ?>
                            <?php if ($selectedSubject !== 'all'): ?> - <?= htmlspecialchars($selectedSubject) ?><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($existingMarks)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <h5>No Academic Records Found</h5>
                            <p>No grades have been submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Term</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                        <th>Teacher</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Sort by date (newest first)
                                    usort($existingMarks, function($a, $b) {
                                        return strtotime($b['date']) - strtotime($a['date']);
                                    });
                                    
                                    foreach ($existingMarks as $mark): 
                                        $learner = array_filter($myLearners, function($l) use ($mark) {
                                            return $l['id'] == $mark['learner_id'];
                                        });
                                        $learner = reset($learner);
                                        
                                        // Get teacher name
                                        $teacher = array_filter($users, function($user) use ($mark) {
                                            return $user['id'] == $mark['teacher_id'];
                                        });
                                        $teacher = reset($teacher);
                                        $teacherName = $teacher ? $teacher['name'] : 'Unknown';
                                        
                                        $grade = getGrade($mark['score']);
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($learner ? ($learner['first_name'] . " " . $learner['last_name']) : 'Unknown Student') ?></strong></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($learner ? $learner['class'] : 'Unknown Class') ?></span></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($mark['subject']) ?></span></td>
                                        <td><?= htmlspecialchars($mark['term']) ?></td>
                                        <td>
                                            <span class="fw-bold"><?= $mark['score'] ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $grade['class'] ?>"><?= $grade['letter'] ?></span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($teacherName) ?></small></td>
                                        <td><small class="text-muted"><?= date("d M Y", strtotime($mark['date'])) ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Statistics -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar me-2"></i>Performance Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success"><?= count($existingMarks) ?></h4>
                                <small class="text-muted">Total Records</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary">
                                    <?php 
                                    $avgScore = 0;
                                    if (!empty($existingMarks)) {
                                        $totalScore = array_sum(array_column($existingMarks, 'score'));
                                        $avgScore = round($totalScore / count($existingMarks), 1);
                                    }
                                    echo $avgScore . '%';
                                    ?>
                                </h4>
                                <small class="text-muted">Average Score</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info"><?= count($myLearners) ?></h4>
                                <small class="text-muted">Total Students</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning">
                                    <?php 
                                    $passedCount = 0;
                                    foreach ($existingMarks as $mark) {
                                        if ($mark['score'] >= 50) $passedCount++;
                                    }
                                    echo $passedCount;
                                    ?>
                                </h4>
                                <small class="text-muted">Passed (50%+)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Grade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="learner_id" id="editLearnerId">
                        <input type="hidden" name="subject" id="editSubject">
                        <input type="hidden" name="term" id="editTerm">
                        
                        <div class="mb-3">
                            <label class="form-label">New Score (%)</label>
                            <input type="number" name="score" id="editScore" class="form-control" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_mark" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Grade
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function setEditData(learnerId, subject, term, score) {
            document.getElementById('editLearnerId').value = learnerId;
            document.getElementById('editSubject').value = subject;
            document.getElementById('editTerm').value = term;
            document.getElementById('editScore').value = score;
        }
        
        function getGrade(score) {
            if (score >= 80) return { letter: 'A', class: 'bg-success' };
            if (score >= 70) return { letter: 'B', class: 'bg-primary' };
            if (score >= 60) return { letter: 'C', class: 'bg-info' };
            if (score >= 50) return { letter: 'D', class: 'bg-warning' };
            return { letter: 'F', class: 'bg-danger' };
        }
    </script>
</body>
</html>
