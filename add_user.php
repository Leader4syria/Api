<?php
require_once 'auth.php';

// فقط الأدمن يمكنه إضافة مستخدمين
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'غير مصرح']));
}

// استقبال البيانات
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// التحقق من البيانات
if (empty($username) || empty($password)) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'يجب إدخال اسم مستخدم وكلمة مرور']));
}

// إضافة المستخدم
if (addUser($username, $password)) {
    echo json_encode(['success' => true, 'message' => 'تمت إضافة المستخدم بنجاح']);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم موجود بالفعل']);
}
?>