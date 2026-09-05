<?php declare(strict_types=1);

namespace Plugin\Voting\Tests\Unit\Outcome;

use App\Entity\Event;
use App\Entity\Location;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Service\Event\AttendeeUpdateNotifier;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Plugin\Voting\Entity\Poll;
use Plugin\Voting\Outcome\LocationOutcome;

class LocationOutcomeTest extends TestCase
{
    public function testItClaimsTheLocationSubjectOnly(): void
    {
        // Arrange
        $outcome = $this->makeOutcome();

        // Act & Assert
        self::assertTrue($outcome->supports('location'));
        self::assertFalse($outcome->supports('film'));
    }

    public function testTheWinningVenueIsWrittenOntoTheEvent(): void
    {
        // Arrange
        $event = $this->event();
        $venue = new Location();
        $notifier = $this->createMock(AttendeeUpdateNotifier::class);
        $notifier->expects(self::once())->method('notify');
        $outcome = $this->makeOutcome($event, $venue, $notifier);

        // Act
        $outcome->commit($this->poll(4), 9);

        // Assert
        self::assertSame($venue, $event->getLocation());
    }

    public function testAnEventLessPollCommitsWithoutTouchingAnything(): void
    {
        // Arrange
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');
        $outcome = $this->makeOutcome(em: $em);

        // Act
        $outcome->commit($this->poll(null), 9);
    }

    public function testAVanishedVenueLeavesTheEventUntouched(): void
    {
        // Arrange
        $event = $this->event();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');
        $outcome = $this->makeOutcome($event, null, em: $em);

        // Act
        $outcome->commit($this->poll(4), 9);

        // Assert
        self::assertNull($event->getLocation());
    }

    private function makeOutcome(
        ?Event $event = null,
        ?Location $venue = null,
        ?AttendeeUpdateNotifier $notifier = null,
        ?EntityManagerInterface $em = null,
    ): LocationOutcome {
        $eventRepo = $this->createStub(EventRepository::class);
        $eventRepo->method('find')->willReturn($event);

        $locationRepo = $this->createStub(LocationRepository::class);
        $locationRepo->method('find')->willReturn($venue);

        return new LocationOutcome(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $eventRepo,
            $locationRepo,
            $notifier ?? $this->createStub(AttendeeUpdateNotifier::class),
        );
    }

    private function event(): Event
    {
        $event = new Event();
        $event->setStart(new DateTime('+7 days'));

        return $event;
    }

    private function poll(?int $eventId): Poll
    {
        $poll = new Poll();
        $poll->setItemType('location');
        $poll->setCreatedBy(1);
        if ($eventId !== null) {
            $event = $this->event();
            new ReflectionProperty(Event::class, 'id')->setValue($event, $eventId);
            $poll->setEvent($event);
        }

        return $poll;
    }
}
