<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/functions.php';

try {
    $items = loadMetadata();
} catch (Throwable $e) {
    $items = [];
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр лабораторных отчётов</title>
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-color: #1f2937;
            --border-color: #e5e7eb;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        h1 {
            margin-top: 0;
            font-size: 24px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        form {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            background: #fff;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: var(--primary-hover);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #4b5563;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        .btn-download {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .btn-download:hover {
            text-decoration: underline;
        }
        .empty-text {
            text-align: center;
            color: #6b7280;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Реестр лабораторных отчётов (Вариант 1)</h1>

        <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success">Отчёт успешно сохранен и добавлен в реестр!</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">Загрузка отклонена. Проверьте размер файла (до 5 МиБ), формат PDF или заполнение полей.</div>
        <?php endif; ?>

        <form method="post" action="/upload.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="discipline">Дисциплина:</label>
                <input type="text" id="discipline" name="discipline" placeholder="Например: Программирование на PHP" required>
            </div>
            <div class="form-group">
                <label for="document">PDF файл (до 5 МиБ):</label>
                <input type="file" id="document" name="document" accept="application/pdf,.pdf" required>
            </div>
            <button type="submit">Загрузить отчёт</button>
        </form>

        <h2>Список загруженных отчётов</h2>
        <table>
            <thead>
                <tr>
                    <th>Дисциплина</th>
                    <th>Имя файла</th>
                    <th>Тип</th>
                    <th>Размер</th>
                    <th>Дата загрузки</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="empty-text">Пока нет ни одного загруженного отчёта.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= h((string)($item['discipline'] ?? '')) ?></td>
                        <td><?= h((string)($item['original_name'] ?? '')) ?></td>
                        <td><?= h((string)($item['mime'] ?? '')) ?></td>
                        <td><?= round((int)($item['size'] ?? 0) / 1024, 2) ?> КБ</td>
                        <td><?= h((string)($item['uploaded_at'] ?? '')) ?></td>
                        <td><a href="/download.php?id=<?= h((string)$item['id']) ?>" class="btn-download">Скачать</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>