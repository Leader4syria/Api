<?php
require_once 'auth.php';

// إنهاء جميع بيانات الجلسة
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

// التحويل إلى صفحة تسجيل الدخول
header('Location: login.php');
exit;
?>