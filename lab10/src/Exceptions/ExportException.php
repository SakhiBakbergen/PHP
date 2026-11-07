<?php
declare(strict_types=1);

namespace App\Exceptions;

/** Сбой самой операции экспорта. */
final class ExportException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $format,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
