<?php declare(strict_types=1);

namespace Tests\Functional\Event;

use App\Entity\EmailQueue;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\User;
use App\Enum\EmailType;
use App\Event\BallotLocationChoice;
use App\Service\Event\AttendeeUpdateNotifier;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotStatus;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\BallotView;
use Module\Ballot\Contract\TallyMode;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueBallotLoopTest extends WebTestCase
{
    private const string ADMIN_EMAIL = 'Admin@example.org';

    public function testSavingTheEventFormOpensTheVoteOnTheTermsTheOverlayCollected(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);

        // Act
        $this->submitVenueVote($client, (int) $event->getId(), durationDays: 3, mode: TallyMode::Single);

        // Assert
        $view = $this->runningBallot($event);
        self::assertInstanceOf(BallotView::class, $view, 'saving the form opened the vote');
        self::assertSame(TallyMode::Single, $view->tallyMode);
        self::assertSame(new DateTimeImmutable('+3 days')->format('Y-m-d'), $view->deadline->format('Y-m-d'));
    }

    public function testTheWinningVenueReachesTheEventAndItsAttendees(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $winner = $this->otherVenue($client, (int) $event->getLocation()?->getId());
        $voters = $this->attendees($client, $event);
        $expectedMails = static::getContainer()->get(AttendeeUpdateNotifier::class)->countNotifiable($event);
        self::assertGreaterThan(0, $expectedMails, 'the event has attendees who asked to hear about changes');
        $mailsBefore = $this->queuedUpdateMails($client);
        $this->submitVenueVote($client, (int) $event->getId());

        // Act
        $ballotId = $this->vote($event, array_fill_keys($voters, (string) $winner->getId()));
        static::getContainer()->get(BallotInterface::class)->settle($ballotId, (string) $winner->getId());

        // Assert
        self::assertSame($winner->getId(), $this->reload($client, (int) $event->getId())->getLocation()?->getId());
        self::assertSame($mailsBefore + $expectedMails, $this->queuedUpdateMails($client), 'every attendee heard about it');
    }

    public function testATiedVoteLeavesTheVenueAloneForAPersonToDecide(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $venueBefore = (int) $event->getLocation()?->getId();
        $other = $this->otherVenue($client, $venueBefore);
        [$first, $second] = $this->attendees($client, $event);
        $this->submitVenueVote($client, (int) $event->getId(), mode: TallyMode::Single);

        // Act
        $ballotId = $this->vote($event, [$first => (string) $venueBefore, $second => (string) $other->getId()]);
        $outcome = static::getContainer()->get(BallotInterface::class)->tally($ballotId);

        // Assert
        self::assertNull($outcome->winningKey, 'a tie names no winner');
        self::assertSame(BallotStatus::Tallied, $outcome->status, 'the ballot waits for a person');
        self::assertSame($venueBefore, $this->reload($client, (int) $event->getId())->getLocation()?->getId());
    }

    private function submitVenueVote(KernelBrowser $client, int $eventId, int $durationDays = 7, ?TallyMode $mode = null): void
    {
        $crawler = $client->request('GET', '/en/admin/events/' . $eventId . '/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['event[location]'] = BallotLocationChoice::VALUE;
        $form['event[ballotTerms][durationDays]'] = (string) $durationDays;
        if ($mode !== null) {
            $form['event[ballotTerms][tallyMode]'] = $mode->value;
        }
        $client->submit($form);

        self::assertResponseRedirects();
        $this->em($client)->clear();
    }

    /**
     * @param  array<int, string> $votes voter id to the candidate key they pick
     * @return int                the ballot they voted on
     */
    private function vote(Event $event, array $votes): int
    {
        $ballots = static::getContainer()->get(BallotInterface::class);
        $view = $this->runningBallot($event);
        self::assertInstanceOf(BallotView::class, $view);

        foreach ($votes as $userId => $key) {
            $ballots->cast($view->id, $userId, [$key]);
        }

        return $view->id;
    }

    private function runningBallot(Event $event): ?BallotView
    {
        $subject = new BallotSubject(BallotLocationChoice::SUBJECT_TYPE, (int) $event->getId());
        foreach (static::getContainer()->get(BallotInterface::class)->listForSubject($subject, null) as $view) {
            if ($view->purpose === BallotLocationChoice::PURPOSE && !$view->status->isResolved()) {
                return $view;
            }
        }

        return null;
    }

    /**
     * @return list<int> two attendee ids who are not the event's creator
     */
    private function attendees(KernelBrowser $client, Event $event): array
    {
        $em = $this->em($client);
        $creatorId = $event->getUser()?->getId();
        $picked = [];
        foreach ($em->getRepository(User::class)->findBy([], ['id' => 'ASC'], 10) as $user) {
            if ($user->getId() === $creatorId) {
                continue;
            }

            $event->addRsvp($user);
            $picked[] = (int) $user->getId();
            if (count($picked) === 2) {
                break;
            }
        }
        $em->flush();

        self::assertCount(2, $picked, 'the fixtures carry enough members to hold a vote');

        return $picked;
    }

    private function queuedUpdateMails(KernelBrowser $client): int
    {
        return (int) $this->em($client)->createQueryBuilder()
            ->select('COUNT(q.id)')
            ->from(EmailQueue::class, 'q')
            ->where('q.template = :template')
            ->setParameter('template', EmailType::EventUpdateNotification->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function futureEventWithVenue(KernelBrowser $client): Event
    {
        $event = $this->em($client)->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.location IS NOT NULL')
            ->andWhere('e.start > :now')
            ->andWhere('e.series IS NULL')
            ->setParameter('now', new DateTime('+1 day'))
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$event instanceof Event) {
            self::fail('Required fixture: a standalone future event with a venue');
        }

        return $event;
    }

    private function otherVenue(KernelBrowser $client, int $exceptId): Location
    {
        $venues = $this->em($client)->getRepository(Location::class)
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

    private function reload(KernelBrowser $client, int $id): Event
    {
        $em = $this->em($client);
        $em->clear();
        $event = $em->getRepository(Event::class)->find($id);
        if (!$event instanceof Event) {
            self::fail('Event vanished');
        }

        return $event;
    }

    private function login(KernelBrowser $client): void
    {
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADMIN_EMAIL]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . self::ADMIN_EMAIL);
        }

        $client->loginUser($user);
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
