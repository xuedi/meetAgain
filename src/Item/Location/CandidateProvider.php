<?php declare(strict_types=1);

namespace App\Item\Location;

use App\Entity\Location;
use App\Filter\Admin\Location\AdminLocationListFilterService;
use App\Item\CandidateProviderInterface;
use App\Repository\LocationRepository;
use Override;

final readonly class CandidateProvider implements CandidateProviderInterface
{
    public function __construct(
        private LocationRepository $repo,
        private AdminLocationListFilterService $filterService,
    ) {}

    #[Override]
    public function getCandidateItemIds(string $itemType): array
    {
        if ($itemType !== ListCellProvider::ITEM_TYPE) {
            return [];
        }

        $visible = $this->repo->findAllForAdmin($this->filterService->getLocationIdFilter()->getLocationIds());

        return array_values(array_map(static fn(Location $location): int => (int) $location->getId(), $visible));
    }
}
