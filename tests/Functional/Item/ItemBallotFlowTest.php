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
        [$eventId, $ballotId] = $this->eventWithARunningVote($client);

        // Act
        $this->ballots()->abandon($ballotId, $this->organizerId());

        // Assert
        $page = $this->get($client, '/en/event/' . $eventId, self::HOST);
        self::assertCount(
            1,
            $page->filter('a[href$="/item-ballot/create/' . $eventId . '/' . self::ITEM_TYPE . '"]'),
            'with no vote running the slot offers to start one',
        );
    }

    public function testAStewardOpensAVoteOnTheTermsTheFormCollects(): void
    {
        // Arrange
        $client = static::createClient();
        $this->login($client);
        $eventId = $this->anyEventId($client, self::HOST);
        $candidates = static::getContainer()->get(Candidates::class)->itemIdsFor(self::ITEM_TYPE);
        self::assertGreaterThan(1, count($candidates), 'the fixtures need at least two film candidates');

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
        $itemIds = array_slice(static::getContainer()->get(Candidates::class)->itemIdsFor(self::ITEM_TYPE), 0, 2);
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
     * @return array{int, int}
     */
    private function eventWithARunningVote(KernelBrowser $client): array
    {
        $crawler = $this->get($client, '/en/events', self::HOST);
        foreach ($crawler->filter('a[href^="/en/event/"]')->extract(['href']) as $href) {
            if (preg_match('#/event/(\d+)$#', (string) $href, $match) !== 1) {
                continue;
            }

            $page = $this->get($client, (string) $href, self::HOST);
            $links = $page->filter('a[href*="/item-ballot/"]:not([href*="/create/"])')->extract(['href']);
            if ($links !== [] && preg_match('#/item-ballot/(\d+)$#', (string) $links[0], $ballot) === 1) {
                return [(int) $match[1], (int) $ballot[1]];
            }
        }

        self::fail('No listed event shows a running film vote; the cinephile fixture is required');
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
