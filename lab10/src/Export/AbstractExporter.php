<?php
declare(strict_types=1);

namespace App\Export;

use App\Contracts\ReportExporter;
use App\Exceptions\ExportException;
use App\Support\LogsActivity;
use App\ValueObject\ExportResult;
use App\ValueObject\Report;

abstract class AbstractExporter implements ReportExporter
{
    use LogsActivity;

    /** Шаблонный метод: порядок шагов одинаков для всех форматов. */
    final public function export(Report $report): ExportResult
    {
        if (!$this->supports($report)) {
            throw new ExportException('Экспортёр не поддерживает формат.', $report->format);
        }

        $this->validateReport($report);

        try {
            $content = $this->format($report);
            $this->log('Экспортирован отчёт «' . $report->title . '» в ' . $report->format);

            return new ExportResult(
                bin2hex(random_bytes(8)),
                $report->format,
                $content,
                new \DateTimeImmutable(),
            );
        } catch (ExportException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ExportException('Ошибка экспорта.', $report->format, $e);
        }
    }

    abstract protected function validateReport(Report $report): void;

    abstract protected function format(Report $report): string;
}
