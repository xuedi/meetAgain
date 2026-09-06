<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

final readonly class BallotSubject
{
    public function __construct(
        public string $type,
        public int $id,
    ) {}

    public function equals(?self $other): bool
    {
        return $other !== null && $other->type === $this->type && $other->id === $this->id;
    }
}
