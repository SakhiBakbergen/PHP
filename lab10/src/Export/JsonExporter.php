<?php
declare(strict_types=1);

namespace App\Export;

use App\Exceptions\InvalidReportException;
use App\ValueObject\Report;

final class JsonExporter extends AbstractExporter
{
    public function supports(Report $report): bool
    {
        return $report->format === 'json';
    }

    protected function validateReport(Report $report): void
    {
        if ($report->rows === []) {
            throw new InvalidReportException('Отчёт не содержит строк.');
        }
        foreach ($report->rows as $row) {
            foreach ($row as $value) {
                if ($value !== null && !is_scalar($value)) {
                    throw new InvalidReportException('Значения должны быть скалярными.');
                }
            }
        }
    }

    protected function format(Report $report): string
    {
        return json_encode(
            ['title' => $report->title, 'rows' => $report->rows],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
    }
}
