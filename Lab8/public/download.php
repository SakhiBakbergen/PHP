<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/functions.php';

$id = $_GET['id'] ?? '';

// Валидация id (ровно 32 hex-символа)
if (!is_string($id) || !preg_match('/\A[a-f0-9]{32}\z/', $id)) {
    http_response_code(400);
    exit('Bad Request');
}

$item = null;
foreach (loadMetadata() as $candidate) {
    if (($candidate['id'] ?? null) === $id) {
        $item = $candidate;
        break;
    }
}

if (!is_array($item)) {
    http_response_code(404);
    exit('Not Found');
}

$path = UPLOAD_DIR . '/' . basename((string)$item['stored_name']);

if (!is_file($path)) {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename=\"document.pdf\"");
header('X-Content-Type-Options: nosniff');

readfile($path);