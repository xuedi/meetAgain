<?php declare(strict_types=1);

namespace App\Item\Ballot;

use App\Enum\ItemViewType;
use App\Item\CandidateProviderInterface;
use App\Item\ListCellRegistry;
use Module\Ballot\Contract\Candidate;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class Candidates
{
    private const int LABEL_LENGTH = 120;

    /**
     * @param iterable<CandidateProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(CandidateProviderInterface::class)]
        private iterable $providers,
        private ListCellRegistry $listCells,
    ) {}

    /**
     * @return list<int>
     */
    public function itemIdsFor(string $itemType): array
    {
        $ranked = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getCandidateItemIds($itemType) as $itemId) {
                $ranked[$itemId] = true;
            }
        }

        return array_map(intval(...), array_keys($ranked));
    }

    /**
     * @param  list<int>       $itemIds
     * @return list<Candidate>
     */
    public function forBallot(string $itemType, array $itemIds): array
    {
        $candidates = [];
        foreach (array_unique($itemIds) as $itemId) {
            $candidates[] = new Candidate((string) $itemId, $this->labelFor($itemType, $itemId));
        }

        return $candidates;
    }

    private function labelFor(string $itemType, int $itemId): string
    {
        $cell = $this->listCells->providerFor($itemType)?->renderListCell($itemId, ItemViewType::Row);
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $cell)));

        return $text === '' ? '#' . $itemId : mb_substr($text, 0, self::LABEL_LENGTH);
    }
}
