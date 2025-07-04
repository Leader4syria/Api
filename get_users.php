<?php
require_once 'auth.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json');
echo json_encode(loadUsers());
?>