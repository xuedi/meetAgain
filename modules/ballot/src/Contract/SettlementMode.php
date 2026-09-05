<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

enum SettlementMode: string
{
    case Automatic = 'automatic';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'ballot.settlement_automatic',
            self::Confirmed => 'ballot.settlement_confirmed',
        };
    }
}
