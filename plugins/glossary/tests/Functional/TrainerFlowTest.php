<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Functional;

use App\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Entity\TrainerCard;
use Plugin\Glossary\Entity\TrainerDay;
use Plugin\Glossary\Enum\CardState;
use Plugin\Glossary\Enum\Direction;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrainerFlowTest extends WebTestCase
{
    private const string MEMBER_EMAIL = 'Phoenix.Baker@example.org';
    private const string OTHER_MEMBER_EMAIL = 'Adem.Lane@example.org';
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';

    public function testAReviewSessionRunsEndToEndWithoutJavaScript(): void
    {
        // Arrange
        $client = static::createClient();
        $member = $this->user($client, self::MEMBER_EMAIL);
        $client->loginUser($member);
        $this->startSession($client, ['trainer_setup[mode]' => 'review', 'trainer_setup[answerMode]' => 'flip', 'trainer_setup[size]' => '5']);

        // Act
        $answered = 0;
        while ($answered < 10) {
            $crawler = $client->request('GET', '/en/glossary/trainer/card?reveal=1', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
            if ($client->getResponse()->isRedirect()) {
                break;
            }
            $client->submit($crawler->filter('button[name="grade"][value="3"]')->form(), serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
            $this->assertResponseRedirects();
            ++$answered;
        }
        $client->request('GET', '/en/glossary/trainer/results', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertSame(5, $answered);
        $cards = $this->em($client)->getRepository(TrainerCard::class)->findBy(['userId' => $member->getId()]);
        self::assertCount(5, $cards);
        self::assertTrue(array_all($cards, static fn(TrainerCard $card): bool => $card->getState() === CardState::Review && $card->getDueAt() !== null));
        $day = $this->em($client)->getRepository(TrainerDay::class)->findOneBy(['userId' => $member->getId()]);
        self::assertSame(5, $day?->getReviewed());
        self::assertSame(5, $day?->getNewStarted());
    }

    public function testAGetOnTheAnswerRouteRecordsNothing(): void
    {
        // Arrange
        $client = static::createClient();
        $member = $this->user($client, self::MEMBER_EMAIL);
        $client->loginUser($member);
        $this->startSession($client, ['trainer_setup[mode]' => 'review', 'trainer_setup[answerMode]' => 'flip']);

        // Act
        $client->request('GET', '/en/glossary/trainer/answer?grade=3', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        self::assertFalse($client->getResponse()->isSuccessful());
        self::assertFalse($client->getResponse()->isRedirect('/en/glossary/trainer/card'));
        self::assertNull($this->em($client)->getRepository(TrainerDay::class)->findOneBy(['userId' => $member->getId()]));
    }

    public function testAPracticeSessionLeavesTheScheduleAloneButCountsTheAnswer(): void
    {
        // Arrange
        $client = static::createClient();
        $member = $this->user($client, self::MEMBER_EMAIL);
        $client->loginUser($member);
        $dueAt = new DateTimeImmutable('+3 days')->setTime(9, 0);
        $card = $this->scheduledCard($client, $member, $this->entry($client, '随便'), $dueAt, marked: true);
        $cardId = (int) $card->getId();
        $this->startSession($client, ['trainer_setup[scope]' => 'starred', 'trainer_setup[mode]' => 'practice', 'trainer_setup[answerMode]' => 'flip']);

        // Act
        $crawler = $client->request('GET', '/en/glossary/trainer/card?reveal=1', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $client->submit($crawler->filter('button[name="grade"][value="1"]')->form(), serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects('/en/glossary/trainer/results');
        $em = $this->em($client);
        $em->clear();
        $reloaded = $em->getRepository(TrainerCard::class)->find($cardId);
        self::assertInstanceOf(TrainerCard::class, $reloaded);
        self::assertSame($dueAt->format('Y-m-d H:i'), $reloaded->getDueAt()?->format('Y-m-d H:i'));
        self::assertSame(6, $reloaded->getIntervalDays());
        self::assertSame(2500, $reloaded->getEasePermille());
        self::assertSame(CardState::Review, $reloaded->getState());
        self::assertSame(2, $reloaded->getTimesSeen());
        self::assertSame(1, $reloaded->getTimesCorrect());
    }

    public function testAMemberSeesAndChangesOnlyTheirOwnProgress(): void
    {
        // Arrange
        $client = static::createClient();
        $owner = $this->user($client, self::MEMBER_EMAIL);
        $entry = $this->entry($client, '随便');
        $card = $this->scheduledCard($client, $owner, $entry, new DateTimeImmutable('+3 days'));
        $cardId = (int) $card->getId();
        $client->loginUser($this->user($client, self::OTHER_MEMBER_EMAIL));

        // Act
        $crawler = $client->request('GET', '/en/glossary/' . $entry->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $token = (string) $crawler->filter('a[href$="/glossary/trainer/mark/' . $entry->getId() . '"]')->attr('data-csrf-token');
        $client->request('POST', '/en/glossary/trainer/mark/' . $entry->getId(), ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects('/en/glossary/' . $entry->getId());
        self::assertStringContainsString('You have not trained this word yet.', (string) $crawler->html());
        self::assertStringNotContainsString('Seen 2 times', (string) $crawler->html());
        $em = $this->em($client);
        $em->clear();
        self::assertFalse($em->getRepository(TrainerCard::class)->find($cardId)?->isMarked());
    }

    public function testTheSidebarOffersTrainingForTheCurrentSelection(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        self::assertCount(1, $crawler->filter('[data-glossary-training] [data-glossary-train]'));
        self::assertCount(1, $crawler->filter('[data-glossary-training] [data-glossary-export]'));
        self::assertCount(1, $crawler->filter('[data-glossary-training] [data-glossary-progress]'));
        self::assertCount(0, $crawler->filter('[data-item-list-body] a[href*="/glossary/export"]'));
    }

    public function testTheSidebarLinksCarryTheSelectedTag(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $list = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $facet = (string) $list->filter('a[data-item-facet][href*="tag"]')->first()->attr('href');

        // Act
        $filtered = $client->request('GET', $facet, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $query = (string) parse_url($facet, PHP_URL_QUERY);
        self::assertNotSame('', $query);
        self::assertSame($query, parse_url((string) $filtered->filter('[data-glossary-train]')->attr('href'), PHP_URL_QUERY));
        self::assertSame($query, parse_url((string) $filtered->filter('[data-glossary-export]')->attr('href'), PHP_URL_QUERY));
    }

    public function testAFlipGradeShowsNoFeedbackOnTheNextCard(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->startSession($client, ['trainer_setup[mode]' => 'review', 'trainer_setup[answerMode]' => 'flip', 'trainer_setup[size]' => '5']);
        $crawler = $client->request('GET', '/en/glossary/trainer/card?reveal=1', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $client->submit($crawler->filter('button[name="grade"][value="3"]')->form(), serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Act
        $next = $client->request('GET', '/en/glossary/trainer/card', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(0, $next->filter('[data-trainer-feedback]'));
    }

    public function testStoppingASessionForgetsItAndReturnsToTheList(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->startSession($client, ['trainer_setup[mode]' => 'review', 'trainer_setup[answerMode]' => 'flip']);
        $crawler = $client->request('GET', '/en/glossary/trainer/card', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Act
        $client->submit($crawler->filter('form[action$="/glossary/trainer/stop"]')->form(), serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects('/en/glossary');
        $client->request('GET', '/en/glossary/trainer/card', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects('/en/glossary/trainer');
    }

    public function testTheProgressPageCountsOnlyTheMembersOwnCards(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::OTHER_MEMBER_EMAIL));

        // Act
        $own = $client->request('GET', '/en/glossary/trainer/progress', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $other = $client->request('GET', '/en/glossary/trainer/progress', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertSame('3', trim($own->filter('[data-progress-due]')->text()));
        self::assertSame('3', trim($own->filter('[data-progress-started]')->text()));
        self::assertSame('0', trim($other->filter('[data-progress-due]')->text()));
        self::assertSame('0', trim($other->filter('[data-progress-started]')->text()));
    }

    /** @param array<string, string> $fields */
    private function startSession(KernelBrowser $client, array $fields): void
    {
        $crawler = $client->request('GET', '/en/glossary/trainer', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="trainer_setup"]')->form();
        foreach ($fields as $name => $value) {
            $form[$name] = $value;
        }
        $client->submit($form, serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects('/en/glossary/trainer/card');
    }

    private function scheduledCard(KernelBrowser $client, User $member, Glossary $entry, DateTimeImmutable $dueAt, bool $marked = false): TrainerCard
    {
        $card = new TrainerCard((int) $member->getId(), $entry, Direction::TermToDefinition, new DateTimeImmutable('-20 days'));
        $card->setState(CardState::Review)
            ->setRepetitions(2)
            ->setIntervalDays(6)
            ->setEasePermille(2500)
            ->setDueAt($dueAt)
            ->setMarked($marked)
            ->recordAnswer(true, new DateTimeImmutable()->sub(new DateInterval('P3D')));

        $em = $this->em($client);
        $em->persist($card);
        $em->flush();

        return $card;
    }

    private function entry(KernelBrowser $client, string $phrase): Glossary
    {
        $entry = $this->em($client)->getRepository(Glossary::class)->findOneBy(['phrase' => $phrase]);
        if (!$entry instanceof Glossary) {
            self::fail('Required glossary fixture entry missing: ' . $phrase);
        }

        return $entry;
    }

    private function user(KernelBrowser $client, string $email): User
    {
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . $email);
        }

        return $user;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
