<?php
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = currentUser();
$isAdmin = isAdmin();

// جلب أدوات المستخدم الحالي فقط
$userTools = [];
$allTools = [];
foreach (scandir('tools') as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
        $toolData = json_decode(file_get_contents("tools/$file"), true);
        $toolName = pathinfo($file, PATHINFO_FILENAME);
        
        if ($toolData['owner'] === $currentUser) {
            $userTools[] = [
                'name' => $toolName,
                'data' => $toolData,
                'expires' => ($toolData['duration'] > 0) ? 
                    date('Y-m-d H:i:s', $toolData['start_time'] + $toolData['duration']) : '∞',
                'full_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                             $_SERVER['HTTP_HOST'] . '/api.php?tool=' . $toolName . '&key=' . md5($currentUser)
            ];
        }
        
        // للأدمن: عرض جميع الأدوات
        if ($isAdmin) {
            $allTools[] = [
                'name' => $toolName,
                'data' => $toolData,
                'owner' => $toolData['owner']
            ];
        }
    }
}

// الإحصائيات
$total_tools = count($userTools);
$active_tools = count(array_filter($userTools, fn($t) => $t['data']['status'] === 'ON'));

// جلب الإعلانات
$announcements = getAnnouncements();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأدوات</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 16px;
        }
        
        h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 28px;
            position: relative;
            padding-bottom: 15px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .stat-card .count {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: var(--gray);
            font-size: 14px;
        }
        
        .card-1 { border-top: 4px solid var(--primary); }
        .card-2 { border-top: 4px solid var(--success); }
        .card-3 { border-top: 4px solid var(--warning); }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--primary);
            color: var(--primary);
        }
        
        .tab:hover:not(.active) {
            border-bottom: 3px solid #ddd;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        form {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            background: var(--light);
            padding: 20px;
            border-radius: 12px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--gray);
        }
        
        input[type="text"], 
        input[type="number"],
        select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Tajawal', sans-serif;
        }
        
        button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-family: 'Tajawal', sans-serif;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        button:hover {
            background: var(--primary-dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-on {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-off {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .url-box {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 5px;
            border: 1px solid #ddd;
        }
        
        .url-box input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        
        .copy-btn {
            background: var(--success);
        }
        
        .toggle-btn {
            background: var(--warning);
            color: var(--dark);
        }
        
        .delete-btn {
            background: var(--danger);
        }
        
        .footer {
            text-align: center;
            font-size: 14px;
            color: var(--gray);
            margin-top: 40px;
            padding: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .logout-btn {
            background: var(--danger);
            margin-left: auto;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            background: var(--gray);
            color: white;
            margin-right: 5px;
        }
        
        .announcements-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .announcement {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .announcement small {
            color: #6c757d;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 20px;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .stat-card {
                min-width: 100%;
                margin-bottom: 15px;
            }
            
            table {
                display: none;
            }
            
            .mobile-tools {
                display: block;
            }
            
            .tool-card {
                background: white;
                border-radius: 10px;
                padding: 15px;
                margin-bottom: 15px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .tool-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
            }
            
            .tool-status {
                padding: 3px 10px;
                border-radius: 15px;
                font-size: 12px;
            }
            
            .tool-url {
                margin: 10px 0;
            }
            
            .tool-actions {
                display: flex;
                gap: 8px;
            }
            
            .tool-actions button {
                flex: 1;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-tools {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-cogs"></i> لوحة تحكم الأدوات</h2>
        <form action="logout.php" method="POST" style="display: inline;">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </button>
        </form>
    </div>
    
    <div class="stats-container">
        <div class="stat-card card-1">
            <i class="fas fa-tools"></i>
            <div class="count"><?= $total_tools ?></div>
            <div class="label">أدواتك</div>
        </div>
        <div class="stat-card card-2">
            <i class="fas fa-power-off"></i>
            <div class="count"><?= $active_tools ?></div>
            <div class="label">أدواتك النشطة</div>
        </div>
        <div class="stat-card card-3">
            <i class="fas fa-user"></i>
            <div class="count"><?= $currentUser ?></div>
            <div class="label">اسم المستخدم</div>
        </div>
    </div>
    
    <div class="tabs">
        <div class="tab active" data-tab="tools">أدواتك</div>
        <div class="tab" data-tab="add-tool">إضافة أداة</div>
        <?php if ($isAdmin): ?>
        <div class="tab" data-tab="users">إدارة المستخدمين</div>
        <div class="tab" data-tab="announcements">الإعلانات</div>
        <?php endif; ?>
    </div>
    
    <div class="tab-content active" id="tools-tab">
        <?php if (count($userTools) > 0): ?>
            <div class="desktop-view">
                <table>
                    <tr>
                        <th>اسم الأداة</th>
                        <th>الحالة</th>
                        <th>ينتهي في</th>
                        <th>رابط API</th>
                        <th>التحكم</th>
                    </tr>
                    <?php foreach ($userTools as $tool): ?>
                        <tr>
                            <td><?= htmlspecialchars($tool['name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($tool['data']['status']) ?>">
                                    <?= $tool['data']['status'] ?>
                                </span>
                            </td>
                            <td><?= $tool['expires'] ?></td>
                            <td>
                                <div class="url-box">
                                    <input type="text" value="<?= $tool['full_url'] ?>" readonly id="link-<?= $tool['name'] ?>">
                                    <button class="copy-btn" onclick="copyLink('<?= $tool['name'] ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="btn-group">
                                <form action="update.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="tool_name" value="<?= $tool['name'] ?>">
                                    <input type="hidden" name="status" value="<?= $tool['data']['status'] === 'ON' ? 'OFF' : 'ON' ?>">
                                    <input type="hidden" name="duration" value="0">
                                    <button class="toggle-btn" type="submit">
                                        <i class="fas fa-toggle-<?= $tool['data']['status'] === 'ON' ? 'on' : 'off' ?>"></i>
                                        <?= $tool['data']['status'] === 'ON' ? 'إيقاف' : 'تشغيل' ?>
                                    </button>
                                </form>
                                <form action="delete.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف الأداة؟');" style="display:inline;">
                                    <input type="hidden" name="tool_name" value="<?= $tool['name'] ?>">
                                    <button class="delete-btn" type="submit">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="mobile-tools">
                <?php foreach ($userTools as $tool): ?>
                    <div class="tool-card">
                        <div class="tool-header">
                            <h3><?= htmlspecialchars($tool['name']) ?></h3>
                            <span class="tool-status status-<?= strtolower($tool['data']['status']) ?>">
                                <?= $tool['data']['status'] ?>
                            </span>
                        </div>
                        
                        <div class="tool-meta">
                            <p><i class="fas fa-clock"></i> ينتهي في: <?= $tool['expires'] ?></p>
                        </div>
                        
                        <div class="tool-url">
                            <div class="url-box">
                                <input type="text" value="<?= $tool['full_url'] ?>" readonly id="mobile-link-<?= $tool['name'] ?>">
                                <button class="copy-btn" onclick="copyLink('mobile-link-<?= $tool['name'] ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="tool-actions">
                            <form action="update.php" method="POST" style="flex:1;">
                                <input type="hidden" name="tool_name" value="<?= $tool['name'] ?>">
                                <input type="hidden" name="status" value="<?= $tool['data']['status'] === 'ON' ? 'OFF' : 'ON' ?>">
                                <input type="hidden" name="duration" value="0">
                                <button class="toggle-btn" type="submit">
                                    <i class="fas fa-toggle-<?= $tool['data']['status'] === 'ON' ? 'on' : 'off' ?>"></i>
                                    <?= $tool['data']['status'] === 'ON' ? 'إيقاف' : 'تشغيل' ?>
                                </button>
                            </form>
                            
                            <form action="delete.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف الأداة؟');" style="flex:1;">
                                <input type="hidden" name="tool_name" value="<?= $tool['name'] ?>">
                                <button class="delete-btn" type="submit">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>لا توجد أدوات متاحة</h3>
                <p>يمكنك إضافة أدوات جديدة من خلال تبويب "إضافة أداة"</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="tab-content" id="add-tool-tab">
        <form action="update.php" method="POST">
            <div class="form-group">
                <label for="tool_name"><i class="fas fa-toolbox"></i> اسم الأداة</label>
                <input type="text" name="tool_name" id="tool_name" placeholder="أدخل اسم الأداة" required>
            </div>
            
            <div class="form-group">
                <label for="status"><i class="fas fa-power-off"></i> الحالة</label>
                <select name="status" id="status">
                    <option value="ON">تشغيل</option>
                    <option value="OFF">إيقاف</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="duration"><i class="fas fa-clock"></i> المدة بالدقائق (0 = دائم)</label>
                <input type="number" name="duration" id="duration" placeholder="أدخل المدة بالدقائق">
            </div>
            
            <button type="submit">
                <i class="fas fa-save"></i> حفظ الأداة
            </button>
        </form>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="tab-content" id="users-tab">
        <div class="user-management">
            <h3><i class="fas fa-users-cog"></i> إدارة المستخدمين</h3>
            
            <form id="add-user-form" style="margin-bottom: 30px;">
                <div class="form-group">
                    <label for="new-username">اسم المستخدم الجديد</label>
                    <input type="text" id="new-username" required>
                </div>
                
                <div class="form-group">
                    <label for="new-password">كلمة المرور</label>
                    <input type="password" id="new-password" required>
                </div>
                
                <button type="button" onclick="addUser()">
                    <i class="fas fa-user-plus"></i> إضافة مستخدم
                </button>
            </form>
            
            <div class="user-list" id="user-list">
                <!-- سيتم ملؤها بالجافاسكريبت -->
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="announcements-tab">
        <div class="announcements-container">
            <h3><i class="fas fa-bullhorn"></i> الإعلانات</h3>
            
            <form id="add-announcement-form" style="margin-bottom: 20px;">
                <div class="form-group">
                    <textarea id="announcement-text" placeholder="نص الإعلان" required style="width: 100%; padding: 10px;"></textarea>
                </div>
                <button type="button" onclick="addAnnouncement()">
                    <i class="fas fa-plus"></i> إضافة إعلان
                </button>
            </form>
            
            <div class="announcements-list" id="announcements-list">
                <?php foreach (array_reverse($announcements) as $index => $announcement): ?>
                    <div class="announcement">
                        <p><?= htmlspecialchars($announcement['text']) ?></p>
                        <small>
                            <?= date('Y-m-d H:i', $announcement['created_at']) ?> | 
                            بواسطة: <?= htmlspecialchars($announcement['created_by']) ?>
                        </small>
                        <button class="delete-btn" style="margin-top: 5px; padding: 5px 10px;" onclick="deleteAnnouncement(<?= $index ?>)">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="footer">
    <p>أدوات تحكم خاصة - Leader Syria</p>
    <p>الإصدار 2.1.0 | آخر تحديث: <?= date('Y-m-d') ?></p>
</div>

<script>
    // تبديل التبويبات
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab + '-tab').classList.add('active');
        });
    });
    
    // نسخ الروابط
    function copyLink(id) {
        var copyText = document.getElementById(id);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        alert("تم نسخ الرابط: " + copyText.value);
    }
    
    // إدارة المستخدمين (للأدمن فقط)
    <?php if ($isAdmin): ?>
    function loadUsers() {
        fetch('get_users.php')
            .then(response => response.json())
            .then(users => {
                const userList = document.getElementById('user-list');
                userList.innerHTML = '';
                
                Object.keys(users).forEach(username => {
                    if (username !== '<?= $currentUser ?>') {
                        const userItem = document.createElement('div');
                        userItem.style.display = 'flex';
                        userItem.style.justifyContent = 'space-between';
                        userItem.style.padding = '10px';
                        userItem.style.borderBottom = '1px solid #eee';
                        
                        userItem.innerHTML = `
                            <div>${username}</div>
                            <button class="delete-btn" onclick="deleteUser('${username}')">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        `;
                        
                        userList.appendChild(userItem);
                    }
                });
            });
    }
    
    function addUser() {
        const username = document.getElementById('new-username').value.trim();
        const password = document.getElementById('new-password').value.trim();
        
        if (!username || !password) {
            alert('يجب إدخال اسم مستخدم وكلمة مرور');
            return;
        }

        fetch('add_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.getElementById('new-username').value = '';
                document.getElementById('new-password').value = '';
                loadUsers();
            } else {
                alert(data.message);
            }
        });
    }
    
    function deleteUser(username) {
        if (confirm(`هل أنت متأكد من حذف المستخدم ${username}؟ سيتم حذف جميع أدواته أيضًا!`)) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حذف المستخدم بنجاح');
                    loadUsers();
                } else {
                    alert(data.message || 'حدث خطأ أثناء حذف المستخدم');
                }
            });
        }
    }
    
    // إدارة الإعلانات
    function addAnnouncement() {
        const text = document.getElementById('announcement-text').value.trim();
        
        if (!text) {
            alert('يجب إدخال نص الإعلان');
            return;
        }

        fetch('add_announcement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                text: text
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('تمت إضافة الإعلان بنجاح');
                document.getElementById('announcement-text').value = '';
                location.reload();
            } else {
                alert(data.message || 'حدث خطأ أثناء إضافة الإعلان');
            }
        });
    }
    
    function deleteAnnouncement(index) {
        if (confirm('هل أنت متأكد من حذف هذا الإعلان؟')) {
            fetch('delete_announcement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    index: index
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حذف الإعلان بنجاح');
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء حذف الإعلان');
                }
            });
        }
    }
    
    // تحميل المستخدمين عند فتح تبويب الإدارة
    document.querySelector('[data-tab="users"]')?.addEventListener('click', loadUsers);
    <?php endif; ?>
</script>

</body>
</html>