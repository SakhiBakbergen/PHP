<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Exceptions\{ExportException, InvalidReportException};
use App\Export\{CsvExporter, JsonExporter};
use App\Services\ExportService;
use App\ValueObject\Report;

$service = new ExportService([new CsvExporter(), new JsonExporter()]);

$cases = [
    'Пустой отчёт'        => new Report('T', [], 'csv'),
    'Неизвестный формат'  => new Report('T', [['a' => 1]], 'xml'),
    'Массив в ячейке JSON' => new Report('T', [['a' => [1]]], 'json'),
    'Битый UTF-8'         => new Report('T', [['a' => "\xB1\x31"]], 'json'),
];

foreach ($cases as $name => $report) {
    try {
        $service->export($report);
    } catch (InvalidReportException $e) {
        echo "[$name] Ошибка данных: {$e->getMessage()}\n";
    } catch (ExportException $e) {
        $prev = $e->getPrevious() ? ' (причина: ' . $e->getPrevious()::class . ')' : '';
        echo "[$name] Сбой экспорта: {$e->getMessage()}$prev\n";
    } finally {
        echo "  finally выполнен\n";
    }
}