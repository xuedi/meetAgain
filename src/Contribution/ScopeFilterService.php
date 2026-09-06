<?php declare(strict_types=1);

namespace App\Contribution;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ScopeFilterService
{
    /**
     * @param iterable<ScopeFilterInterface> $filters
     */
    public function __construct(
        #[AutowireIterator(ScopeFilterInterface::class)]
        private iterable $filters,
    ) {}

    /**
     * @param  list<int|string>  $ids
     * @return list<int|string>
     */
    public function narrow(string $type, array $ids, User $user): array
    {
        $reachable = $ids;

        foreach ($this->sorted() as $filter) {
            $narrowed = $filter->narrowContributableIds($type, $reachable, $user);
            if ($narrowed === null) {
                continue;
            }

            $reachable = array_values(array_intersect($reachable, $narrowed));
            if ($reachable === []) {
                return [];
            }
        }

        return $reachable;
    }

    public function allows(string $type, int|string $id, User $user): bool
    {
        return $this->narrow($type, [$id], $user) !== [];
    }

    /**
     * @return list<ScopeFilterInterface>
     */
    private function sorted(): array
    {
        $filters = array_values(iterator_to_array($this->filters));
        usort($filters, static fn(ScopeFilterInterface $a, ScopeFilterInterface $b): int => $b->getPriority() <=> $a->getPriority());

        return $filters;
    }
}
