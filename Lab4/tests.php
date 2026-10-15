<?php
declare(strict_types=1);

require_once 'functions.php';

echo "<h2>Запуск тестов для Варианта №1 (Успеваемость студентов)</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>№</th><th>Сценарий</th><th>Что проверяется</th><th>Ожидаемая реакция</th><th>Статус</th></tr>";

$tests = [
    [
        'name' => 'Обычные корректные данные',
        'callback' => fn() => weightedScore([80.0, 90.0], [0.5, 0.5]) === 85.0
    ],
    [
        'name' => 'Нижняя допустимая граница (0)',
        'callback' => fn() => classifyScore(0.0) === 'неудовлетворительно'
    ],
    [
        'name' => 'Верхняя допустимая граница (100)',
        'callback' => fn() => classifyScore(100.0) === 'отлично'
    ],
    [
        'name' => 'Значение ниже границы (-1)',
        'callback' => fn() => classifyScore(-1.0),
        'expectException' => InvalidArgumentException::class
    ],
    [
        'name' => 'Значение выше границы (101)',
        'callback' => fn() => classifyScore(101.0),
        'expectException' => InvalidArgumentException::class
    ],
    [
        'name' => 'Пустой массив оценок',
        'callback' => fn() => weightedScore([], []),
        'expectException' => InvalidArgumentException::class
    ],
    [
        'name' => 'Null в разрешенном параметре комментария',
        'callback' => fn() => formatResult("Иван", 75.0, null) === "Студент: Иван | Балл: 75 | Категория: хорошо"
    ],
    [
        'name' => 'Фильтрация массива (callback и условие)',
        'callback' => fn() => count(filterPassing([40.0, 60.0, 80.0], 50.0)) === 2
    ],
    [
        'name' => 'Неверный вес (отрицательный)',
        'callback' => fn() => weightedScore([80.0], [-0.5]),
        'expectException' => InvalidArgumentException::class
    ]
];

foreach ($tests as $i => $test) {
    $num = $i + 1;
    $status = "ПРОЙДЕН";
    $style = "color: green;";
    
    try {
        $result = $test['callback']();
        if (isset($test['expectException'])) {
            $status = "ОШИБКА: Ожидаемое исключение не было выброшено";
            $style = "color: red;";
        }
    } catch (Throwable $e) {
        if (isset($test['expectException']) && $e instanceof $test['expectException']) {
            $status = "ПРОЙДЕН (Исключение поймано)";
        } else {
            $status = "ОШИБКА: " . $e->getMessage();
            $style = "color: red;";
        }
    }

    echo "<tr>";
    echo "<td>{$num}</td>";
    echo "<td>{$test['name']}</td>";
    echo "<td>" . (isset($test['expectException']) ? 'Валидация / Исключение' : 'Основной контракт') . "</td>";
    echo "<td>" . (isset($test['expectException']) ? 'Исключение' : 'Успешный расчет') . "</td>";
    echo "<td style='{$style}'><strong>{$status}</strong></td>";
    echo "</tr>";
}

echo "</table>";