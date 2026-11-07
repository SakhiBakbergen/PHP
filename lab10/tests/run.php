<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Exceptions\{ExportException, InvalidReportException};
use App\Export\{AbstractExporter, CsvExporter, JsonExporter};
use App\Services\ExportService;
use App\ValueObject\Report;

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;
    if ($condition) { $passed++; echo "OK:   $label\n"; }
    else            { $failed++; echo "FAIL: $label\n"; }
}

/** Возвращает пойманное исключение, если оно нужного типа, иначе null. */
function throwsType(callable $action, string $class): ?\Throwable
{
    try {
        $action();
    } catch (\Throwable $e) {
        return $e instanceof $class ? $e : null;
    }
    return null;
}

$rows    = [['name' => 'Айдар', 'grade' => 95], ['name' => 'Алия', 'grade' => 88]];
$csv     = new CsvExporter();
$service = new ExportService([$csv, new JsonExporter()]);

// 1. Корректный CSV
$r = $service->export(new Report('T', $rows, 'csv'));
check('1. CSV: формат csv и заголовок в содержимом',
    $r->format === 'csv' && str_starts_with($r->content, 'name,grade'));

// 2. Корректный JSON
$r = $service->export(new Report('T', $rows, 'json'));
check('2. JSON: формат json и валидный JSON',
    $r->format === 'json' && json_decode($r->content, true)['title'] === 'T');

// 3. Пустой отчёт -> InvalidReportException
check('3. Пустой отчёт -> InvalidReportException',
    throwsType(fn() => $service->export(new Report('T', [], 'csv')), InvalidReportException::class) !== null);

// 4. Нескалярное значение в JSON -> InvalidReportException
check('4. Массив в ячейке JSON -> InvalidReportException',
    throwsType(fn() => $service->export(new Report('T', [['a' => [1]]], 'json')), InvalidReportException::class) !== null);

// 5. Неизвестный формат -> ExportException
check('5. Формат xml -> ExportException',
    throwsType(fn() => $service->export(new Report('T', $rows, 'xml')), ExportException::class) !== null);

// 6. Сбой JSON (невалидный UTF-8) -> ExportException с previous
$e = throwsType(fn() => $service->export(new Report('T', [['a' => "\xB1\x31"]], 'json')), ExportException::class);
check('6. Невалидный UTF-8 -> ExportException с previous',
    $e instanceof ExportException && $e->getPrevious() instanceof \JsonException);

// 7. CSV длиннее 1000 строк -> ExportException
$big = array_fill(0, 1001, ['a' => 1]);
check('7. CSV > 1000 строк -> ExportException',
    throwsType(fn() => $service->export(new Report('T', $big, 'csv')), ExportException::class) !== null);

// 8. Журнал после успеха
$service->export(new Report('Лог', $rows, 'csv'));
$entries = $csv->logEntries();
check('8. logEntries() содержит запись после успеха',
    $entries !== [] && str_contains($entries[count($entries) - 1], 'Лог'));

// 9. finally выполняется при ошибке
$finallyRan = false;
try {
    $service->export(new Report('T', [], 'csv'));
} catch (InvalidReportException) {
} finally {
    $finallyRan = true;
}
check('9. finally выполнен при исключении', $finallyRan);

// 10. finally выполняется при успехе
$finallyRan = false;
try {
    $service->export(new Report('T', $rows, 'csv'));
} finally {
    $finallyRan = true;
}
check('10. finally выполнен при успехе', $finallyRan);

// 11. Абстрактный класс нельзя создать напрямую
check('11. new AbstractExporter -> Error',
    throwsType(fn() => new AbstractExporter(), \Error::class) !== null);

echo "\nПройдено: $passed, провалено: $failed\n";
exit($failed === 0 ? 0 : 1);
