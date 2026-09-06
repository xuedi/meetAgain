<?php declare(strict_types=1);

namespace Tests\Functional\Review;

use App\Entity\ChangeProposal;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\FieldResolution;
use App\Review\ChangeProposalException;
use App\Review\ChangeProposalService;
use App\Review\EventChangeTarget;
use App\Review\FieldBallotService;
use App\Review\FieldChange;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\TallyMode;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FieldBallotFlowTest extends WebTestCase
{
    private const string STEWARD_EMAIL = 'Admin@example.org';
    private const string FIELD = 'title_en';

    public function testTheBallotOffersEveryProposedValuePlusLeavingItAlone(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Alpha Title');
        $this->proposeTitle($client, $proposers[1], $eventId, 'Beta Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);

        // Act
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        // Assert
        $view = $this->ballots()->view($ballotId, (int) $steward->getId());
        self::assertNotNull($view);
        self::assertCount(3, $view->candidates, 'both proposed values plus leaving it alone');
        $labels = array_map(static fn(object $candidate): string => $candidate->label, $view->candidates);
        self::assertContains('Alpha Title', $labels);
        self::assertContains('Beta Title', $labels);
        self::assertNotNull($view->title, 'the ballot page says what is being decided');
    }

    public function testTwoProposalsForTheSameValueCollapseIntoOneCandidate(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Agreed Title');
        $this->proposeTitle($client, $proposers[1], $eventId, 'Agreed Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);

        // Act
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        // Assert
        $view = $this->ballots()->view($ballotId, (int) $steward->getId());
        self::assertCount(2, $view->candidates ?? [], 'the shared value appears once, beside leaving it alone');
    }

    public function testConfirmingWritesTheWinnerAndDeniesTheLoser(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $loser = $this->proposeTitle($client, $proposers[0], $eventId, 'Losing Title');
        $winner = $this->proposeTitle($client, $proposers[1], $eventId, 'Winning Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);
        $winningKey = $this->keyLabelled($ballotId, (int) $steward->getId(), 'Winning Title');

        $this->vote($client, $proposers[0], $ballotId, $winningKey);
        $this->vote($client, $proposers[1], $ballotId, $winningKey);
        $this->closeTheDeadline($client, $ballotId);

        // Act
        $this->login($client, self::STEWARD_EMAIL);
        $this->fieldBallots()->confirm($ballotId, $steward);

        // Assert
        self::assertSame('Winning Title', $this->reloadEvent($client, $eventId)->getTitle('en'));
        self::assertSame(FieldResolution::Applied, $this->resolutionOf($client, $winner));
        self::assertSame(FieldResolution::Denied, $this->resolutionOf($client, $loser), 'the value nobody chose is closed out');
    }

    public function testATieIsLeftForAPersonAndWritesNothing(): void
    {
        // Arrange
        $client = static::createClient();
        $event = $this->multilingualEvent($client);
        $eventId = (int) $event->getId();
        $titleBefore = $event->getTitle('en');
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'One Title');
        $this->proposeTitle($client, $proposers[1], $eventId, 'Other Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        $this->vote($client, $proposers[0], $ballotId, $this->keyLabelled($ballotId, (int) $steward->getId(), 'One Title'));
        $this->vote($client, $proposers[1], $ballotId, $this->keyLabelled($ballotId, (int) $steward->getId(), 'Other Title'));
        $this->closeTheDeadline($client, $ballotId);
        $this->login($client, self::STEWARD_EMAIL);

        // Act
        $refused = null;
        try {
            $this->fieldBallots()->confirm($ballotId, $steward);
        } catch (ChangeProposalException $e) {
            $refused = $e->getMessage();
        }

        // Assert
        self::assertNotNull($refused, 'a tie has no winner to apply');
        self::assertSame($titleBefore, $this->reloadEvent($client, $eventId)->getTitle('en'));
        self::assertTrue(
            $this
                ->ballots()
                ->view($ballotId, (int) $steward->getId())
                ?->isTied(),
        );
    }

    public function testARunningBallotCannotBeConfirmedEarly(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Early Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        // Act
        $this->expectException(ChangeProposalException::class);

        // Assert
        $this->fieldBallots()->confirm($ballotId, $steward);
    }

    public function testOnlyMembersWhoMayProposeOnTheTargetMayVote(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Electorate Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        // Act
        $client->loginUser($proposers[0]);
        $proposerMayVote = $this->ballots()->mayVote($ballotId, (int) $proposers[0]->getId());

        // Assert
        self::assertTrue($proposerMayVote, 'whoever may propose a change may vote on which one wins');
    }

    public function testTheReviewPageOffersTheBallotAndThenShowsItRunning(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Page Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);
        $url = '/en/review/proposals/' . EventChangeTarget::TARGET_TYPE . '/' . $eventId;

        // Act
        $before = $client->request('GET', $url);
        $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);
        $after = $client->request('GET', $url);

        // Assert
        self::assertCount(1, $before->filter('button[data-ballot-field="' . self::FIELD . '"]'), 'the steward is offered the ballot');
        self::assertCount(1, $before->filter('#field-ballot-modal input[name="ballot_terms[durationDays]"]'), 'the overlay that collects the terms is on the page');
        self::assertCount(0, $after->filter('button[data-ballot-field="' . self::FIELD . '"]'), 'the offer is gone while the members decide');
        self::assertStringContainsString('Members are deciding', $after->text());
    }

    public function testTheTermsTheStewardSetsReachTheOpenedBallot(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Termed Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);

        // Act
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward, [
            'durationDays' => 2,
            'tallyMode' => TallyMode::Approval->value,
        ]);

        // Assert
        $view = $this->ballots()->view($ballotId, (int) $steward->getId());
        self::assertNotNull($view);
        self::assertSame(TallyMode::Approval, $view->tallyMode);
        self::assertSame(
            (new DateTimeImmutable('+2 days'))->format('Y-m-d'),
            $view->deadline->format('Y-m-d'),
            'the deadline is the one the steward asked for, not the seven-day default',
        );
    }

    public function testAnUntouchedModalStillOpensASevenDaySingleChoiceVote(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Default Title');
        $steward = $this->login($client, self::STEWARD_EMAIL);

        // Act
        $ballotId = $this->fieldBallots()->open(EventChangeTarget::TARGET_TYPE, $eventId, self::FIELD, $steward);

        // Assert
        $view = $this->ballots()->view($ballotId, (int) $steward->getId());
        self::assertNotNull($view);
        self::assertSame(TallyMode::Single, $view->tallyMode, 'a field ballot still picks one value by default');
        self::assertSame((new DateTimeImmutable('+7 days'))->format('Y-m-d'), $view->deadline->format('Y-m-d'));
    }

    public function testTheMemberFacingFormStillRendersItsOwnPendingProposal(): void
    {
        // Arrange
        $client = static::createClient();
        $eventId = (int) $this->multilingualEvent($client)->getId();
        $proposers = $this->proposers($client, $eventId);
        $this->proposeTitle($client, $proposers[0], $eventId, 'Member Facing Title');
        $client->loginUser($proposers[0]);

        // Act
        $crawler = $client->request('GET', '/en/contribute/event/' . $eventId);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Member Facing Title', $crawler->text(), 'the proposer sees what they proposed');
    }

    private function keyLabelled(int $ballotId, int $viewerId, string $label): string
    {
        foreach ($this->ballots()->view($ballotId, $viewerId)->candidates ?? [] as $candidate) {
            if ($candidate->label === $label) {
                return $candidate->key;
            }
        }

        self::fail('No candidate labelled ' . $label);
    }

    private function vote(KernelBrowser $client, User $voter, int $ballotId, string $key): void
    {
        $client->loginUser($voter);
        $this->ballots()->cast($ballotId, (int) $voter->getId(), [$key]);
    }

    private function closeTheDeadline(KernelBrowser $client, int $ballotId): void
    {
        $this->em($client)->getConnection()->executeStatement('UPDATE mod_ballot SET deadline = :past WHERE id = :id', [
            'past' => new DateTimeImmutable('-1 hour')->format('Y-m-d H:i:s'),
            'id' => $ballotId,
        ]);
        $this->em($client)->clear();
    }

    private function proposeTitle(KernelBrowser $client, User $proposer, int $eventId, string $title): int
    {
        $client->loginUser($proposer);
        $proposal = $this->proposals()->propose(EventChangeTarget::TARGET_TYPE, $eventId, $proposer, [
            new FieldChange(self::FIELD, $this->reloadEvent($client, $eventId)->getTitle('en'), $title),
        ]);
        self::assertInstanceOf(ChangeProposal::class, $proposal);

        return (int) $proposal->getId();
    }

    private function resolutionOf(KernelBrowser $client, int $proposalId): ?FieldResolution
    {
        $this->em($client)->clear();
        $proposal = $this->em($client)->getRepository(ChangeProposal::class)->find($proposalId);
        self::assertInstanceOf(ChangeProposal::class, $proposal);

        return $proposal->getChange(self::FIELD)->resolution;
    }

    /**
     * @return array{User, User}
     */
    private function proposers(KernelBrowser $client, int $eventId): array
    {
        $found = [];
        foreach ($this->candidateMembers($client) as $member) {
            $client->loginUser($member);
            if (!$this->proposals()->canProposeTarget(EventChangeTarget::TARGET_TYPE, $eventId, $member)) {
                continue;
            }

            $found[] = $member;
            if (count($found) === 2) {
                return [$found[0], $found[1]];
            }
        }

        self::fail('Required fixture: two members who may propose a change to event ' . $eventId);
    }

    /**
     * @return list<User>
     */
    private function candidateMembers(KernelBrowser $client): array
    {
        return $this
            ->em($client)
            ->createQuery('SELECT u FROM ' . User::class . ' u WHERE u.status = 2 ORDER BY u.id ASC')
            ->setMaxResults(40)
            ->getResult();
    }

    private function multilingualEvent(KernelBrowser $client): Event
    {
        $events = $this
            ->em($client)
            ->createQuery('SELECT e FROM ' . Event::class . ' e JOIN e.translations t GROUP BY e.id HAVING COUNT(t.id) > 1 ORDER BY e.id ASC')
            ->setMaxResults(1)
            ->getResult();
        if ($events === []) {
            self::fail('Required multilingual event fixture missing');
        }

        return $events[0];
    }

    private function reloadEvent(KernelBrowser $client, int $id): Event
    {
        $event = $this->em($client)->getRepository(Event::class)->find($id);
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

    private function fieldBallots(): FieldBallotService
    {
        return static::getContainer()->get(FieldBallotService::class);
    }

    private function ballots(): BallotInterface
    {
        return static::getContainer()->get(BallotInterface::class);
    }

    private function proposals(): ChangeProposalService
    {
        return static::getContainer()->get(ChangeProposalService::class);
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
