<?php declare(strict_types=1);

namespace App\Service\Location;

use App\Entity\Location;
use App\Filter\Admin\Location\AdminLocationListFilterService;
use App\Filter\Event\EventFilterService;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;

readonly class MemberVenueService
{
    public function __construct(
        private LocationRepository $locationRepo,
        private EventRepository $eventRepo,
        private AdminLocationListFilterService $locationFilter,
        private EventFilterService $eventFilter,
    ) {}

    /**
     * @return list<Location>
     */
    public function listForMember(): array
    {
        return array_values($this->locationRepo->findAllForAdmin($this->locationFilter->getLocationIdFilter()->getLocationIds()));
    }

    public function mayTouch(int $locationId): bool
    {
        if ($this->locationRepo->find($locationId) === null) {
            return false;
        }

        if ($this->locationFilter->isLocationAccessible($locationId)) {
            return true;
        }

        return $this->eventFilter->getAccessibleEventIds($this->eventRepo->findIdsByLocation($locationId)) !== [];
    }
}
