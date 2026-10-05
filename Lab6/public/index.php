<?php
declare(strict_types=1);

// Функция экранирования HTML-вывода (Защита от XSS)
function h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Функция безопасного извлечения строковых значений
function postString(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : null;
}

// Разрешённые списки (Allow-lists)
$allowedGroups = ['ИС-24-21', 'ИС-24-22', 'ИС-24-23'];
$allowedSubjects = ['Программирование на PHP', 'Базы данных', 'Сети и телекоммуникации'];
$allowedFormats = ['offline', 'online'];

// Начальные значения для формы
$values = [
    'full_name' => '',
    'email'     => '',
    'group'     => '',
    'subject'   => '',
    'format'    => '',
];

$errors = [];
$success = false;

// Проверка метода запроса (POST)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // 1. Извлечение
    $fullName = postString('full_name');
    $email    = postString('email');
    $group    = postString('group');
    $subject  = postString('subject');
    $format   = postString('format');

    // 2. Нормализация
    $values['full_name'] = $fullName === null ? '' : trim($fullName);
    $values['email']     = $email === null ? '' : trim($email);
    $values['group']     = $group ?? '';
    $values['subject']   = $subject ?? '';
    $values['format']    = $format ?? '';

    // 3. Серверная валидация
    // ФИО
    if ($fullName === null || $values['full_name'] === '') {
        $errors['full_name'] = 'Укажите ФИО.';
    } elseif (mb_strlen($values['full_name']) > 100) {
        $errors['full_name'] = 'ФИО не должно превышать 100 символов.';
    }

    // E-mail
    if ($email === null || $values['email'] === '') {
        $errors['email'] = 'Укажите электронную почту.';
    } elseif (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Введите корректный адрес электронной почты.';
    }

    // Группа (allow-list)
    if (!in_array($values['group'], $allowedGroups, true)) {
        $errors['group'] = 'Выберите учебную группу из списка.';
    }

    // Дисциплина (allow-list)
    if (!in_array($values['subject'], $allowedSubjects, true)) {
        $errors['subject'] = 'Выберите дисциплину из списка.';
    }

    // Формат (allow-list)
    if (!in_array($values['format'], $allowedFormats, true)) {
        $errors['format'] = 'Выберите формат участия.';
    }

    // Чекбокс согласия (Бизнес-правило)
    if (!isset($_POST['agreement']) || $_POST['agreement'] !== '1') {
        $errors['agreement'] = 'Подтвердите согласие с правилами.';
    }

    // Статус успешной отправки
    $success = ($errors === []);
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация на дисциплину</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #1f2937;
            --border: #d1d5db;
            --error-bg: #fef2f2;
            --error-border: #fca5a5;
            --error-text: #dc2626;
            --success-bg: #ecfdf5;
            --success-border: #6ee7b7;
            --success-text: #047857;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 520px;
            padding: 32px;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
            text-align: center;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            background-color: #f9fafb;
            transition: all 0.2s ease-in-out;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        fieldset {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            background-color: #f9fafb;
        }

        legend {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0 6px;
            color: #4b5563;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 6px;
        }

        .radio-label, .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .error {
            color: var(--error-text);
            background-color: var(--error-bg);
            border: 1px solid var(--error-border);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.825rem;
            margin-top: 6px;
        }

        .success-msg {
            background-color: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
            line-height: 1.5;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background-color: var(--primary-hover);
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Регистрация на дисциплину</h1>

    <?php if ($success): ?>
        <div class="success-msg">
            🎉 Заявка на дисциплину «<strong><?= h($values['subject']) ?></strong>» успешно принята для <strong><?= h($values['full_name']) ?></strong>!
        </div>
    <?php else: ?>
        <form method="post" action="">
            <!-- ФИО -->
            <div class="field">
                <label for="full_name">ФИО</label>
                <input type="text" id="full_name" name="full_name" maxlength="100" placeholder="Сахи Бакберген" value="<?= h($values['full_name']) ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="error"><?= h($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <!-- E-mail -->
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="example@mail.com" value="<?= h($values['email']) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?= h($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Группа -->
            <div class="field">
                <label for="group">Учебная группа</label>
                <select id="group" name="group" required>
                    <option value="">Выберите группу</option>
                    <?php foreach ($allowedGroups as $item): ?>
                        <option value="<?= h($item) ?>" <?= $values['group'] === $item ? 'selected' : '' ?>>
                            <?= h($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['group'])): ?>
                    <div class="error"><?= h($errors['group']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Дисциплина -->
            <div class="field">
                <label for="subject">Дисциплина</label>
                <select id="subject" name="subject" required>
                    <option value="">Выберите дисциплину</option>
                    <?php foreach ($allowedSubjects as $item): ?>
                        <option value="<?= h($item) ?>" <?= $values['subject'] === $item ? 'selected' : '' ?>>
                            <?= h($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['subject'])): ?>
                    <div class="error"><?= h($errors['subject']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Формат -->
            <div class="field">
                <fieldset>
                    <legend>Формат обучения</legend>
                    <div class="radio-group">
                        <?php foreach ($allowedFormats as $item): ?>
                            <label class="radio-label">
                                <input type="radio" name="format" value="<?= h($item) ?>" <?= $values['format'] === $item ? 'checked' : '' ?>>
                                <?= h($item) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <?php if (isset($errors['format'])): ?>
                    <div class="error"><?= h($errors['format']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Согласие -->
            <div class="field">
                <label class="checkbox-label">
                    <input type="checkbox" name="agreement" value="1">
                    Я согласен с правилами регистрации
                </label>
                <?php if (isset($errors['agreement'])): ?>
                    <div class="error"><?= h($errors['agreement']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit">Отправить заявку</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>