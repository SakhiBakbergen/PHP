<?php
declare(strict_types=1);

namespace App\Export;

use App\Exceptions\InvalidReportException;
use App\ValueObject\Report;

final class CsvExporter extends AbstractExporter
{
    private const MAX_ROWS = 1000;

    public function supports(Report $report): bool
    {
        return $report->format === 'csv';
    }

    protected function validateReport(Report $report): void
    {
        if ($report->rows === []) {
            throw new InvalidReportException('Отчёт не содержит строк.');
        }
        foreach (array_keys($report->rows[0]) as $header) {
            if (preg_match('/[\r\n]/', (string) $header) === 1) {
                throw new InvalidReportException('Заголовок колонки содержит перевод строки.');
            }
        }
    }

    protected function format(Report $report): string
    {
        if (count($report->rows) > self::MAX_ROWS) {
            throw new \RuntimeException('CSV превышает ' . self::MAX_ROWS . ' строк.');
        }

        $handle = fopen('php://temp', 'r+');
        try {
            fputcsv($handle, array_keys($report->rows[0]), ',', '"', '');
            foreach ($report->rows as $row) {
                fputcsv($handle, array_values($row), ',', '"', '');
            }
            rewind($handle);
            return (string) stream_get_contents($handle);
        } finally {
            fclose($handle); // ресурс освобождается при любом исходе
        }
    }
}
