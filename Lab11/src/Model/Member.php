<?php

declare(strict_types=1);

namespace University\Library\Model;

use University\Library\Exception\InvalidBookException;

final readonly class Member
{
    public function __construct(
        public int $id,
        public string $name,
        public int $maxLoans = 3,
    ) {
        if ($id < 1) {
            throw new InvalidBookException('Номер читателя должен быть положительным.');
        }
        if (trim($name) === '') {
            throw new InvalidBookException('Имя читателя обязательно.');
        }
        if ($maxLoans < 1 || $maxLoans > 10) {
            throw new InvalidBookException('Лимит выдачи должен быть от 1 до 10.');
        }
    }
}
