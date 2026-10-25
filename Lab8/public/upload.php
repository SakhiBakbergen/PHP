<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$storedPath = null;

try {
    // Получаем и проверяем поле дисциплины (Вариант 1)
    $discipline = trim($_POST['discipline'] ?? '');
    if ($discipline === '') {
        throw new RuntimeException('Укажите название дисциплины.');
    }

    $file = uploadedFile('document');
    $mime = pdfMime($file['tmp_name']);

    $id = bin2hex(random_bytes(16));
    $storedName = $id . '.pdf';
    $storedPath = UPLOAD_DIR . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    appendMetadata([
        'id' => $id,
        'discipline' => $discipline, // Сохраняем дисциплину
        'original_name' => basename((string)$file['name']),
        'stored_name' => $storedName,
        'mime' => $mime,
        'size' => filesize($storedPath),
        'sha256' => hash_file('sha256', $storedPath),
        'uploaded_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ]);

    header('Location: /index.php?ok=1');
    exit;

} catch (Throwable $e) {
    // Откат: удаляем файл, если он успел записаться на диск при сбое в JSON
    if ($storedPath !== null && is_file($storedPath)) {
        unlink($storedPath);
    }
    
    error_log($e->getMessage());
    header('Location: /index.php?error=upload');
    exit;
}