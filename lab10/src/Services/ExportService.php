<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\ReportExporter;
use App\Exceptions\ExportException;
use App\Support\LogsActivity;
use App\ValueObject\ExportResult;
use App\ValueObject\Report;

final class ExportService
{
    use LogsActivity;

    /** @param list<ReportExporter> $exporters */
    public function __construct(private array $exporters) {}

    public function export(Report $report): ExportResult
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($report)) {
                $this->log('Выбран ' . $exporter::class);
                return $exporter->export($report);
            }
        }

        throw new ExportException('Подходящий экспортёр не найден.', $report->format);
    }
}
