<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a Principal
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: dashboard.php");
    exit();
}

$marks = getData('marks');
$learners = getData('learners');
$users = getData('users');

// Get the school for the current principal
$userSchoolId = $_SESSION['user']['school_id'] ?? null;

// Get unique grades for filtering
$grades = [];
foreach ($learners as $learner) {
    if (!in_array($learner['class'], $grades)) {
        $grades[] = $learner['class'];
    }
}
sort($grades);

// Get unique subjects for filtering
$subjects = [];
foreach ($marks as $mark) {
    if (!in_array($mark['subject'], $subjects)) {
        $subjects[] = $mark['subject'];
    }
}
sort($subjects);

// Apply filters
$searchTerm = $_GET['search'] ?? '';
$filterGrade = $_GET['grade'] ?? '';
$filterSubject = $_GET['subject'] ?? '';

$filteredMarks = $marks;
$filteredLearners = $learners;

// Apply learner search
if ($searchTerm) {
    $filteredLearners = array_filter($learners, function($learner) use ($searchTerm) {
        $fullName = strtolower($learner['first_name'] . ' ' . $learner['last_name']);
        return strpos($fullName, strtolower($searchTerm)) !== false;
    });
    $filteredLearnerIds = array_map(function($l) { return $l['id']; }, $filteredLearners);
    $filteredMarks = array_filter($marks, function($mark) use ($filteredLearnerIds) {
        return in_array($mark['learner_id'], $filteredLearnerIds);
    });
}

// Apply grade filter
if ($filterGrade) {
    $filteredLearners = array_filter($filteredLearners, function($learner) use ($filterGrade) {
        return $learner['class'] == $filterGrade;
    });
    $filteredLearnerIds = array_map(function($l) { return $l['id']; }, $filteredLearners);
    $filteredMarks = array_filter($filteredMarks, function($mark) use ($filteredLearnerIds) {
        return in_array($mark['learner_id'], $filteredLearnerIds);
    });
}

// Apply subject filter
if ($filterSubject) {
    $filteredMarks = array_filter($filteredMarks, function($mark) use ($filterSubject) {
        return $mark['subject'] == $filterSubject;
    });
}

// Calculate statistics
$totalMarks = count($filteredMarks);
if ($totalMarks > 0) {
    $averageMark = round(array_sum(array_column($filteredMarks, 'marks')) / $totalMarks, 1);
    $highestMark = max(array_column($filteredMarks, 'marks'));
    $lowestMark = min(array_column($filteredMarks, 'marks'));
} else {
    $averageMark = 0;
    $highestMark = 0;
    $lowestMark = 0;
}

// Group marks by learner for summary
$learnerPerformance = [];
foreach ($filteredMarks as $mark) {
    $learnerId = $mark['learner_id'];
    if (!isset($learnerPerformance[$learnerId])) {
        $learnerPerformance[$learnerId] = [
            'total_marks' => 0,
            'subject_count' => 0,
            'learner_name' => ''
        ];
    }
    $learnerPerformance[$learnerId]['total_marks'] += $mark['marks'];
    $learnerPerformance[$learnerId]['subject_count']++;
    
    // Get learner name
    foreach ($learners as $learner) {
        if ($learner['id'] == $learnerId) {
            $learnerPerformance[$learnerId]['learner_name'] = $learner['first_name'] . ' ' . $learner['last_name'];
            $learnerPerformance[$learnerId]['class'] = $learner['class'];
            break;
        }
    }
}

// Calculate averages per learner
foreach ($learnerPerformance as &$perf) {
    $perf['average'] = $perf['subject_count'] > 0 ? round($perf['total_marks'] / $perf['subject_count'], 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | LearnTrack</title>
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
        <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action">Attendance Reports</a>
        <a href="principal_results.php" class="list-group-item list-group-item-action active">Results</a>
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
            <i class="fas fa-chart-line me-2"></i>School Academic Performance
        </h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Records</h6>
                        <h3 class="mb-0"><?= $totalMarks ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Average Mark</h6>
                        <h3 class="mb-0 text-primary"><?= $averageMark ?>%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Highest Mark</h6>
                        <h3 class="mb-0 text-success"><?= $highestMark ?>%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Lowest Mark</h6>
                        <h3 class="mb-0 text-danger"><?= $lowestMark ?>%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-filter me-2"></i>Filter & Search
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Learner</label>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search by name...">
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
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>" <?= $filterSubject == $subject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-12">
                        <a href="principal_results.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Learner Performance Summary -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-users me-2"></i>Learner Performance Summary
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Learner Name</th>
                                <th>Grade/Class</th>
                                <th>Subjects</th>
                                <th>Total Marks</th>
                                <th>Average</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($learnerPerformance)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No performance data found.</td></tr>
                            <?php else: ?>
                                <?php
                                uasort($learnerPerformance, function($a, $b) {
                                    return $b['average'] <=> $a['average'];
                                });
                                foreach ($learnerPerformance as $learnerId => $perf): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($perf['learner_name']) ?></strong></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($perf['class']) ?></span></td>
                                        <td><?= $perf['subject_count'] ?></td>
                                        <td><?= $perf['total_marks'] ?></td>
                                        <td><strong><?= $perf['average'] ?>%</strong></td>
                                        <td>
                                            <?php if ($perf['average'] >= 80): ?>
                                                <span class="badge bg-success">Excellent</span>
                                            <?php elseif ($perf['average'] >= 60): ?>
                                                <span class="badge bg-primary">Good</span>
                                            <?php elseif ($perf['average'] >= 50): ?>
                                                <span class="badge bg-warning">Satisfactory</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Needs Improvement</span>
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

        <!-- Detailed Marks Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Detailed Marks
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
                                <th>Learner Name</th>
                                <th>Grade/Class</th>
                                <th>Subject</th>
                                <th>Term</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filteredMarks)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No marks records found.</td></tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($filteredMarks) as $mark): ?>
                                    <?php
                                    $learnerName = 'Unknown';
                                    $learnerClass = 'N/A';
                                    foreach ($learners as $learner) {
                                        if ($learner['id'] == $mark['learner_id']) {
                                            $learnerName = $learner['first_name'] . ' ' . $learner['last_name'];
                                            $learnerClass = $learner['class'];
                                            break;
                                        }
                                    }
                                    
                                    // Determine grade based on marks
                                    $grade = 'F';
                                    if ($mark['marks'] >= 80) $grade = 'A';
                                    elseif ($mark['marks'] >= 70) $grade = 'B';
                                    elseif ($mark['marks'] >= 60) $grade = 'C';
                                    elseif ($mark['marks'] >= 50) $grade = 'D';
                                    elseif ($mark['marks'] >= 40) $grade = 'E';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($learnerName) ?></strong></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($learnerClass) ?></span></td>
                                        <td><?= htmlspecialchars($mark['subject']) ?></td>
                                        <td><?= htmlspecialchars($mark['term'] ?? 'N/A') ?></td>
                                        <td><strong><?= $mark['marks'] ?>%</strong></td>
                                        <td>
                                            <span class="badge <?= in_array($grade, ['A', 'B']) ? 'bg-success' : (in_array($grade, ['C', 'D']) ? 'bg-primary' : 'bg-danger') ?>">
                                                <?= $grade ?>
                                            </span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
