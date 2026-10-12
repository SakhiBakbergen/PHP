<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_name('DISC_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

const IDLE_TIMEOUT = 900;

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function loginUser(int $id, string $email, string $role): void {
    session_regenerate_id(true);
    unset($_SESSION['login_csrf']);
    
    $_SESSION['user_id'] = $id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        header('Location: /login.php');
        exit;
    }

    if (time() - $_SESSION['last_activity'] > IDLE_TIMEOUT) {
        logoutUser();
        header('Location: /login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

// Функции для работы со статус-файлом заявки
function getEnrollmentStatus(): string {
    $file = dirname(__DIR__) . '/src/status.json';
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(['status' => 'pending']));
    }
    $data = json_decode(file_get_contents($file), true);
    return $data['status'] ?? 'pending';
}

function setEnrollmentStatus(string $status): void {
    $file = dirname(__DIR__) . '/src/status.json';
    file_put_contents($file, json_encode(['status' => $status]));
}