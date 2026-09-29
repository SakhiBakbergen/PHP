<?php

declare(strict_types=1);

/** Обязательные ключи записи каталога. */
const REQUIRED_KEYS = ["id", "name", "category", "type", "color", "price", "stock", "created_at"];

/** Убирает крайние и повторяющиеся пробелы (UTF-8), регистр не меняет. */
function cleanText(string $value): string
{
    $value = trim($value);
    return preg_replace("/\s+/u", " ", $value) ?? $value;
}

/** Нормализация: чистка пробелов + нижний регистр (UTF-8). */
function normalizeText(string $value): string
{
    return mb_strtolower(cleanText($value), "UTF-8");
}

/** Первая буква заглавная, остальные строчные (UTF-8). */
function mbUcfirst(string $value): string
{
    $first = mb_strtoupper(mb_substr($value, 0, 1, "UTF-8"), "UTF-8");
    return $first . mb_substr($value, 1, null, "UTF-8");
}

/** Проверка одной записи: ключи, типы, диапазоны, дата. */
function validateProduct(array $product): void
{
    foreach (REQUIRED_KEYS as $key) {
        if (!array_key_exists($key, $product)) {
            throw new InvalidArgumentException("Отсутствует поле: $key");
        }
    }
    if (!is_int($product["id"]) || $product["id"] <= 0) {
        throw new InvalidArgumentException("Некорректный id");
    }
    foreach (["name" => "название", "category" => "категория", "type" => "тип", "color" => "цвет"] as $key => $label) {
        if (!is_string($product[$key]) || cleanText($product[$key]) === "") {
            throw new InvalidArgumentException("Пустое или нестроковое поле: $key ($label)");
        }
    }
    if (!is_int($product["price"]) && !is_float($product["price"])) {
        throw new InvalidArgumentException("Некорректная цена: price должно быть числом");
    }
    if ((float) $product["price"] < 0) {
        throw new InvalidArgumentException("Некорректная цена: price отрицательная");
    }
    if (!is_int($product["stock"]) || $product["stock"] < 0) {
        throw new InvalidArgumentException("Некорректный остаток: stock");
    }
    $date = is_string($product["created_at"])
        ? DateTimeImmutable::createFromFormat("!Y-m-d", $product["created_at"])
        : false;
    if ($date === false || $date->format("Y-m-d") !== $product["created_at"]) {
        throw new InvalidArgumentException("Некорректная дата created_at (ожидается Y-m-d)");
    }
}

/** Проверка всего каталога: каждая запись + уникальность id. */
function validateCatalog(array $items): void
{
    $ids = [];
    foreach ($items as $index => $product) {
        if (!is_array($product)) {
            throw new InvalidArgumentException("Запись №" . ($index + 1) . ": ожидается массив");
        }
        try {
            validateProduct($product);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Запись №" . ($index + 1) . ": " . $e->getMessage(), 0, $e);
        }
        if (isset($ids[$product["id"]])) {
            throw new InvalidArgumentException("Повторяющийся id: " . $product["id"]);
        }
        $ids[$product["id"]] = true;
    }
}

/** Возвращает НОВЫЙ каталог с нормализованными строками (исходный не меняется). */
function normalizeCatalog(array $items): array
{
    return array_map(function (array $p): array {
        $p["name"]     = cleanText((string) $p["name"]);
        $p["category"] = cleanText((string) $p["category"]);
        $p["type"]     = mbUcfirst(normalizeText((string) $p["type"]));
        $p["color"]    = mbUcfirst(normalizeText((string) $p["color"]));
        return $p;
    }, $items);
}

/** Поиск по части названия без учета регистра. */
function searchProducts(array $items, string $query): array
{
    $query = normalizeText($query);
    if ($query === "") {
        return $items;
    }
    return array_values(array_filter($items, fn(array $p): bool =>
        mb_stripos(normalizeText((string) $p["name"]), $query, 0, "UTF-8") !== false
    ));
}

/** Комбинированная фильтрация: категория, максимальная цена, наличие. */
function filterCatalog(array $items, ?string $category = null,
                       ?float $maxPrice = null, bool $onlyAvailable = false): array
{
    $category = $category === null ? null : normalizeText($category);
    return array_values(array_filter($items, function (array $p) use ($category, $maxPrice, $onlyAvailable): bool {
        if ($category !== null && normalizeText((string) $p["category"]) !== $category) {
            return false;
        }
        if ($maxPrice !== null && (float) $p["price"] > $maxPrice) {
            return false;
        }
        return !$onlyAvailable || $p["stock"] > 0;
    }));
}

/** Фильтр по типу канцелярского товара (специфика варианта 11). */
function filterByType(array $items, string $type): array
{
    $type = normalizeText($type);
    return array_values(array_filter($items, fn(array $p): bool =>
        normalizeText((string) $p["type"]) === $type
    ));
}

/** Товары с низким остатком (остаток < порога). */
function lowStock(array $items, int $threshold): array
{
    return array_values(array_filter($items, fn(array $p): bool => $p["stock"] < $threshold));
}

/** Сортировка КОПИИ каталога по цене. */
function sortByPrice(array $items, bool $ascending = true): array
{
    usort($items, fn(array $a, array $b): int => $ascending
        ? $a["price"] <=> $b["price"]
        : $b["price"] <=> $a["price"]);
    return $items;
}

/** Стоимость товарного запаса: сумма price * stock. */
function inventoryValue(array $items): float
{
    return round(array_reduce($items, fn(float $sum, array $p): float =>
        $sum + (float) $p["price"] * (int) $p["stock"], 0.0), 2);
}

/** Суммарное количество единиц на складе. */
function totalStock(array $items): int
{
    return (int) array_reduce($items, fn(int $sum, array $p): int => $sum + (int) $p["stock"], 0);
}

/** Добавляет final_price в КОПИИ записей (array_map). */
function discounted(array $items, float $rate): array
{
    if ($rate < 0 || $rate > 1) {
        throw new InvalidArgumentException("Неверная скидка");
    }
    return array_map(function (array $p) use ($rate): array {
        $p["final_price"] = round((float) $p["price"] * (1 - $rate), 2);
        return $p;
    }, $items);
}

/** Группировка записей по ключу, возвращаемому callback-функцией. */
function groupBy(array $items, callable $keySelector): array
{
    $groups = [];
    foreach ($items as $item) {
        $groups[(string) $keySelector($item)][] = $item;
    }
    return $groups;
}

/** Статистика по категориям: позиции, остаток, стоимость запасов. */
function categoryStats(array $items): array
{
    $groups = groupBy($items, fn(array $p): string => (string) $p["category"]);
    $stats = [];
    foreach ($groups as $category => $group) {
        $stats[$category] = [
            "count" => count($group),
            "stock" => totalStock($group),
            "value" => inventoryValue($group),
        ];
    }
    return $stats;
}

/** Экранирование текста для HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

/** Безопасная HTML-таблица. */
function renderTable(array $items): string
{
    $withFinal = $items !== [] && array_key_exists("final_price", reset($items));
    $html = "<table><tr><th>ID</th><th>Название</th><th>Категория</th><th>Тип</th><th>Цвет</th>"
          . "<th>Цена</th>" . ($withFinal ? "<th>Цена со скидкой</th>" : "") . "<th>Остаток</th></tr>";

    if ($items === []) {
        $colspan = $withFinal ? 8 : 7;
        return $html . "<tr><td colspan=\"$colspan\">Товары не найдены</td></tr></table>";
    }
    foreach ($items as $p) {
        $html .= "<tr><td>" . (int) $p["id"] . "</td>"
              . "<td>" . e((string) $p["name"]) . "</td>"
              . "<td>" . e((string) $p["category"]) . "</td>"
              . "<td>" . e((string) $p["type"]) . "</td>"
              . "<td>" . e((string) $p["color"]) . "</td>"
              . "<td>" . number_format((float) $p["price"], 2, ".", " ") . "</td>"
              . ($withFinal ? "<td>" . number_format((float) $p["final_price"], 2, ".", " ") . "</td>" : "")
              . "<td>" . (int) $p["stock"] . "</td></tr>";
    }
    return $html . "</table>";
}