<?php
declare(strict_types=1);

require_once 'functions.php';

// Замыкание, захватывающее бонус через use
$bonus = 5.0;
$withBonus = function (float $score) use ($bonus): float {
    assertGrade($score);
    return min(100.0, $score + $bonus);
};

// Callback-функция (нормализация / ограничение баллов)
$normalizeScores = fn(float $score): float => round(min(100.0, max(0.0, $score)), 2);

// Исходные данные
$studentName = "Алия";
$grades = [78.0, 84.0, 91.0];
$weights = [0.2, 0.3, 0.5];

// Применение функций
$rawWeighted = weightedScore($grades, $weights);
$finalScore = $withBonus($rawWeighted);
$htmlResult = htmlspecialchars(formatResult($studentName, $finalScore, "с учетом бонуса"));

// Пример обработки массива с помощью стрелочной функции (callback)
$classScores = [45.0, 60.0, 72.0, 88.0, 95.0];
$passingScores = filterPassing($classScores, 50.0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа 4 - Вариант 1</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; color: #333; }
        .card { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 600px; }
        h1 { color: #2c3e50; font-size: 22px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Результаты анализа успеваемости</h1>
        <p><strong>Отчет:</strong> <?= $htmlResult ?></p>
        <p><strong>Прошедшие порог (>=50):</strong> <?= implode(', ', $passingScores) ?></p>
    </div>
</body>
</html>