<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use University\Library\Exception\InvalidBookException;
use University\Library\Exception\LoanException;
use University\Library\Model\Book;
use University\Library\Model\Member;
use University\Library\Repository\InMemoryBookRepository;
use University\Library\Service\LoanService;

$passed = 0;

function check(string $name, bool $condition): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException("Тест не пройден: $name");
    }
    $passed++;
    echo "[OK] $name\n";
}

function expectException(string $name, callable $action, string $expected): void
{
    try {
        $action();
    } catch (Throwable $error) {
        check($name, $error instanceof $expected);
        return;
    }
    throw new RuntimeException("Тест не пройден: $name (исключение не выброшено)");
}

function makeEnv(): array
{
    $repo = new InMemoryBookRepository();
    $repo->add(new Book('9785170000001', 'PHP', 'Автор А.'));
    $repo->add(new Book('9785170000002', 'SQL', 'Автор Б.'));
    return [$repo, new LoanService($repo), new Member(1, 'Читатель', 2)];
}

// 1. Успешная выдача
[$repo, $service, $member] = makeEnv();
$service->lend('9785170000001', $member);
check('Выдача книги делает её недоступной', !$repo->findByIsbn('9785170000001')->isAvailable());

// 2. Успешный возврат
$service->giveBack('9785170000001', $member);
check('Возврат делает книгу доступной', $repo->findByIsbn('9785170000001')->isAvailable());

// 3. Подсчёт выданных книг
$service->lend('9785170000001', $member);
check('Счётчик выдач равен 1', $service->countLoans($member) === 1);

// 4. Граница: лимит выдачи
$service->lend('9785170000002', $member);
check('Счётчик выдач на границе лимита равен 2', $service->countLoans($member) === 2);

// 5. Ошибка: повторная выдача
expectException('Повторная выдача -> LoanException',
    fn () => $service->lend('9785170000001', new Member(2, 'Другой')), LoanException::class);

// 6. Ошибка: неверный ISBN
expectException('Неверный ISBN -> InvalidBookException',
    fn () => new Book('abc', 'Книга', 'Автор'), InvalidBookException::class);

// 7. Ошибка: возврат не тем читателем
expectException('Возврат чужой книги -> LoanException',
    fn () => $service->giveBack('9785170000001', new Member(2, 'Другой')), LoanException::class);

// 8. Ошибка: книга не найдена
expectException('Несуществующая книга -> LoanException',
    fn () => $service->lend('0000000000', $member), LoanException::class);

// 9. PSR-4: класс загружается автозагрузчиком
check('PSR-4: класс Book загружен Composer', class_exists(Book::class));

// 10. PSR-4: несуществующий класс не находится
check('PSR-4: несуществующий класс не найден',
    !class_exists('University\\Library\\Model\\Magazine'));

// 11. Срок возврата = дата выдачи + 14 дней
[$repo, $service, $member] = makeEnv();
$issued = new DateTimeImmutable('2026-11-01');
$loan = $service->lend('9785170000001', $member, $issued);
check('Срок возврата через 14 дней', $loan->dueAt->format('Y-m-d') === '2026-11-15');

// 12. Просрочка
check('Просрочка определяется по дате',
    $loan->isOverdue(new DateTimeImmutable('2026-11-20')) && !$loan->isOverdue(new DateTimeImmutable('2026-11-10')));

// 13. Поиск и жанры
$repo->add(new Book('9785170000003', 'Базы данных', 'Петров П.', 'Программирование', 2022));
check('Поиск по названию', count($repo->search('базы')) === 1);
check('Фильтр по жанру', count($repo->search('', 'Программирование')) === 1);

// 14. Ошибка: неверный год
expectException('Неверный год -> InvalidBookException',
    fn () => new Book('9785170000009', 'Книга', 'Автор', 'Жанр', 500), InvalidBookException::class);

echo "Все проверки пройдены: $passed.\n";