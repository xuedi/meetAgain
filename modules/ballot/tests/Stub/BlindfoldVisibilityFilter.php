<?php declare(strict_types=1);

namespace Module\Ballot\Tests\Stub;

use Module\Ballot\Contract\VisibilityFilterInterface;
use Override;

class BlindfoldVisibilityFilter implements VisibilityFilterInterface
{
    /** @var list<int> */
    public array $hiddenBallotIds = [];

    #[Override]
    public function getPriority(): int
    {
        return 0;
    }

    #[Override]
    public function narrowVisibleBallotIds(string $purpose, array $ballotIds, ?int $viewerUserId): ?array
    {
        if ($this->hiddenBallotIds === []) {
            return null;
        }

        return array_values(array_diff($ballotIds, $this->hiddenBallotIds));
    }
}
