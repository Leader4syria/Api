<?php
session_start();

// مسارات الملفات
define('USERS_FILE', __DIR__ . '/data/users.json');
define('TOOLS_DIR', __DIR__ . '/tools');
define('ANNOUNCEMENTS_FILE', __DIR__ . '/data/announcements.json');

// إنشاء الهيكل الأساسي إذا لم يكن موجودًا
if (!file_exists(dirname(USERS_FILE))) {
    mkdir(dirname(USERS_FILE), 0755, true);
}
if (!file_exists(TOOLS_DIR)) {
    mkdir(TOOLS_DIR, 0755, true);
}

/**
 * تحميل المستخدمين من ملف JSON
 */
function loadUsers() {
    if (!file_exists(USERS_FILE)) {
        $defaultUsers = [
            'admin' => [
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'created_at' => time()
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($defaultUsers, JSON_PRETTY_PRINT));
        return $defaultUsers;
    }
    return json_decode(file_get_contents(USERS_FILE), true);
}

/**
 * حفظ المستخدمين في ملف JSON
 */
function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

/**
 * تسجيل الدخول
 */
function login($username, $password) {
    $users = loadUsers();
    
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['user'] = [
            'username' => $username,
            'role' => $users[$username]['role']
        ];
        return true;
    }
    return false;
}

/**
 * تسجيل الخروج
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * الحصول على اسم المستخدم الحالي
 */
function currentUser() {
    return $_SESSION['user']['username'] ?? null;
}

/**
 * التحقق إذا كان المستخدم أدمن
 */
function isAdmin() {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * إضافة مستخدم جديد
 */
function addUser($username, $password, $role = 'user') {
    $users = loadUsers();
    
    if (!isset($users[$username])) {
        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => time()
        ];
        saveUsers($users);
        return true;
    }
    return false;
}

/**
 * حذف مستخدم
 */
function deleteUser($username) {
    $users = loadUsers();
    
    if (isset($users[$username]) && count($users) > 1 && $username !== 'admin') {
        // حذف أدوات المستخدم أولاً
        deleteUserTools($username);
        
        unset($users[$username]);
        saveUsers($users);
        return true;
    }
    return false;
}

/**
 * حذف أدوات المستخدم
 */
function deleteUserTools($username) {
    foreach (scandir(TOOLS_DIR) as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $toolFile = TOOLS_DIR . '/' . $file;
            $data = json_decode(file_get_contents($toolFile), true);
            if ($data['owner'] === $username) {
                unlink($toolFile);
            }
        }
    }
}

/**
 * الحصول على أدوات المستخدم
 */
function getToolsByOwner($owner) {
    $tools = [];
    foreach (scandir(TOOLS_DIR) as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $data = json_decode(file_get_contents(TOOLS_DIR.'/'.$file), true);
            if ($data['owner'] === $owner) {
                $tools[] = [
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'data' => $data
                ];
            }
        }
    }
    return $tools;
}

/**
 * إدارة الإعلانات
 */
function getAnnouncements() {
    if (!file_exists(ANNOUNCEMENTS_FILE)) {
        file_put_contents(ANNOUNCEMENTS_FILE, json_encode([]));
    }
    return json_decode(file_get_contents(ANNOUNCEMENTS_FILE), true);
}

function addAnnouncement($text, $createdBy) {
    $announcements = getAnnouncements();
    $announcements[] = [
        'text' => $text,
        'created_by' => $createdBy,
        'created_at' => time()
    ];
    file_put_contents(ANNOUNCEMENTS_FILE, json_encode($announcements, JSON_PRETTY_PRINT));
}

function deleteAnnouncement($index) {
    $announcements = getAnnouncements();
    if (isset($announcements[$index])) {
        unset($announcements[$index]);
        file_put_contents(ANNOUNCEMENTS_FILE, json_encode(array_values($announcements), JSON_PRETTY_PRINT));
        return true;
    }
    return false;
}
?>