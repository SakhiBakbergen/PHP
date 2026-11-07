<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Export\CsvExporter as Csv; // псевдоним as
use App\ValueObject\Report;

$exporter = new Csv();
echo $exporter->export(new Report('Демо', [['a' => 1, 'b' => 2]], 'csv'))->content;
