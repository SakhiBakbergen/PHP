<?php
declare(strict_types=1);

/**
 * Централизованная проверка диапазона оценки (от 0 до 100)
 */
function assertGrade(float $grade, string $name = "Оценка"): void
{
    if ($grade < 0.0 || $grade > 100.0) {
        throw new InvalidArgumentException("{$name} должна быть в диапазоне от 0 до 100.");
    }
}

/**
 * Расчет средневзвешенного балла
 */
function weightedScore(array $grades, array $weights): float
{
    if ($grades === [] || count($grades) !== count($weights)) {
        throw new InvalidArgumentException("Наборы оценок и весов должны быть непустыми и одинаковой длины.");
    }

    $sum = 0.0;
    $weightSum = 0.0;

    foreach ($grades as $i => $grade) {
        $g = (float)$grade;
        $w = (float)$weights[$i];
        
        assertGrade($g, "Оценка под индексом {$i}");
        
        if ($w < 0.0) {
            throw new InvalidArgumentException("Вес не может быть отрицательным.");
        }
        
        $sum += $g * $w;
        $weightSum += $w;
    }

    if ($weightSum <= 0.0) {
        throw new RuntimeException("Сумма весов должна быть больше нуля.");
    }

    return round($sum / $weightSum, 2);
}

/**
 * Классификация баллов по категориям
 */
function classifyScore(float $score): string
{
    assertGrade($score, "Итоговый балл");

    return match (true) {
        $score >= 90.0 => "отлично",
        $score >= 75.0 => "хорошо",
        $score >= 50.0 => "удовлетворительно",
        default => "неудовлетворительно",
    };
}

/**
 * Фильтрация студентов, преодолевших пороговый балл
 */
function filterPassing(array $scores, float $threshold = 50.0): array
{
    assertGrade($threshold, "Порог");

    return array_values(array_filter(
        $scores,
        fn(float $score): bool => $score >= $threshold
    ));
}

/**
 * Форматирование итогового отчета по студенту
 */
function formatResult(string $student, float $score, ?string $comment = null): string
{
    if (trim($student) === "") {
        throw new InvalidArgumentException("Имя студента не может быть пустым.");
    }

    $text = "Студент: {$student} | Балл: {$score} | Категория: " . classifyScore($score);

    return $comment === null ? $text : "{$text} ({$comment})";
}