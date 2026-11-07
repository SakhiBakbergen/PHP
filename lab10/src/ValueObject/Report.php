<?php
declare(strict_types=1);

namespace App\ValueObject;

final readonly class Report
{
    /** @param list<array<string, scalar|null>> $rows */
    public function __construct(
        public string $title,
        public array $rows,
        public string $format,
    ) {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Название отчёта обязательно.');
        }
    }
}
