<?php

declare(strict_types=1);

namespace University\Library\Contract;

use University\Library\Model\Book;

interface BookRepositoryInterface
{
    public function add(Book $book): void;

    /** @return list<Book> */
    public function all(): array;

    public function findByIsbn(string $isbn): ?Book;

    /** @return list<Book> */
    public function search(string $query = '', ?string $genre = null): array;

    /** @return list<string> */
    public function genres(): array;
}