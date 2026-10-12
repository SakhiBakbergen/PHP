<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';
requireLogin();

$allowedLangs = ['ru', 'kk', 'en'];
$lang = $_COOKIE['interface_lang'] ?? 'ru';
if (!in_array($lang, $allowedLangs, true)) {
    $lang = 'ru';
}

$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Смена языка интерфейса
    if (isset($_POST['lang']) && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $newLang = $_POST['lang'];
        if (in_array($newLang, $allowedLangs, true)) {
            setcookie('interface_lang', $newLang, [
                'expires' => time() + 2592000,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            header('Location: /dashboard.php');
            exit;
        }
    }

    // Одобрение заявки репетитором/регистратором
    if (isset($_POST['action']) && $_POST['action'] === 'approve_student') {
        if ($_SESSION['role'] === 'registrar' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            setEnrollmentStatus('approved');
            $success_msg = "Заявка успешно принята!";
        }
    }
}

$currentStatus = getEnrollmentStatus();

// Переводы интерфейса
$texts = [
    'ru' => [
        'title' => 'Личный кабинет',
        'role' => 'Роль',
        'lang' => 'Язык интерфейса',
        'save' => 'Сохранить',
        'logout' => 'Выйти из системы',
        'student_panel' => 'Регистрация на дисциплины',
        'courses' => 'Выбранные курсы: «Программирование на PHP», «Базы данных»',
        'pending' => '⏳ Ожидает подтверждения регистратора...',
        'approved' => '✅ Заявка одобрена! Вы допущены к занятиям.',
        'registrar_panel' => 'Панель регистратора (преподавателя)',
        'student_request' => 'Студент: student@atu.edu.kz подал заявку на дисциплины.',
        'approve_btn' => '✅ Принять заявку студента'
    ],
    'kk' => [
        'title' => 'Жеке кабинет',
        'role' => 'Рөлі',
        'lang' => 'Интерфейс тілі',
        'save' => 'Сақтау',
        'logout' => 'Жүйеден шығу',
        'student_panel' => 'Пәндерге тіркелу',
        'courses' => 'Таңдалған курстар: «PHP бағдарламалау», «Мәліметтер базасы»',
        'pending' => '⏳ Тіркеушінің растауын күтуде...',
        'approved' => '✅ Өтінім мақұлданды! Сабаққа рұқсат етілді.',
        'registrar_panel' => 'Тіркеуші панелі (оқытушы)',
        'student_request' => 'Студент: student@atu.edu.kz пәндерге өтінім берді.',
        'approve_btn' => '✅ Студент өтінімін қабылдау'
    ],
    'en' => [
        'title' => 'Dashboard',
        'role' => 'Role',
        'lang' => 'Interface Language',
        'save' => 'Save',
        'logout' => 'Logout',
        'student_panel' => 'Course Enrollment',
        'courses' => 'Selected courses: "PHP Programming", "Databases"',
        'pending' => '⏳ Waiting for registrar approval...',
        'approved' => '✅ Request approved! You are enrolled.',
        'registrar_panel' => 'Registrar Panel',
        'student_request' => 'Student: student@atu.edu.kz requested course enrollment.',
        'approve_btn' => '✅ Accept Student Request'
    ]
];
$t = $texts[$lang];
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif; }
        body { background: var(--bg-color); color: var(--text-main); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .dashboard-card { background: var(--card-bg); width: 100%; max-width: 650px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); padding: 40px; border: 1px solid var(--border); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .header h1 { font-size: 22px; color: var(--text-main); font-weight: 700; }
        .user-badge { background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .section { margin-bottom: 25px; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid var(--border); }
        .section h3 { font-size: 16px; margin-bottom: 10px; color: var(--primary); font-weight: 600; }
        .lang-form { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; }
        .lang-form select { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: white; font-size: 14px; outline: none; }
        .btn { padding: 10px 18px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-success { background: #16a34a; color: white; width: 100%; margin-top: 10px; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .btn-danger:hover { background: #fecaca; }
        .status-box { display: inline-block; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 10px; }
        .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; }
        .email-info { font-size: 14px; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <div class="header">
            <h1><?= $t['title'] ?></h1>
            <span class="user-badge"><?= h($_SESSION['role']) ?></span>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><?= h($success_msg) ?></div>
        <?php endif; ?>

        <!-- Настройка языка интерфейса (Cookie) -->
        <form method="post" class="lang-form">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <label style="font-size: 14px; font-weight: 500;"><?= $t['lang'] ?>:</label>
            <select name="lang">
                <option value="ru" <?= $lang === 'ru' ? 'selected' : '' ?>>Русский</option>
                <option value="kk" <?= $lang === 'kk' ? 'selected' : '' ?>>Қазақша</option>
                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
            </select>
            <button type="submit" class="btn btn-primary"><?= $t['save'] ?></button>
        </form>

        <!-- Основной контент по ролям -->
        <div class="section">
            <?php if ($_SESSION['role'] === 'student'): ?>
                <h3>📌 <?= $t['student_panel'] ?></h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;"><?= $t['courses'] ?></p>
                <div>
                    <span style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Статус вашей заявки:</span><br>
                    <?php if ($currentStatus === 'approved'): ?>
                        <div class="status-box status-approved"><?= $t['approved'] ?></div>
                    <?php else: ?>
                        <div class="status-box status-pending"><?= $t['pending'] ?></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($_SESSION['role'] === 'registrar'): ?>
                <h3>⚙️ <?= $t['registrar_panel'] ?></h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 12px;"><?= $t['student_request'] ?></p>
                
                <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                    <p style="font-size: 14px; margin-bottom: 8px;"><strong>Текущий статус:</strong> 
                        <?= $currentStatus === 'approved' ? '<span style="color: #16a34a; font-weight: bold;">Одобрено ✅</span>' : '<span style="color: #b45309; font-weight: bold;">Ожидает ⏳</span>' ?>
                    </p>
                    <?php if ($currentStatus !== 'approved'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="approve_student">
                            <button type="submit" class="btn btn-success"><?= $t['approve_btn'] ?></button>
                        </form>
                    <?php else: ?>
                        <p style="color: #16a34a; font-size: 13px; font-weight: 600; margin-top: 5px;">Заявка уже подтверждена.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <span class="email-info">👤 <?= h($_SESSION['email']) ?></span>
            <form method="post" action="/logout.php">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                <button type="submit" class="btn btn-danger"><?= $t['logout'] ?></button>
            </form>
        </div>
    </div>
</body>
</html>