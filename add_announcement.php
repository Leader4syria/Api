<?php
require_once 'auth.php';

if (!isAdmin()) {
    http_response_code(403);
    exit;
}

$text = $_POST['text'] ?? '';
if (empty($text)) {
    header('Location: index.php');
    exit;
}

addAnnouncement($text, currentUser());
header('Location: index.php');
?>