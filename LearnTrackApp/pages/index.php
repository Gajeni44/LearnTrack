<?php
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Load the JSON data
    $jsonData = file_get_contents("users.json");
    $users = json_decode($jsonData, true);

    $found = false;
    foreach ($users as $u) {
        if ($u['username'] === $user && $u['password'] === $pass) {
            $_SESSION['user'] = $u['username'];
            $_SESSION['role'] = $u['role'];
            header("Location: " . $u['redirect']);
            exit();
        }
    }
    $error = "Invalid Username or Password!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>LearnTrack | Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 400px; margin: 100px auto;" class="card">
        <h2 style="text-align:center;">LearnTrack Login</h2>
        
        <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" style="width:100%; padding:10px; margin:10px 0;" required>
            
            <label>Password</label>
            <input type="password" name="password" style="width:100%; padding:10px; margin:10px 0;" required>
            
            <button type="submit">Secure Login</button>
        </form>
        <p style="text-align:center; font-size:12px; color:gray; margin-top:20px;">
            Authorized Personnel Only
        </p>
    </div>
</body>
</html>