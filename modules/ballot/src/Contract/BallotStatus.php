<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

enum BallotStatus: string
{
    case Open = 'open';
    case Tallied = 'tallied';
    case Settled = 'settled';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'ballot.status_open',
            self::Tallied => 'ballot.status_tallied',
            self::Settled => 'ballot.status_settled',
            self::Abandoned => 'ballot.status_abandoned',
        };
    }

    public function acceptsVotes(): bool
    {
        return $this === self::Open;
    }

    public function isResolved(): bool
    {
        return $this === self::Settled || $this === self::Abandoned;
    }
}
