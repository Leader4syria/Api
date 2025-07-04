<?php
require_once 'auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if (!isset($_POST['tool_name']) || !isset($_POST['status'])) {
    die("بيانات غير مكتملة.");
}

$name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['tool_name']);
$status = ($_POST['status'] === 'ON') ? 'ON' : 'OFF';
$duration = isset($_POST['duration']) ? intval($_POST['duration']) * 60 : 0;

$toolFile = TOOLS_DIR . '/' . $name . '.json';
$data = file_exists($toolFile) ? json_decode(file_get_contents($toolFile), true) : [];

// تعيين المالك إن لم يكن موجودًا
$data['owner'] = $data['owner'] ?? currentUser();

// تحديث الحالة والمدة
$data['status'] = $status;
$data['duration'] = $duration;

// التعامل مع وقت البدء
if ($status === 'ON') {
    $data['start_time'] = $data['start_time'] ?? time();
} else {
    $data['start_time'] = $data['start_time'] ?? time();
}

// حساب وقت الانتهاء إذا كانت المدة > 0
if ($duration > 0 && $status === 'ON') {
    $data['expires_at'] = $data['start_time'] + $duration;
} else {
    $data['expires_at'] = 0;
}

file_put_contents($toolFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header("Location: index.php");
exit;
?>