<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

final readonly class Candidate
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}
}
