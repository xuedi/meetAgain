<?php declare(strict_types=1);

namespace Tests\Functional\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Event\BallotLocationChoice;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotStatus;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\BallotView;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueBallotDecisionTest extends WebTestCase
{
    private const string ADMIN_EMAIL = 'Admin@example.org';

    public function testTheEventFormOffersToDecideAVoteThatStoppedAtTheCount(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $ballotId = $this->tiedBallot($client, $event);

        // Act
        $crawler = $client->request('GET', '/en/admin/events/' . $event->getId() . '/edit');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href$="/venue-ballot/' . $ballotId . '/close"]'));
    }

    public function testARunningVoteGetsNoSuchOffer(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);

        // Act
        $crawler = $client->request('GET', '/en/admin/events/' . $event->getId() . '/edit');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('a[href*="/venue-ballot/"]'));
    }

    public function testThePageOffersOnlyTheTiedVenues(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $ballotId = $this->tiedBallot($client, $event);

        // Act
        $crawler = $client->request('GET', '/en/venue-ballot/' . $ballotId . '/close');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('input[name="winner"]'), 'the undecided candidate never tied');
    }

    public function testPickingAWinnerSettlesTheVoteAndWritesTheVenue(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $eventId = (int) $event->getId();
        $ballotId = $this->tiedBallot($client, $event);
        $winner = $this->ballots()->view($ballotId, null)?->tiedKeys[1] ?? '';
        $crawler = $client->request('GET', '/en/venue-ballot/' . $ballotId . '/close');

        // Act
        $form = $crawler->filter('form')->reduce(
            static fn(object $node): bool => str_contains((string) $node->attr('action'), '/venue-ballot/'),
        )->form();
        $form['winner'] = $winner;
        $client->submit($form);

        // Assert
        self::assertResponseRedirects('/en/ballots/' . $ballotId);
        self::assertSame(BallotStatus::Settled, $this->ballots()->view($ballotId, null)?->status);
        self::assertSame($winner, (string) $this->reload($client, $eventId)->getLocation()?->getId());
    }

    public function testAVoteNobodyAnsweredIsAlsoLeftForAPerson(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $event = $this->futureEventWithVenue($client);
        $ballotId = $this->talliedBallot($client, $event, vote: false);

        // Act
        $crawler = $client->request('GET', '/en/venue-ballot/' . $ballotId . '/close');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(2, $crawler->filter('input[name="winner"]')->count(), 'with no tie every candidate is offered');
    }

    public function testAVoteThatIsNotAboutAVenueIsNotFound(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);

        // Act
        $client->request('GET', '/en/venue-ballot/999999/close');

        // Assert
        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    private function tiedBallot(KernelBrowser $client, Event $event): int
    {
        return $this->talliedBallot($client, $event);
    }

    private function talliedBallot(KernelBrowser $client, Event $event, bool $vote = true): int
    {
        static::getContainer()->get(BallotLocationChoice::class)->choose($event, []);

        $subject = new BallotSubject(BallotLocationChoice::SUBJECT_TYPE, (int) $event->getId());
        $view = null;
        foreach ($this->ballots()->listForSubject($subject, null) as $candidate) {
            if ($candidate->purpose !== BallotLocationChoice::PURPOSE || $candidate->status->isResolved()) {
                continue;
            }

            $view = $candidate;
            break;
        }

        self::assertInstanceOf(BallotView::class, $view, 'the venue vote was not opened');

        if ($vote) {
            [$first, $second] = $this->voterIds($client);
            $venues = $this->venueKeys($view);
            $this->ballots()->cast($view->id, $first, [$venues[0]]);
            $this->ballots()->cast($view->id, $second, [$venues[1]]);
        }

        $this->ballots()->tally($view->id);

        return $view->id;
    }

    /**
     * @return list<string> the two venue keys the fixtures guarantee, undecided excluded
     */
    private function venueKeys(BallotView $view): array
    {
        $keys = [];
        foreach ($view->candidates as $candidate) {
            if ($candidate->key === BallotLocationChoice::CANDIDATE_UNDECIDED) {
                continue;
            }

            $keys[] = $candidate->key;
        }

        self::assertGreaterThanOrEqual(2, count($keys), 'a tie needs two venues');

        return $keys;
    }

    /**
     * @return list<int>
     */
    private function voterIds(KernelBrowser $client): array
    {
        $rows = $this->em($client)->createQueryBuilder()
            ->select('u.id')
            ->from(User::class, 'u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(2)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row): int => (int) $row['id'], $rows);
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

    private function ballots(): BallotInterface
    {
        return static::getContainer()->get(BallotInterface::class);
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
