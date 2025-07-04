<?php
require_once 'auth.php';

if (!isAdmin()) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'غير مصرح']));
}

$username = $_POST['username'] ?? '';
if (empty($username)) {
    exit(json_encode(['success' => false, 'message' => 'اسم المستخدم مطلوب']));
}

// منع حذف المستخدم الحالي
if ($username === currentUser()) {
    exit(json_encode(['success' => false, 'message' => 'لا يمكن حذف المستخدم الحالي']));
}

$users = loadUsers();
if (!isset($users[$username])) {
    exit(json_encode(['success' => false, 'message' => 'المستخدم غير موجود']));
}

// حذف أدوات المستخدم أولاً
foreach (scandir('tools') as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
        $toolFile = "tools/$file";
        $data = json_decode(file_get_contents($toolFile), true);
        if ($data['owner'] === $username) {
            unlink($toolFile);
        }
    }
}

// حذف المستخدم
unset($users[$username]);
file_put_contents(__DIR__ . '/data/users.json', json_encode($users, JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);
?>