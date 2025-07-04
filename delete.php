<?php
require_once 'auth.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit;
}

$username = $_POST['username'] ?? '';
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم مطلوب']);
    exit;
}

if (deleteUser($username)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'لا يمكن حذف المستخدم']);
}
?>