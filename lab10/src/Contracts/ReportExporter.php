<?php
declare(strict_types=1);

namespace App\Contracts;

use App\ValueObject\ExportResult;
use App\ValueObject\Report;

interface ReportExporter
{
    public function supports(Report $report): bool;

    public function export(Report $report): ExportResult;
}
