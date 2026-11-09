<?php

declare(strict_types=1);

namespace University\Library\Service;

use University\Library\Contract\BookRepositoryInterface;
use University\Library\Exception\LoanException;
use University\Library\Model\Loan;
use University\Library\Model\Member;

final class LoanService
{
    public const LOAN_DAYS = 14;

    /** @var array<string, Loan> isbn => выдача */
    private array $loans = [];

    public function __construct(
        private readonly BookRepositoryInterface $books,
    ) {
    }

    public function lend(string $isbn, Member $member, ?\DateTimeImmutable $at = null): Loan
    {
        $book = $this->books->findByIsbn($isbn)
            ?? throw new LoanException('Книга не найдена в каталоге.');

        if (!$book->isAvailable()) {
            throw new LoanException('Книга уже выдана.');
        }
        if ($this->countLoans($member) >= $member->maxLoans) {
            throw new LoanException('Превышен лимит выдачи для читателя.');
        }

        $issued = $at ?? new \DateTimeImmutable();
        $loan = new Loan($isbn, $member->id, $issued, $issued->modify('+' . self::LOAN_DAYS . ' days'));

        $book->markLoaned();
        $this->loans[$isbn] = $loan;

        return $loan;
    }

    public function giveBack(string $isbn, Member $member): void
    {
        $book = $this->books->findByIsbn($isbn)
            ?? throw new LoanException('Книга не найдена в каталоге.');

        if (($this->loans[$isbn]->memberId ?? null) !== $member->id) {
            throw new LoanException('Эта книга не выдана данному читателю.');
        }

        $book->markReturned();
        unset($this->loans[$isbn]);
    }

    public function loanFor(string $isbn): ?Loan
    {
        return $this->loans[$isbn] ?? null;
    }

    public function holderOf(string $isbn): ?int
    {
        return $this->loans[$isbn]->memberId ?? null;
    }

    /** @return list<Loan> */
    public function loansOf(Member $member): array
    {
        return array_values(array_filter(
            $this->loans,
            static fn (Loan $loan): bool => $loan->memberId === $member->id,
        ));
    }

    public function countLoans(Member $member): int
    {
        return count($this->loansOf($member));
    }
}