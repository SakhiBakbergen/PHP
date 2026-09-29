<?php

declare(strict_types=1);

require __DIR__ . "/functions.php";

/** Безопасное чтение строки из GET. */
function getString(string $key): string
{
    $v = $_GET[$key] ?? "";
    return is_string($v) ? $v : "";
}

$raw = require __DIR__ . "/catalog.php";

$query    = getString("q");
$type     = getString("type");
$maxRaw   = getString("max_price");
$maxPrice = is_numeric($maxRaw) ? (float) $maxRaw : null;
$onlyAvail = getString("available") === "1";
$sort     = in_array(getString("sort"), ["asc", "desc"], true) ? getString("sort") : "none";
$discRaw  = getString("discount");
$discount = is_numeric($discRaw) ? (float) $discRaw / 100 : 0.0;

$error = null;
$result = [];
$catalog = [];
$types = [];

try {
    validateCatalog($raw);
    $catalog = normalizeCatalog($raw);           // копия, $raw не меняется
    $types = array_values(array_unique(array_column($catalog, "type")));
    sort($types);

    $result = searchProducts($catalog, $query);
    if ($type !== "") {
        $result = filterByType($result, $type);
    }
    $result = filterCatalog($result, null, $maxPrice, $onlyAvail);
    $result = discounted($result, $discount);   // array_map
    if ($sort !== "none") {
        $result = sortByPrice($result, $sort === "asc");   // usort по копии
    }
} catch (InvalidArgumentException $ex) {
    $error = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Лабораторная 5 — Канцелярские товары</title>
<style>
    body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 24px 32px; color: #1f2937;
           background: linear-gradient(135deg, #eef2ff 0%, #fdf2f8 100%); min-height: 100vh; }
    h1 { color: #4338ca; border-bottom: 4px solid #f59e0b; display: inline-block; padding-bottom: 6px; }
    h2 { color: #be185d; margin-top: 32px; }
    form { background: #fff; padding: 14px 16px; border-radius: 12px; border-left: 6px solid #6366f1;
           box-shadow: 0 2px 8px rgba(99,102,241,.2); }
    form > * { margin: 4px 8px 4px 0; }
    input[type=text], input[type=number], select { padding: 7px 10px; border: 1px solid #a5b4fc;
           border-radius: 8px; background: #f5f7ff; }
    input:focus, select:focus { outline: 2px solid #f59e0b; }
    button { background: linear-gradient(135deg, #6366f1, #ec4899); color: #fff; border: 0;
           padding: 8px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    button:hover { filter: brightness(1.1); }
    table { border-collapse: collapse; margin: 14px 0; background: #fff; border-radius: 12px;
            overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.12); }
    th, td { padding: 9px 14px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; }
    tr:nth-child(even) td { background: #f5f3ff; }
    tr:hover td { background: #fef3c7; }
    p { background: #ecfdf5; border-left: 5px solid #10b981; padding: 8px 14px; border-radius: 8px;
        display: inline-block; margin: 6px 0; }
    .err { color: #b00020; background: #fee2e2; border-left-color: #dc2626; font-weight: bold; }
</style>
</head>
<body>
<h1>Каталог: канцелярские товары</h1>

<form method="get">
    <input type="text" name="q" placeholder="Поиск по названию" value="<?= e($query) ?>">
    <select name="type">
        <option value="">Все типы</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= normalizeText($type) === normalizeText($t) ? "selected" : "" ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" step="0.01" min="0" name="max_price" placeholder="Макс. цена" value="<?= e($maxRaw) ?>">
    <label><input type="checkbox" name="available" value="1" <?= $onlyAvail ? "checked" : "" ?>> Только в наличии</label>
    <select name="sort">
        <option value="none" <?= $sort === "none" ? "selected" : "" ?>>Без сортировки</option>
        <option value="asc"  <?= $sort === "asc"  ? "selected" : "" ?>>Цена ↑</option>
        <option value="desc" <?= $sort === "desc" ? "selected" : "" ?>>Цена ↓</option>
    </select>
    <input type="number" step="1" min="0" max="100" name="discount" placeholder="Скидка %" value="<?= e($discRaw) ?>">
    <button type="submit">Применить</button>
</form>

<?php if ($error !== null): ?>
    <p class="err">Ошибка данных: <?= e($error) ?></p>
<?php else: ?>
    <?= renderTable($result) ?>
    <p>Найдено позиций: <?= count($result) ?></p>
    <p>Стоимость запасов (выборка): <?= number_format(inventoryValue($result), 2, ".", " ") ?> тг</p>
    <p>Стоимость запасов (весь каталог): <?= number_format(inventoryValue($catalog), 2, ".", " ") ?> тг,
       единиц на складе: <?= totalStock($catalog) ?></p>

    <h2>Статистика по категориям</h2>
    <table>
        <tr><th>Категория</th><th>Позиций</th><th>Остаток</th><th>Стоимость запасов, тг</th></tr>
        <?php foreach (categoryStats($catalog) as $cat => $s): ?>
            <tr>
                <td><?= e((string) $cat) ?></td>
                <td><?= $s["count"] ?></td>
                <td><?= $s["stock"] ?></td>
                <td><?= number_format($s["value"], 2, ".", " ") ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Заканчиваются (остаток &lt; 30)</h2>
    <?= renderTable(lowStock($catalog, 30)) ?>
<?php endif; ?>
</body>
</html>