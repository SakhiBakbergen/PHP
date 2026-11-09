<?php

declare(strict_types=1);

namespace University\Library\Model;

use University\Library\Exception\InvalidBookException;

final class Book
{
    private bool $available = true;

    public function __construct(
        public readonly string $isbn,
        public readonly string $title,
        public readonly string $author,
        public readonly string $genre = 'Общее',
        public readonly int $year = 2024,
        public readonly string $description = '',
    ) {
        if (preg_match('/^[0-9]{10}([0-9]{3})?$/', $isbn) !== 1) {
            throw new InvalidBookException('ISBN должен содержать 10 или 13 цифр.');
        }
        if (trim($title) === '') {
            throw new InvalidBookException('Название книги обязательно.');
        }
        if (trim($author) === '') {
            throw new InvalidBookException('Автор книги обязателен.');
        }
        if ($year < 1000 || $year > 2100) {
            throw new InvalidBookException('Год издания должен быть от 1000 до 2100.');
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function markLoaned(): void
    {
        $this->available = false;
    }

    public function markReturned(): void
    {
        $this->available = true;
    }
}