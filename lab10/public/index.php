<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Exceptions\ExportException;
use App\Exceptions\InvalidReportException;
use App\Export\{CsvExporter, JsonExporter};
use App\Services\ExportService;
use App\ValueObject\Report;

$service = new ExportService([
    new CsvExporter(),
    new JsonExporter(),
]);

$rows = [
    ['student' => 'Айдар', 'group' => 'ИС-31', 'grade' => 95],
    ['student' => 'Алия', 'group' => 'ИС-31', 'grade' => 88],
];

foreach (['csv', 'json'] as $format) {
    try {
        $result = $service->export(new Report('Успеваемость', $rows, $format));
        echo "ID: {$result->id} ({$result->format})\n{$result->content}\n";
    } catch (InvalidReportException $e) {
        echo 'Проверьте данные отчёта: ' . $e->getMessage() . PHP_EOL;
    } catch (ExportException $e) {
        error_log($e->__toString());
        echo 'Не удалось выполнить экспорт.' . PHP_EOL;
    } finally {
        echo "Сценарий завершён ($format).\n\n";
    }
}
