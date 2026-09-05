<?php declare(strict_types=1);

namespace Tests\Unit\Service\Location;

use App\Entity\Location;
use App\Filter\Admin\Location\AdminLocationListFilterService;
use App\Filter\Event\EventFilterService;
use App\Filter\Location\LocationFilterResult;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Service\Location\MemberVenueService;
use PHPUnit\Framework\TestCase;

class MemberVenueServiceTest extends TestCase
{
    public function testTheNavListsTheVenuesTheGroupScopeAllows(): void
    {
        // Arrange
        $venue = $this->venue('Cafe Central');
        $service = $this->makeService(venues: [$venue]);

        // Act
        $list = $service->listForMember();

        // Assert
        self::assertSame([$venue], $list);
    }

    public function testAVenueInTheGroupScopeMayBeTouched(): void
    {
        // Arrange
        $service = $this->makeService(accessible: true, accessibleEventIds: []);

        // Act & Assert
        self::assertTrue($service->mayTouch(7));
    }

    public function testAVenueOutsideTheGroupScopeButOnAVisibleEventMayBeTouched(): void
    {
        // Arrange
        $service = $this->makeService(accessible: false, accessibleEventIds: [12]);

        // Act & Assert
        self::assertTrue($service->mayTouch(7));
    }

    public function testAVenueThatIsNeitherInScopeNorOnAVisibleEventMayNotBeTouched(): void
    {
        // Arrange
        $service = $this->makeService(accessible: false, accessibleEventIds: []);

        // Act & Assert
        self::assertFalse($service->mayTouch(7));
    }

    public function testAVenueThatNoLongerExistsMayNotBeTouched(): void
    {
        // Arrange
        $service = $this->makeService(exists: false);

        // Act & Assert
        self::assertFalse($service->mayTouch(7));
    }

    /**
     * @param list<Location> $venues
     * @param list<int> $accessibleEventIds
     */
    private function makeService(
        array $venues = [],
        bool $accessible = true,
        array $accessibleEventIds = [],
        bool $exists = true,
    ): MemberVenueService {
        $locationRepo = $this->createStub(LocationRepository::class);
        $locationRepo->method('findAllForAdmin')->willReturn($venues);
        $locationRepo->method('find')->willReturn($exists ? $this->venue('Cafe Central') : null);

        $eventRepo = $this->createStub(EventRepository::class);
        $eventRepo->method('findIdsByLocation')->willReturn([12]);

        $locationFilter = $this->createStub(AdminLocationListFilterService::class);
        $locationFilter->method('getLocationIdFilter')->willReturn(new LocationFilterResult(null, false));
        $locationFilter->method('isLocationAccessible')->willReturn($accessible);

        $eventFilter = $this->createStub(EventFilterService::class);
        $eventFilter->method('getAccessibleEventIds')->willReturn($accessibleEventIds);

        return new MemberVenueService($locationRepo, $eventRepo, $locationFilter, $eventFilter);
    }

    private function venue(string $name): Location
    {
        $venue = new Location();
        $venue->setName($name);

        return $venue;
    }
}
