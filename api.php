<?php
require_once 'auth.php';

header('Content-Type: application/json');

if (!isset($_GET['tool']) || !isset($_GET['key'])) {
    http_response_code(400);
    echo json_encode(['error' => 'المعطيات ناقصة']);
    exit;
}

$name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tool']);
$key = $_GET['key'];
$toolFile = "tools/$name.json";

if (!file_exists($toolFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'الأداة غير موجودة']);
    exit;
}

$tool = json_decode(file_get_contents($toolFile), true);

// التحقق من أن المفتاح صحيح (md5 للمالك)
if ($key !== md5($tool['owner'])) {
    http_response_code(403);
    echo json_encode(['error' => 'غير مصرح بالوصول']);
    exit;
}

// الباقي كما هو
if ($tool['status'] === 'OFF') {
    echo json_encode(['status' => 'OFF']);
    exit;
}

if ($tool['duration'] > 0 && time() > $tool['start_time'] + $tool['duration']) {
    echo json_encode(['status' => 'OFF']);
    exit;
}

echo json_encode(['status' => 'ON']);
?>