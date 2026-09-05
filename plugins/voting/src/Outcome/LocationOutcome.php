<?php declare(strict_types=1);

namespace Plugin\Voting\Outcome;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Service\Event\AttendeeUpdateNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Plugin\Voting\Entity\Poll;

final readonly class LocationOutcome implements PollOutcomeProviderInterface
{
    public const string ITEM_TYPE = 'location';

    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepo,
        private LocationRepository $locationRepo,
        private AttendeeUpdateNotifier $attendeeNotifier,
    ) {}

    #[Override]
    public function supports(string $itemType): bool
    {
        return $itemType === self::ITEM_TYPE;
    }

    #[Override]
    public function getLabelKey(string $itemType): ?string
    {
        return 'voting_poll.subject_location';
    }

    #[Override]
    public function commit(Poll $poll, int $chosenItemId): void
    {
        $eventId = $poll->getEventId();
        if ($eventId === null) {
            return;
        }

        $event = $this->eventRepo->find($eventId);
        $location = $this->locationRepo->find($chosenItemId);
        if (!$event instanceof Event || $location === null) {
            return;
        }

        $before = $this->attendeeNotifier->snapshot($event);
        $event->setLocation($location);
        $this->em->flush();

        $this->attendeeNotifier->notify($event, null, $before, $this->attendeeNotifier->snapshot($event));
    }

    #[Override]
    public function getPriority(): int
    {
        return 10;
    }
}
