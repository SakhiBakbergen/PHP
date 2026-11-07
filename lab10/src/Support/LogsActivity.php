<?php
declare(strict_types=1);

namespace App\Support;

trait LogsActivity
{
    /** @var list<string> */
    private array $log = [];

    final protected function log(string $message): void
    {
        $this->log[] = (new \DateTimeImmutable())->format(DATE_ATOM) . ' ' . $message;
    }

    /** @return list<string> */
    final public function logEntries(): array
    {
        return $this->log;
    }
}
