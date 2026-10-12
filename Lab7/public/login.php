<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$_SESSION['login_csrf'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (hash_equals($_SESSION['login_csrf'], $token)) {
        // Проверка пользователей под Вариант 1
        if ($email === 'student@atu.edu.kz' && $password === 'Student123!') {
            loginUser(1, $email, 'student');
            header('Location: /dashboard.php');
            exit;
        } elseif ($email === 'registrar@atu.edu.kz' && $password === 'Registrar123!') {
            loginUser(2, $email, 'registrar');
            header('Location: /dashboard.php');
            exit;
        }
    }
    $error = 'Неверный e-mail или пароль.';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Регистрация на дисциплины</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #ffffff; border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        .card h1 { font-size: 22px; color: #1e3a8a; margin-bottom: 8px; text-align: center; font-weight: 700; }
        .subtitle { font-size: 13px; color: #64748b; text-align: center; margin-bottom: 24px; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-info { background-color: #e0f2fe; color: #075985; border: 1px solid #38bdf8; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        button.btn-primary { width: 100%; padding: 12px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        button.btn-primary:hover { background-color: #1d4ed8; }
        .hints { margin-top: 15px; font-size: 12px; color: #64748b; background: #f8fafc; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Регистрация на дисциплины</h1>
        <div class="subtitle">Система управления учебным процессом</div>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-info">Сеанс завершён из-за бездействия.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login.php">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['login_csrf']) ?>">
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required value="student@atu.edu.kz">
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary">Войти в систему</button>
        </form>

        <div class="hints">
            <b>Тестовые доступы:</b><br>
            • Студент: <code>student@atu.edu.kz</code> / <code>Student123!</code><br>
            • Регистратор: <code>registrar@atu.edu.kz</code> / <code>Registrar123!</code>
        </div>
    </div>
</body>
</html>