<?php

declare(strict_types=1);

require __DIR__ . "/functions.php";

if (PHP_SAPI !== "cli") {
    header("Content-Type: text/plain; charset=UTF-8");
}

$catalog = require __DIR__ . "/catalog.php";
$valid = $catalog[0];
$results = [];

/** Запуск теста: $fn возвращает [bool ok, string actual]. */
function runTest(string $scenario, string $checks, string $expected, callable $fn): void
{
    global $results;
    try {
        [$ok, $actual] = $fn();
    } catch (Throwable $t) {
        $ok = false;
        $actual = "Непредвиденное исключение " . get_class($t) . ": " . $t->getMessage();
    }
    $results[] = compact("scenario", "checks", "expected", "actual", "ok");
}

/** Ожидает InvalidArgumentException с подстрокой в сообщении. */
function expectInvalid(callable $action, string $needle): array
{
    try {
        $action();
        return [false, "Исключение не выброшено"];
    } catch (InvalidArgumentException $e) {
        return [mb_stripos($e->getMessage(), $needle, 0, "UTF-8") !== false,
                "InvalidArgumentException: " . $e->getMessage()];
    }
}

function ids(array $items): array
{
    return array_column($items, "id");
}

// 1. Корректный каталог
runTest("1. Корректный каталог", "основной поток: валидация, таблица, итоги",
    "без ошибок, 10 строк в таблице, запас = сумма price*stock", function () use ($catalog) {
        validateCatalog($catalog);
        $norm = normalizeCatalog($catalog);
        $html = renderTable($norm);
        $rows = substr_count($html, "<tr>") - 1;
        $expected = 0.0;
        foreach ($catalog as $p) { $expected += $p["price"] * $p["stock"]; }
        $actual = inventoryValue($norm);
        return [count($catalog) >= 8 && $rows === count($catalog) && abs($actual - $expected) < 0.01,
                "строк: $rows, запас: $actual (ожидалось " . round($expected, 2) . ")"];
    });

// 2. Поиск в разном регистре
runTest("2. Поиск в разном регистре", "UTF-8 и mb_stripos",
    "одинаковый непустой набор результатов", function () use ($catalog) {
        $a = ids(searchProducts($catalog, "ручка"));
        $b = ids(searchProducts($catalog, "РУЧКА"));
        $c = ids(searchProducts($catalog, "РуЧкА"));
        return [$a !== [] && $a === $b && $b === $c, "ids: " . implode(",", $a) . " | " . implode(",", $b) . " | " . implode(",", $c)];
    });

// 3. Лишние пробелы
runTest("3. Лишние пробелы", "нормализация пробелов, регистра, цвета",
    "\"ручка синяя\"; цвет \" СИНИЙ \" -> \"Синий\"", function () use ($catalog) {
        $text = normalizeText("  Ручка   Синяя \t ");
        $color = normalizeCatalog([array_merge($catalog[0], ["color" => " СИНИЙ  "])])[0]["color"];
        return [$text === "ручка синяя" && $color === "Синий", "\"$text\"; \"$color\""];
    });

// 4. Пустой результат
runTest("4. Пустой результат фильтра", "работа с пустым массивом",
    "пустой список без ошибки; таблица с сообщением", function () use ($catalog) {
        $r1 = filterCatalog($catalog, "Несуществующая категория");
        $r2 = filterCatalog([], null, null, false);
        $r3 = searchProducts($catalog, "zzz-нет-такого");
        $html = renderTable([]);
        return [$r1 === [] && $r2 === [] && $r3 === [] && str_contains($html, "не найдены") && inventoryValue([]) === 0.0,
                "count: " . count($r1) . ", " . count($r2) . ", " . count($r3)];
    });

// 5. Граничная цена 0
runTest("5. Граничная цена 0", "нижняя граница",
    "значение принято, запас = 0.0", function () use ($valid) {
        $p = array_merge($valid, ["price" => 0.0]);
        validateProduct($p);
        $v = inventoryValue([$p]);
        return [$v === 0.0, "валидация пройдена, запас = $v"];
    });

// 6. Отрицательная цена
runTest("6. Отрицательная цена", "валидация",
    "InvalidArgumentException (цена)", fn() =>
        expectInvalid(fn() => validateProduct(array_merge($valid, ["price" => -1.0])), "цена"));

// 7. Отсутствующий ключ
runTest("7. Отсутствующий ключ", "структура записи",
    "исключение с именем поля price", function () use ($valid) {
        $p = $valid;
        unset($p["price"]);
        return expectInvalid(fn() => validateProduct($p), "price");
    });

// 8. Некорректная дата
runTest("8. Некорректная дата", "проверка формата",
    "исключение для 2026-02-30 и 01.09.2026", function () use ($valid) {
        [$ok1, $m1] = expectInvalid(fn() => validateProduct(array_merge($valid, ["created_at" => "2026-02-30"])), "дата");
        [$ok2, $m2] = expectInvalid(fn() => validateProduct(array_merge($valid, ["created_at" => "01.09.2026"])), "дата");
        return [$ok1 && $ok2, "$m1 | $m2"];
    });

// 9. HTML-тег
runTest("9. Строка с HTML-тегом", "экранирование",
    "тег отображается как текст (&lt;script&gt;)", function () use ($valid) {
        $p = array_merge($valid, ["name" => "<script>alert(1)</script> \"Ручка\"", "type" => "<b>Ручка</b>"]);
        $html = renderTable([$p]);
        $ok = str_contains($html, "&lt;script&gt;") && !str_contains($html, "<script>")
            && str_contains($html, "&lt;b&gt;") && !str_contains($html, "<b>")
            && str_contains($html, "&quot;Ручка&quot;");
        return [$ok, $ok ? "теги экранированы" : "найден неэкранированный тег"];
    });

// 10. Сортировка одинаковых цен
runTest("10. Сортировка одинаковых цен", "компаратор",
    "все записи сохранены, порядок по цене верный, исходный массив не изменён", function () use ($catalog) {
        $before = $catalog;
        $asc = sortByPrice($catalog, true);
        $desc = sortByPrice($catalog, false);
        $sameIds = ids($asc);
        $orig = ids($catalog);
        sort($sameIds);
        sort($orig);
        $prices = array_column($asc, "price");
        $sorted = $prices;
        sort($sorted);
        $dups = count($prices) - count(array_unique($prices));
        $ok = $sameIds === $orig && count($desc) === count($catalog)
            && $prices === $sorted && $catalog === $before && $dups > 0;
        return [$ok, "всего: " . count($asc) . ", дублей цен: $dups, порядок ASC: " . implode(",", $prices)];
    });

// 11. Фильтр по типу (вариант 11)
runTest("11. Фильтр по типу", "специфика варианта: тип товара без учета регистра/пробелов",
    "\"  РУЧКА \" -> 2 записи, все с типом Ручка", function () use ($catalog) {
        $norm = normalizeCatalog($catalog);
        $r = filterByType($norm, "  РУЧКА ");
        $allPen = count(array_filter($r, fn(array $p): bool => $p["type"] === "Ручка")) === count($r);
        return [count($r) === 2 && $allPen, "найдено: " . count($r) . ", ids: " . implode(",", ids($r))];
    });

// 12. map + неизменность исходных данных
runTest("12. Скидка (array_map)", "final_price в копии, исходник не изменён; неверная скидка",
    "цена 450 при 10% = 405.00; исключение при 1.5", function () use ($catalog) {
        $before = $catalog;
        $d = discounted($catalog, 0.10);
        [$okEx, $msg] = expectInvalid(fn() => discounted($catalog, 1.5), "скидка");
        $ok = $d[0]["final_price"] === 405.0 && !array_key_exists("final_price", $catalog[0])
            && $catalog === $before && $okEx;
        return [$ok, "final_price[0] = " . $d[0]["final_price"] . "; $msg"];
    });

// 13. groupBy и статистика по категориям (повышенная сложность)
runTest("13. groupBy и статистика", "группировка по категориям",
    "3 категории, сумма позиций = 10, сумма стоимости = общий запас", function () use ($catalog) {
        $stats = categoryStats($catalog);
        $cnt = array_sum(array_column($stats, "count"));
        $val = round(array_sum(array_column($stats, "value")), 2);
        $ok = count($stats) === 3 && $cnt === count($catalog) && abs($val - inventoryValue($catalog)) < 0.01;
        return [$ok, "категорий: " . count($stats) . ", позиций: $cnt, стоимость: $val"];
    });

// 14. Дубликат id
runTest("14. Повторяющийся id", "validateCatalog",
    "InvalidArgumentException (id)", fn() =>
        expectInvalid(fn() => validateCatalog([$valid, $valid]), "id"));

// Вывод
$passed = 0;
foreach ($results as $i => $r) {
    $passed += $r["ok"] ? 1 : 0;
    echo ($r["ok"] ? "[PASS] " : "[FAIL] ") . $r["scenario"] . "\n";
    echo "   Проверяется: " . $r["checks"] . "\n";
    echo "   Ожидается:   " . $r["expected"] . "\n";
    echo "   Фактически:  " . $r["actual"] . "\n\n";
}
echo "Итого: $passed из " . count($results) . " тестов пройдено\n";
exit($passed === count($results) ? 0 : 1);