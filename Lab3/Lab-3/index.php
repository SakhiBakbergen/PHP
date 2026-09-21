<?php
declare(strict_types=1);

// Исходные данные посылок (масса в кг)
$packages = [5.2, 0.1, null, 12.5, 48.0, 3.8, 25.1];

try {
    // 1. Проверка на пустой массив
    if (empty($packages)) {
        throw new RuntimeException("Массив данных о посылках пуст.");
    }

    // 2. Отфильтровываем элементы null
    $validPackages = array_filter($packages, static fn($weight) => $weight !== null);

    if (empty($validPackages)) {
        throw new RuntimeException("Нет валидных посылок для расчета.");
    }

    // 3. Валидация типов и диапазона значений (0.1 - 50 кг)
    array_walk($validPackages, static function ($weight, $key) {
        if (!is_int($weight) && !is_float($weight)) {
            throw new InvalidArgumentException("В позиции {$key} передано некорректное значение.");
        }
        if ($weight < 0.1 || $weight > 50.0) {
            throw new InvalidArgumentException("Масса посылки в позиции {$key} ({$weight} кг) выходит за допустимый диапазон [0.1 - 50 кг].");
        }
    });

    // 4. Расчет необходимых параметров
    $count = count($validPackages);
    $totalWeight = array_sum($validPackages);
    $maxWeight = max($validPackages);
    $averageWeight = round($totalWeight / $count, 2);

    // 5. Определение категории
    $tariffCategory = match (true) {
        $averageWeight <= 2.0  => "Легкие отправления (Минимальный тариф)",
        $averageWeight <= 10.0 => "Стандартные посылки (Базовый тариф)",
        $averageWeight <= 30.0 => "Тяжелые посылки (Повышенный тариф)",
        default                => "Крупногабаритный груз (Максимальный тариф)",
    };

    // Вывод результатов
    echo "<h2>Результаты обработки посылок</h2>";
    echo "<p><b>Обработано посылок:</b> {$count} шт.</p>";
    echo "<p><b>Общая масса:</b> {$totalWeight} кг</p>";
    echo "<p><b>Средняя масса:</b> {$averageWeight} кг</p>";
    echo "<p><b>Максимальная масса:</b> {$maxWeight} кг</p>";
    echo "<p><b>Тарифная категория:</b> {$tariffCategory}</p>";

} catch (InvalidArgumentException | RuntimeException $e) {
    echo "<h3 style='color: red;'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</h3>";
} finally {
    echo "<p><i>Обработка данных завершена.</i></p>";
}