<?php
declare(strict_types=1);

namespace App\ValueObject;

final readonly class ExportResult
{
    public function __construct(
        public string $id,
        public string $format,
        public string $content,
        public \DateTimeImmutable $createdAt,
    ) {}
}
