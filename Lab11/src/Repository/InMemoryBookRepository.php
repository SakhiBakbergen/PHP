<?php

declare(strict_types=1);

namespace University\Library\Repository;

use University\Library\Contract\BookRepositoryInterface;
use University\Library\Model\Book;

final class InMemoryBookRepository implements BookRepositoryInterface
{
    /** @var array<string, Book> */
    private array $items = [];

    public function add(Book $book): void
    {
        $this->items[$book->isbn] = $book;
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function findByIsbn(string $isbn): ?Book
    {
        return $this->items[$isbn] ?? null;
    }

    public function search(string $query = '', ?string $genre = null): array
    {
        $needle = mb_strtolower(trim($query));

        return array_values(array_filter(
            $this->items,
            static function (Book $book) use ($needle, $genre): bool {
                if ($genre !== null && $genre !== '' && $book->genre !== $genre) {
                    return false;
                }
                if ($needle === '') {
                    return true;
                }
                $haystack = mb_strtolower($book->title . ' ' . $book->author . ' ' . $book->isbn);
                return str_contains($haystack, $needle);
            },
        ));
    }

    public function genres(): array
    {
        $genres = array_unique(array_map(
            static fn (Book $book): string => $book->genre,
            $this->items,
        ));
        sort($genres);

        return array_values($genres);
    }
}