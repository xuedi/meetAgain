<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

final readonly class BallotScope
{
    /**
     * @param list<string> $candidateKeys
     */
    public function __construct(
        public int $id,
        public string $purpose,
        public ?BallotSubject $subject = null,
        public array $candidateKeys = [],
    ) {}
}
