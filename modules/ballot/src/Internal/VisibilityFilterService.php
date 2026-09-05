<?php declare(strict_types=1);

namespace Module\Ballot\Internal;

use Module\Ballot\Contract\VisibilityFilterInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class VisibilityFilterService
{
    /**
     * @param iterable<VisibilityFilterInterface> $filters
     */
    public function __construct(
        #[AutowireIterator(VisibilityFilterInterface::class)]
        private iterable $filters,
    ) {}

    /**
     * @param  list<int> $ballotIds
     * @return list<int>
     */
    public function narrow(string $purpose, array $ballotIds, ?int $viewerUserId): array
    {
        $visible = $ballotIds;

        foreach ($this->sorted() as $filter) {
            $narrowed = $filter->narrowVisibleBallotIds($purpose, $visible, $viewerUserId);
            if ($narrowed === null) {
                continue;
            }

            $visible = array_values(array_intersect($visible, $narrowed));
            if ($visible === []) {
                return [];
            }
        }

        return $visible;
    }

    public function allows(string $purpose, int $ballotId, ?int $viewerUserId): bool
    {
        return $this->narrow($purpose, [$ballotId], $viewerUserId) !== [];
    }

    /**
     * @return list<VisibilityFilterInterface>
     */
    private function sorted(): array
    {
        $filters = array_values(iterator_to_array($this->filters));
        usort(
            $filters,
            static fn(VisibilityFilterInterface $a, VisibilityFilterInterface $b): int => $b->getPriority() <=> $a->getPriority(),
        );

        return $filters;
    }
}
