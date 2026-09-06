<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

use DateTimeImmutable;

final readonly class BallotView
{
    /**
     * @param list<Candidate>     $candidates
     * @param array<string, int>  $tally
     * @param list<string>        $tiedKeys
     * @param list<string>        $viewerSelection
     */
    public function __construct(
        public int $id,
        public string $purpose,
        public BallotStatus $status,
        public array $candidates,
        public array $tally,
        public DateTimeImmutable $deadline,
        public TallyMode $tallyMode,
        public SettlementMode $settlementMode,
        public int $openedByUserId,
        public int $voterCount,
        public ?BallotSubject $subject = null,
        public ?string $winningKey = null,
        public array $tiedKeys = [],
        public array $viewerSelection = [],
        public bool $viewerMayVote = false,
        public ?string $title = null,
        public ?DateTimeImmutable $settledAt = null,
    ) {}

    public function isOpen(): bool
    {
        return $this->status->acceptsVotes();
    }

    public function isTied(): bool
    {
        return $this->winningKey === null && count($this->tiedKeys) > 1;
    }

    public function votesFor(string $key): int
    {
        return $this->tally[$key] ?? 0;
    }

    public function candidateFor(string $key): ?Candidate
    {
        foreach ($this->candidates as $candidate) {
            if ($candidate->key === $key) {
                return $candidate;
            }
        }

        return null;
    }

    public function winner(): ?Candidate
    {
        return $this->winningKey === null ? null : $this->candidateFor($this->winningKey);
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->deadline <= $now;
    }

    public function hasVoted(): bool
    {
        return $this->viewerSelection !== [];
    }
}
