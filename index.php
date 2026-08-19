<?php
session_start();
require_once 'includes/json_helper.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = findUser($_POST['email']);
    
    // Simple password check (In a professional build, use password_hash/verify)
    if ($user && $user['password'] === $_POST['password']) {
        $_SESSION['user'] = $user;
        
        // Log the successful login to system_logs.json
        logActivity("User logged in successfully");
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnTrack | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">

    <div class="card login-card shadow-lg">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold">LearnTrack</h2>
                <p class="text-muted">School Management System</p>
            </div>

            <?php if($error): ?> 
                <div class="alert alert-danger py-2 text-center small"><?= $error ?></div> 
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">&copy; 2026 LearnTrack System</small>
            </div>
        </div>
    </div>

</body>
</html>