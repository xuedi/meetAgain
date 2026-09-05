<?php declare(strict_types=1);

namespace Module\Ballot\Internal;

final readonly class TallyResult
{
    /**
     * @param list<string> $tiedKeys
     */
    public function __construct(
        public ?string $winningKey = null,
        public array $tiedKeys = [],
    ) {}
}
