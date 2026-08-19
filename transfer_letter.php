<?php
session_start();
require_once 'includes/json_helper.php';

// Check if logged in AND if the user is a Principal
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: dashboard.php");
    exit();
}

$learners = getData('learners');
$schools = getData('schools');
$users = getData('users');

// Get the school for the current principal
$userSchoolId = $_SESSION['user']['school_id'] ?? null;
$school = null;

if ($userSchoolId) {
    foreach ($schools as $s) {
        if ($s['id'] == $userSchoolId) {
            $school = $s;
            break;
        }
    }
}

$selectedLearner = null;
$transferDate = date('Y-m-d');
$reason = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_letter'])) {
        $learnerId = $_POST['learner_id'];
        $transferDate = $_POST['transfer_date'];
        $reason = $_POST['reason'];

        foreach ($learners as $learner) {
            if ($learner['id'] == $learnerId) {
                $selectedLearner = $learner;
                break;
            }
        }
    }
}

// Get unique grades for filtering
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
    <title>Transfer Letter | LearnTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            .letter-content { border: none !important; box-shadow: none !important; }
        }
        .letter-content {
            background: white;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        .letter-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .letter-logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        .letter-body {
            line-height: 1.6;
        }
        .letter-signature {
            margin-top: 60px;
            text-align: right;
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
        <a href="transfer_letter.php" class="list-group-item list-group-item-action active">Transfer Letter</a>
        <a href="principal_attendance_reports.php" class="list-group-item list-group-item-action">Attendance Reports</a>
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
        <h2 class="mb-4 no-print">
            <i class="fas fa-file-alt me-2"></i>Transfer Letter Generation
        </h2>

        <!-- Selection Form -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-user-graduate me-2"></i>Select Learner
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Select Learner</label>
                        <select class="form-select" name="learner_id" required>
                            <option value="">Select Learner</option>
                            <?php foreach ($learners as $learner): ?>
                                <option value="<?= $learner['id'] ?>" <?= isset($_POST['learner_id']) && $_POST['learner_id'] == $learner['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?> - <?= htmlspecialchars($learner['class']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transfer Date</label>
                        <input type="date" class="form-control" name="transfer_date" value="<?= $transferDate ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Reason for Transfer</label>
                        <input type="text" class="form-control" name="reason" value="<?= htmlspecialchars($reason) ?>" placeholder="e.g., Relocation, Change of school, etc." required>
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" name="generate_letter" class="btn btn-primary">
                            <i class="fas fa-file-alt me-2"></i>Generate Letter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Letter Preview -->
        <?php if ($selectedLearner && $school): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-eye me-2"></i>Letter Preview
                </h5>
                <div class="btn-group btn-group-sm no-print">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button class="btn btn-outline-success" onclick="downloadPDF()">
                        <i class="fas fa-file-pdf me-1"></i>Download PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="letter-content" id="letterContent">
                    <div class="letter-header">
                        <?php if (!empty($school['logo'])): ?>
                            <img src="<?= htmlspecialchars($school['logo']) ?>" alt="School Logo" class="letter-logo">
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($school['name']) ?></h4>
                        <p class="mb-1">
                            <strong>EMIS:</strong> <?= htmlspecialchars($school['emis_number'] ?? 'N/A') ?> |
                            <strong>Principal:</strong> <?= htmlspecialchars($school['principal_name']) ?>
                        </p>
                        <p class="mb-0">
                            <?= htmlspecialchars($school['address']) ?> |
                            <?= htmlspecialchars($school['telephone']) ?> |
                            <?= htmlspecialchars($school['email']) ?>
                        </p>
                        <?php if (!empty($school['slogan'])): ?>
                            <p class="mb-0 text-muted fst-italic">"<?= htmlspecialchars($school['slogan']) ?>"</p>
                        <?php endif; ?>
                    </div>

                    <div class="letter-body">
                        <p class="mb-4">
                            <strong>Date:</strong> <?= date('F j, Y', strtotime($transferDate)) ?>
                        </p>

                        <p class="mb-2"><strong>To Whom It May Concern,</strong></p>

                        <p class="mb-4">
                            This letter serves to confirm that <strong><?= htmlspecialchars($selectedLearner['first_name'] . ' ' . $selectedLearner['last_name']) ?></strong>
                            has been a learner at <?= htmlspecialchars($school['name']) ?> in <?= htmlspecialchars($selectedLearner['class']) ?>.
                        </p>

                        <p class="mb-4">
                            The learner is being transferred due to: <strong><?= htmlspecialchars($reason) ?></strong>.
                            The transfer is effective from <strong><?= date('F j, Y', strtotime($transferDate)) ?></strong>.
                        </p>

                        <p class="mb-4">
                            During their time at our school, the learner has maintained satisfactory academic performance
                            and conduct. We wish them success in their future educational endeavors.
                        </p>

                        <p class="mb-4">
                            <strong>Parent/Guardian Information:</strong><br>
                            Name: <?= htmlspecialchars($selectedLearner['parent_name']) ?><br>
                            Phone: <?= htmlspecialchars($selectedLearner['parent_phone']) ?><br>
                            <?php if (!empty($selectedLearner['parent_email'])): ?>
                            Email: <?= htmlspecialchars($selectedLearner['parent_email']) ?>
                            <?php endif; ?>
                        </p>

                        <div class="letter-signature">
                            <p class="mb-1">Sincerely,</p>
                            <p class="mb-1"><strong><?= htmlspecialchars($school['principal_name']) ?></strong></p>
                            <p class="mb-0">Principal</p>
                            <p class="mb-0"><?= htmlspecialchars($school['name']) ?></p>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('letterContent');
    const learnerName = '<?= $selectedLearner ? htmlspecialchars($selectedLearner['first_name'] . '_' . $selectedLearner['last_name']) : 'transfer_letter' ?>';
    const filename = learnerName + '_transfer_letter.pdf';

    const opt = {
        margin: 10,
        filename: filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
