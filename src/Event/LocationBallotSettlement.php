<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Service\Event\AttendeeUpdateNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotOutcome;
use Module\Ballot\Contract\SettlementListenerInterface;
use Override;

final readonly class LocationBallotSettlement implements SettlementListenerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepo,
        private LocationRepository $locationRepo,
        private AttendeeUpdateNotifier $attendeeNotifier,
    ) {}

    #[Override]
    public function getPriority(): int
    {
        return 0;
    }

    #[Override]
    public function supports(string $purpose): bool
    {
        return $purpose === BallotLocationChoice::PURPOSE;
    }

    #[Override]
    public function settled(BallotOutcome $outcome): void
    {
        $subject = $outcome->subject;
        $winner = $outcome->winningKey;
        if ($subject === null || $winner === null || $winner === BallotLocationChoice::CANDIDATE_UNDECIDED) {
            return;
        }

        $event = $this->eventRepo->find($subject->id);
        $location = ctype_digit($winner) ? $this->locationRepo->find((int) $winner) : null;
        if (!$event instanceof Event || $location === null) {
            return;
        }

        $before = $this->attendeeNotifier->snapshot($event);
        $event->setLocation($location);
        $this->em->flush();

        $this->attendeeNotifier->notify($event, null, $before, $this->attendeeNotifier->snapshot($event));
    }
}
