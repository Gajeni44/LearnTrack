<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a School Admin, Principal, Teacher, or System Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['school_admin', 'principal', 'teacher', 'sys_admin'])) {
    header("Location: dashboard.php");
    exit();
}

$announcements = getData('announcements');
$learners = getData('learners');

$message = "";
$alertType = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        // Handle document upload if provided
        $documentPath = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $uploadDir = 'uploads/announcement_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $documentName = time() . '_' . basename($_FILES['document']['name']);
            $documentPath = $uploadDir . $documentName;
            move_uploaded_file($_FILES['document']['tmp_name'], $documentPath);
        }

        $newAnnouncement = [
            'id' => time(),
            'title' => $_POST['title'],
            'message' => $_POST['message'],
            'target_audience' => $_POST['target_audience'],
            'grade' => $_POST['grade'] ?? 'all',
            'document' => $documentPath,
            'created_by' => $_SESSION['user']['name'],
            'created_date' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];

        $announcements[] = $newAnnouncement;
        saveData('announcements', $announcements);

        $message = "Announcement sent successfully!";
        $alertType = "success";
        logActivity("Announcement '" . $_POST['title'] . "' sent by " . $_SESSION['user']['name']);

        header("Location: announcements.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
    
    if (isset($_POST['delete_announcement'])) {
        foreach ($announcements as $key => $announcement) {
            if ($announcement['id'] == $_POST['id']) {
                unset($announcements[$key]);
                break;
            }
        }
        
        $announcements = array_values($announcements);
        saveData('announcements', $announcements);
        
        $message = "Announcement deleted successfully!";
        $alertType = "warning";
        logActivity("Announcement deleted by " . $_SESSION['user']['name']);
        
        header("Location: announcements.php?message=" . urlencode($message) . "&type=" . $alertType);
        exit();
    }
}

// Handle URL messages
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $alertType = $_GET['type'] ?? 'info';
}

// Get unique grades for dropdown
$grades = [];
foreach ($learners as $learner) {
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
    <title>Announcements | LearnTrack</title>
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
            <a href="marks.php" class="list-group-item list-group-item-action">Academic Performance</a>
            <a href="announcements.php" class="list-group-item list-group-item-action active">Announcements</a>
            <a href="school_profile.php" class="list-group-item list-group-item-action">School Profile</a>
        <?php endif; ?>

        <?php if($_SESSION['user']['role'] == 'principal'): ?>
            <a href="transfer_letter.php" class="list-group-item list-group-item-action">Transfer Letter</a>
            <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action">Attendance Reports</a>
            <a href="principal_results.php" class="list-group-item list-group-item-action">Results</a>
            <a href="announcements.php" class="list-group-item list-group-item-action active">Announcements</a>
            <a href="principal_settings.php" class="list-group-item list-group-item-action">System Settings</a>
        <?php endif; ?>

        <?php if($_SESSION['user']['role'] == 'teacher'): ?>
            <a href="teacher_view_classes.php" class="list-group-item list-group-item-action">View Classes</a>
            <a href="manage_learners.php" class="list-group-item list-group-item-action">Manage Learners</a>
            <a href="teacher_view_profiles.php" class="list-group-item list-group-item-action">View Learner Profiles</a>
            <a href="attendance.php" class="list-group-item list-group-item-action">Mark Attendance</a>
            <a href="marks.php" class="list-group-item list-group-item-action">Capture Marks</a>
            <a href="announcements.php" class="list-group-item list-group-item-action active">Announcements</a>
            <a href="view_history.php" class="list-group-item list-group-item-action">Attendance History</a>
        <?php endif; ?>
        
        <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
            <a href="register_schools.php" class="list-group-item list-group-item-action">Register Schools</a>
            <a href="announcements.php" class="list-group-item list-group-item-action active">Announcements</a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="manage_roles.php" class="list-group-item list-group-item-action">Manage Roles</a>
            <a href="backup_restore.php" class="list-group-item list-group-item-action">Backup & Restore</a>
            <a href="security.php" class="list-group-item list-group-item-action">Security</a>
            <a href="contact_details.php" class="list-group-item list-group-item-action">Contact Details</a>
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
            <i class="fas fa-bullhorn me-2"></i>Parent Announcements
        </h2>

        <?php if($message): ?>
            <div class="alert alert-<?= $alertType ?> border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Send Announcement Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                <i class="fas fa-plus me-2"></i>Send New Announcement
            </button>
        </div>

        <!-- Announcements List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Recent Announcements
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($announcements)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Announcements Found</h5>
                        <p class="text-muted">Send your first announcement to parents.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($announcements) as $announcement): ?>
                    <div class="border-bottom p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
                                <div class="d-flex align-items-center text-muted small mb-2">
                                    <span class="me-3">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($announcement['created_by']) ?>
                                    </span>
                                    <span class="me-3">
                                        <i class="fas fa-calendar me-1"></i><?= date('M j, Y g:i A', strtotime($announcement['created_date'])) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-users me-1"></i>
                                        <?php
                                        if ($announcement['target_audience'] === 'all') {
                                            echo 'All Parents';
                                        } elseif ($announcement['target_audience'] === 'grade') {
                                            echo htmlspecialchars($announcement['grade']) . ' Parents';
                                        } elseif ($announcement['target_audience'] === 'school_admins') {
                                            echo 'All School Admins';
                                        } elseif ($announcement['target_audience'] === 'principals') {
                                            echo 'All Principals';
                                        } elseif ($announcement['target_audience'] === 'both') {
                                            echo 'Both School Admins and Principals';
                                        } elseif ($announcement['target_audience'] === 'staff') {
                                            echo 'All Staff';
                                        } elseif ($announcement['target_audience'] === 'teachers') {
                                            echo 'All Teachers';
                                        } elseif ($announcement['target_audience'] === 'class') {
                                            echo 'My Class Parents';
                                        } else {
                                            echo htmlspecialchars($announcement['target_audience']);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">Active</span>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteAnnouncement(<?= $announcement['id'] ?>, '<?= htmlspecialchars($announcement['title']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-light border-0">
                            <?= nl2br(htmlspecialchars($announcement['message'])) ?>
                        </div>
                        <?php if (!empty($announcement['document'])): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($announcement['document']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file me-1"></i>Download Attachment
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Announcement Title</label>
                        <input type="text" class="form-control" name="title" placeholder="Enter announcement title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Audience</label>
                        <select class="form-select" name="target_audience" id="targetAudience" required>
                            <option value="">Select Audience</option>
                            <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                                <option value="school_admins">All School Admins</option>
                                <option value="principals">All Principals</option>
                                <option value="both">Both School Admins and Principals</option>
                            <?php endif; ?>
                            <?php if($_SESSION['user']['role'] == 'school_admin'): ?>
                                <option value="staff">All Staff (Teachers, Principal, School Admin)</option>
                                <option value="all">All Parents</option>
                                <option value="grade">Specific Grade</option>
                            <?php endif; ?>
                            <?php if($_SESSION['user']['role'] == 'principal'): ?>
                                <option value="staff">All Staff (Teachers, School Admin)</option>
                                <option value="teachers">All Teachers</option>
                                <option value="all">All Parents</option>
                                <option value="grade">Specific Grade</option>
                            <?php endif; ?>
                            <?php if($_SESSION['user']['role'] == 'teacher'): ?>
                                <option value="class">My Class Parents</option>
                                <option value="grade">Specific Grade</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="gradeSelection" style="display: none;">
                        <label class="form-label">Select Grade</label>
                        <select class="form-select" name="grade" id="gradeSelect">
                            <option value="">Select Grade</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= htmlspecialchars($grade) ?>"><?= htmlspecialchars($grade) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="6" placeholder="Type your announcement message here..." required></textarea>
                        <small class="text-muted">
                            <?php if($_SESSION['user']['role'] == 'sys_admin'): ?>
                                This message will be sent to the selected school administrators.
                            <?php else: ?>
                                This message will be sent to the selected audience (parents or staff).
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach Document (Optional)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                        <small class="text-muted">Upload documents for meetings, events, exam timetables, etc. (PDF, DOC, XLS, PPT)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_announcement" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
<script>
document.getElementById('targetAudience').addEventListener('change', function() {
    const gradeSelection = document.getElementById('gradeSelection');
    const gradeSelect = document.getElementById('gradeSelect');
    
    if (this.value === 'grade') {
        gradeSelection.style.display = 'block';
        gradeSelect.required = true;
    } else {
        gradeSelection.style.display = 'none';
        gradeSelect.required = false;
        gradeSelect.value = '';
    }
});

function deleteAnnouncement(id, title) {
    if (confirm('Are you sure you want to delete announcement "' + title + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="id" value="' + id + '"><input type="hidden" name="delete_announcement" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
