<?php

declare(strict_types=1);

namespace University\Library\Model;

final readonly class Loan
{
    public function __construct(
        public string $isbn,
        public int $memberId,
        public \DateTimeImmutable $issuedAt,
        public \DateTimeImmutable $dueAt,
    ) {
    }

    public function isOverdue(?\DateTimeImmutable $now = null): bool
    {
        return ($now ?? new \DateTimeImmutable()) > $this->dueAt;
    }
}