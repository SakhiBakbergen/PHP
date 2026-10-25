<?php
declare(strict_types=1);

const MAX_FILE_SIZE = 5 * 1024 * 1024; // Лимит 5 МиБ[cite: 4, 8]
const DATA_FILE = __DIR__ . '/../var/data/submissions.json';
const UPLOAD_DIR = __DIR__ . '/../var/uploads';

// Экранирование вывода для защиты от XSS[cite: 8]
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Проверка структуры $_FILES, кодов ошибок и размера[cite: 3, 4, 8]
function uploadedFile(string $field): array {
    $file = $_FILES[$field] ?? null;

    if (!is_array($file) || is_array($file['error'] ?? null)) {
        throw new RuntimeException('Некорректное поле файла.');
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Файл не загружен. Код: ' . $file['error']);
    }

    if (!is_int($file['size']) || $file['size'] < 1 || $file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('Недопустимый размер файла.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Источник не является HTTP-загрузкой.');
    }

    return $file;
}

// Проверка MIME-типа через Fileinfo (только PDF)[cite: 3, 4, 8]
function pdfMime(string $path): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);

    if ($mime !== 'application/pdf') {
        throw new RuntimeException('Разрешён только PDF.');
    }

    return $mime;
}

// Атомарное добавление метаданных в JSON под блокировкой[cite: 3, 5, 8]
function appendMetadata(array $item): void {
    $lockPath = DATA_FILE . '.lock';
    $lock = fopen($lockPath, 'c');

    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Не удалось заблокировать реестр.');
    }

    try {
        $json = is_file(DATA_FILE) ? file_get_contents(DATA_FILE) : '[]';
        if ($json === false) {
            throw new RuntimeException('Не удалось прочитать реестр.');
        }

        $items = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($items)) {
            throw new RuntimeException('Неверная структура реестра.');
        }

        $items[] = $item;

        $newJson = json_encode(
            $items,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $tmp = DATA_FILE . '.' . bin2hex(random_bytes(6)) . '.tmp';
        
        if (file_put_contents($tmp, $newJson, LOCK_EX) === false || !rename($tmp, DATA_FILE)) {
            @unlink($tmp);
            throw new RuntimeException('Не удалось обновить реестр.');
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// Чтение реестра с сортировкой по дате (требование Варианта 1)[cite: 5, 8]
function loadMetadata(): array {
    if (!is_file(DATA_FILE)) return [];
    $json = file_get_contents(DATA_FILE);
    if ($json === false) return [];
    
    $items = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($items)) return [];

    // Сортировка по дате (от новых к старым)
    usort($items, function ($a, $b) {
        return strcmp($b['uploaded_at'] ?? '', $a['uploaded_at'] ?? '');
    });

    return $items;
}