<?php

declare(strict_types=1);

// 1. Константа комиссии банка в процентах (Вариант 8)
const BANK_COMMISSION_PERCENT = 1.5;

// 2. Исходные данные и скалярные типы
$studentName = "Иван Иванов";      // string
$course = 3;                        // int
$amountKzt = 150000.0;              // float (Сумма в KZT)
$exchangeRate = 475.50;             // float (Курс USD/KZT)
$isOperationAllowed = true;         // bool

// 3. Расчеты
$commissionAmount = $amountKzt * (BANK_COMMISSION_PERCENT / 100);
$effectiveKzt = $amountKzt - $commissionAmount;
$dollars = $effectiveKzt / $exchangeRate;

// 4. Проверка условий через строгое сравнение (===)
$status = ($dollars > 0.0 && $isOperationAllowed === true)
    ? "Конвертация выполнена"
    : "Ошибка операции";

// 5. Явное преобразование типов
$rawRateInput = "475.50";
$intRate = (int) $rawRateInput;
$floatRate = (float) $rawRateInput;
$boolRate = (bool) $rawRateInput;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа №2 — Вариант 8</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Конвертер валют (KZT в USD)</h1>

    <h3>Сведения о студенте</h3>
    <p>Студент: <?= htmlspecialchars($studentName) ?>, Курс: <?= $course ?></p>

    <h3>Результат конвертации</h3>
    <table>
        <tr>
            <th>Параметр</th>
            <th>Значение</th>
        </tr>
        <tr>
            <td>Исходная сумма (KZT)</td>
            <td><?= number_format($amountKzt, 2, '.', ' ') ?> ₸</td>
        </tr>
        <tr>
            <td>Курс USD/KZT</td>
            <td><?= number_format($exchangeRate, 2, '.', ' ') ?> ₸</td>
        </tr>
        <tr>
            <td>Комиссия (<?= BANK_COMMISSION_PERCENT ?>%)</td>
            <td><?= number_format($commissionAmount, 2, '.', ' ') ?> ₸</td>
        </tr>
        <tr>
            <td>Получено (USD)</td>
            <td><strong><?= number_format($dollars, 2, '.', ' ') ?> $</strong></td>
        </tr>
        <tr>
            <td>Статус</td>
            <td><?= htmlspecialchars($status) ?></td>
        </tr>
    </table>

    <h3>Данные отладки типов (var_dump)</h3>
    <pre>
<?php
var_dump($amountKzt);
var_dump($exchangeRate);
var_dump($dollars);
var_dump($status);
?>
    </pre>
</body>
</html>