<?php
function getData($file) {
    $path = __DIR__ . "/../data/$file.json";
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?: [];
}

function saveData($file, $data) {
    $path = __DIR__ . "/../data/$file.json";
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function findUser($email) {
    $users = getData('users');
    foreach ($users as $user) {
        if ($user['email'] === $email) return $user;
    }
    return null;
    
}
function logActivity($action) {
    $logs = getData('system_logs');
    $newLog = [
        "timestamp" => date("Y-m-d H:i:s"),
        "user" => $_SESSION['user']['name'] ?? 'System',
        "role" => $_SESSION['user']['role'] ?? 'N/A',
        "action" => $action
    ];
    array_unshift($logs, $newLog); // Puts the newest log at the top
    saveData('system_logs', $logs);
}

?>
