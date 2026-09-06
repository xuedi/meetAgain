<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

final readonly class BallotOutcome
{
    /**
     * @param list<string> $tiedKeys
     */
    public function __construct(
        public int $ballotId,
        public string $purpose,
        public BallotStatus $status,
        public ?string $winningKey = null,
        public array $tiedKeys = [],
        public ?BallotSubject $subject = null,
        public ?int $settledByUserId = null,
        public ?int $openedByUserId = null,
    ) {}

    public function isDecided(): bool
    {
        return $this->winningKey !== null;
    }

    public function isTied(): bool
    {
        return $this->winningKey === null && count($this->tiedKeys) > 1;
    }
}
