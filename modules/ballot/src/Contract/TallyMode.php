<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

enum TallyMode: string
{
    case Approval = 'approval';
    case Single = 'single';

    public function label(): string
    {
        return match ($this) {
            self::Approval => 'ballot.tally_approval',
            self::Single => 'ballot.tally_single',
        };
    }

    public function maximumSelections(): ?int
    {
        return $this === self::Single ? 1 : null;
    }
}
