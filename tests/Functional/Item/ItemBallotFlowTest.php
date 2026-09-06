<?php declare(strict_types=1);

namespace Tests\Functional\Item;

use App\Entity\User;
use App\Item\Ballot\Candidates;
use App\Item\Ballot\Purpose;
use App\Service\Item\AssociationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotRequest;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\Candidate;
use Module\Ballot\Contract\SettlementMode;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ItemBallotFlowTest extends WebTestCase
{
    private const string HOST = 'cinema.meetagain.local';
    private const string ITEM_TYPE = 'film';
    private const string ORGANIZER_EMAIL = 'Admin@example.org';
    private const int LOSER_ITEM = 999801;
    private const int WINNER_ITEM = 999802;

    public function testSettlingAnItemBallotAttachesTheWinnerToItsEvent(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = $this->anyEventId($client);
        $ballotId = $this->openBallot($eventId, [self::LOSER_ITEM, self::WINNER_ITEM]);

        // Act
        $this->ballots()->settle($ballotId, (string) self::WINNER_ITEM);

        // Assert
        $associations = static::getContainer()->get(AssociationService::class);
        self::assertContains($eventId, $associations->eventIdsForItem(self::ITEM_TYPE, self::WINNER_ITEM));
        self::assertSame([], $associations->eventIdsForItem(self::ITEM_TYPE, self::LOSER_ITEM));
    }

    public function testTheEventPageLinksToTheRunningVoteAndOffersToStartOneOtherwise(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $eventId = $this->anyEventId($client, self::HOST);
        $this->abandonRunningVotes($eventId);
        $ballotId = $this->openBallot($eventId, [self::LOSER_ITEM, self::WINNER_ITEM]);

        // Act
        $running = $this->get($client, '/en/event/' . $eventId, self::HOST);
        $this->ballots()->abandon($ballotId, $this->organizerId());
        $idle = $this->get($client, '/en/event/' . $eventId, self::HOST);

        // Assert
        self::assertCount(
            1,
            $running->filter('a[href$="/item-ballot/' . $ballotId . '"]'),
            'a running vote takes over the slot',
        );
        self::assertCount(
            1,
            $idle->filter('a[href$="/item-ballot/create/' . $eventId . '/' . self::ITEM_TYPE . '"]'),
            'with no vote running the slot offers to start one',
        );
    }

    public function testAStewardOpensAVoteOnTheTermsTheFormCollects(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $eventId = $this->anyEventId($client, self::HOST);
        if (count(static::getContainer()->get(Candidates::class)->itemIdsFor(self::ITEM_TYPE)) < 2) {
            self::markTestSkipped('The create form only has choices where members wished for films; no such fixture here.');
        }

        // Act
        $crawler = $this->get($client, '/en/item-ballot/create/' . $eventId . '/' . self::ITEM_TYPE, self::HOST);
        $form = $crawler->filter('form[name="item_ballot"]')->form();
        $form['item_ballot[ballotTerms][durationDays]'] = '2';
        $client->submit($form, serverParameters: ['HTTP_HOST' => self::HOST]);

        // Assert
        $this->assertResponseRedirects();
        $page = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString(
            new DateTimeImmutable('+2 days')->format('Y-m-d'),
            $page->filter('.subtitle')->text(),
            'the overlay asked for two days',
        );
    }

    public function testTheVotePageRendersEachCandidateAsItsOwnCell(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $eventId = $this->anyEventId($client, self::HOST);
        $itemIds = $this->listedItemIds($client);
        $ballotId = $this->openBallot($eventId, $itemIds);

        // Act
        $crawler = $this->get($client, '/en/item-ballot/' . $ballotId, self::HOST);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(count($itemIds), $crawler->filter('.ballot-option'), 'one card per candidate');
    }

    public function testTheClosePageOffersTheCandidatesToAPerson(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $eventId = $this->anyEventId($client, self::HOST);
        $ballotId = $this->openBallot($eventId, [self::LOSER_ITEM, self::WINNER_ITEM]);

        // Act
        $crawler = $this->get($client, '/en/item-ballot/' . $ballotId . '/close', self::HOST);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('input[name="winner"]'));
    }

    /**
     * @param list<int> $itemIds
     */
    private function openBallot(int $eventId, array $itemIds): int
    {
        $candidates = [];
        foreach ($itemIds as $itemId) {
            $candidates[] = new Candidate((string) $itemId, 'Item ' . $itemId);
        }

        return $this->ballots()->open(new BallotRequest(
            Purpose::forType(self::ITEM_TYPE),
            $candidates,
            new DateTimeImmutable('+7 days'),
            $this->organizerId(),
            new BallotSubject(Purpose::SUBJECT_TYPE, $eventId),
            settlementMode: SettlementMode::Automatic,
        ));
    }

    /**
     * @return array{int, int} two ids the item cell can actually render; the tiles skip the rest
     */
    private function listedItemIds(KernelBrowser $client): array
    {
        $ids = [];
        foreach ($this->get($client, '/en/films', self::HOST)->filter('a[href^="/en/films/"]')->extract(['href']) as $href) {
            if (preg_match('#/films/(\d+)$#', (string) $href, $match) === 1) {
                $ids[(int) $match[1]] = true;
            }
        }

        $ids = array_slice(array_keys($ids), 0, 2);
        self::assertCount(2, $ids, 'the fixtures need at least two listed films');

        return [$ids[0], $ids[1]];
    }

    private function abandonRunningVotes(int $eventId): void
    {
        $subject = new BallotSubject(Purpose::SUBJECT_TYPE, $eventId);
        foreach ($this->ballots()->listForSubject($subject, null) as $view) {
            if ($view->purpose === Purpose::forType(self::ITEM_TYPE) && !$view->status->isResolved()) {
                $this->ballots()->abandon($view->id, $this->organizerId());
            }
        }
    }

    private function anyEventId(KernelBrowser $client, ?string $host = null): int
    {
        $crawler = $this->get($client, '/en/events', $host);
        $links = $crawler->filter('a[href^="/en/event/"]');
        self::assertGreaterThan(0, $links->count(), 'the fixtures need at least one listed event');

        foreach ($links->extract(['href']) as $href) {
            if (preg_match('#/event/(\d+)$#', (string) $href, $match) !== 1) {
                continue;
            }

            $this->get($client, (string) $href, $host);
            if ($client->getResponse()->isSuccessful()) {
                return (int) $match[1];
            }
        }

        self::fail('No listed event was reachable on ' . ($host ?? 'the default host'));
    }

    private function get(KernelBrowser $client, string $uri, ?string $host = null): Crawler
    {
        return $client->request('GET', $uri, server: $host === null ? [] : ['HTTP_HOST' => $host]);
    }

    private function login(KernelBrowser $client): User
    {
        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => self::ORGANIZER_EMAIL]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . self::ORGANIZER_EMAIL);
        }
        $client->loginUser($user);

        return $user;
    }

    private function organizerId(): int
    {
        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => self::ORGANIZER_EMAIL]);
        self::assertInstanceOf(User::class, $user);

        return (int) $user->getId();
    }

    private function ballots(): BallotInterface
    {
        return static::getContainer()->get(BallotInterface::class);
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
