<?php declare(strict_types=1);

namespace Tests\Functional\Event;

use App\Entity\Event;
use App\Entity\Location;
use App\Entity\User;
use App\Event\BallotLocationChoice;
use App\Form\BallotTermsType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\BallotView;
use Module\Ballot\Contract\TallyMode;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueBallotSettlementTest extends WebTestCase
{
    private const string STEWARD_EMAIL = 'Admin@example.org';

    public function testTheWinningVenueIsWrittenOntoTheEvent(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client, self::STEWARD_EMAIL);
        $event = $this->eventWithVenue($client);
        $eventId = (int) $event->getId();
        $other = $this->otherVenue($client, (int) $event->getLocation()?->getId());
        $ballotId = $this->openBallot($client, $event);

        // Act
        $this->ballots()->settle($ballotId, (string) $other->getId());

        // Assert
        self::assertSame($other->getId(), $this->reloadEvent($client, $eventId)->getLocation()?->getId());
    }

    public function testLeavingItUndecidedWritesNothing(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client, self::STEWARD_EMAIL);
        $event = $this->eventWithVenue($client);
        $eventId = (int) $event->getId();
        $before = (int) $event->getLocation()?->getId();
        $ballotId = $this->openBallot($client, $event);

        // Act
        $this->ballots()->settle($ballotId, BallotLocationChoice::CANDIDATE_UNDECIDED);

        // Assert
        self::assertSame($before, $this->reloadEvent($client, $eventId)->getLocation()?->getId());
    }

    public function testTheOverlayTermsReachTheBallot(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client, self::STEWARD_EMAIL);
        $event = $this->eventWithVenue($client);

        // Act
        $ballotId = $this->openBallot($client, $event, [
            BallotTermsType::FIELD_DURATION => '2',
            BallotTermsType::FIELD_MODE => TallyMode::Single->value,
        ]);

        // Assert
        $view = $this->ballots()->view($ballotId, null);
        self::assertInstanceOf(BallotView::class, $view);
        self::assertSame(TallyMode::Single, $view->tallyMode);
        self::assertSame(new DateTimeImmutable('+2 days')->format('Y-m-d'), $view->deadline->format('Y-m-d'), 'the overlay asked for two days');
        self::assertNotNull($view->title, 'the ballot page says which event it decides');
    }

    public function testEveryVisibleVenuePlusLeavingItUndecidedIsACandidate(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client, self::STEWARD_EMAIL);
        $event = $this->eventWithVenue($client);

        // Act
        $ballotId = $this->openBallot($client, $event);

        // Assert
        $keys = array_map(static fn(object $candidate): string => $candidate->key, $this->ballots()->view($ballotId, null)->candidates ?? []);
        self::assertContains(BallotLocationChoice::CANDIDATE_UNDECIDED, $keys);
        self::assertContains((string) $event->getLocation()?->getId(), $keys);
    }

    /**
     * @param array<string, mixed> $terms
     */
    private function openBallot(KernelBrowser $client, Event $event, array $terms = []): int
    {
        static::getContainer()->get(BallotLocationChoice::class)->choose($event, $terms);

        $subject = new BallotSubject(BallotLocationChoice::SUBJECT_TYPE, (int) $event->getId());
        foreach ($this->ballots()->listForSubject($subject, null) as $view) {
            if ($view->purpose === BallotLocationChoice::PURPOSE && !$view->status->isResolved()) {
                return $view->id;
            }
        }

        self::fail('No ballot was opened for event ' . $event->getId());
    }

    private function otherVenue(KernelBrowser $client, int $exceptId): Location
    {
        $venues = $this
            ->em($client)
            ->getRepository(Location::class)
            ->createQueryBuilder('l')
            ->where('l.id != :id')
            ->setParameter('id', $exceptId)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if ($venues === []) {
            self::fail('Required fixture: a second venue');
        }

        return $venues[0];
    }

    private function eventWithVenue(KernelBrowser $client): Event
    {
        $event = $this
            ->em($client)
            ->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.location IS NOT NULL')
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$event instanceof Event) {
            self::fail('Required event fixture with a venue missing');
        }

        return $event;
    }

    private function reloadEvent(KernelBrowser $client, int $id): Event
    {
        $em = $this->em($client);
        $em->clear();
        $event = $em->getRepository(Event::class)->find($id);
        if (!$event instanceof Event) {
            self::fail('Event vanished');
        }

        return $event;
    }

    private function login(KernelBrowser $client, string $email): User
    {
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . $email);
        }
        $client->loginUser($user);

        return $user;
    }

    private function ballots(): BallotInterface
    {
        return static::getContainer()->get(BallotInterface::class);
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
