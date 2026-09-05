<?php declare(strict_types=1);

namespace Module\Ballot\Tests\Functional;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotRequest;
use Module\Ballot\Contract\BallotStatus;
use Module\Ballot\Contract\Candidate;
use Module\Ballot\Contract\SettlementMode;
use Module\Ballot\Internal\Cron\SettleDueBallotsCron;
use Module\Ballot\Internal\Entity\Ballot;
use Module\Ballot\Internal\Notification\OpenBallotNotificationProvider;
use Module\Ballot\Tests\Stub\RecordingSettlementListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class DeadlineTest extends KernelTestCase
{
    public function testAClearWinnerIsSettledAutomaticallyOnTheDeadline(): void
    {
        // Arrange
        self::bootKernel();
        $ballots = self::getContainer()->get(BallotInterface::class);
        $listener = self::getContainer()->get(RecordingSettlementListener::class);
        [$first, $second] = $this->voterIds();
        $id = $this->overdueBallot(RecordingSettlementListener::PURPOSE, SettlementMode::Automatic, [$first => ['b'], $second => ['b']]);

        // Act
        $result = $this->runCron();

        // Assert
        self::assertStringContainsString('1 settled', $result);
        self::assertSame(BallotStatus::Settled, $ballots->view($id, $first)?->status);
        self::assertCount(1, $listener->settled);
        self::assertSame('b', $listener->settled[0]->winningKey);
    }

    public function testATieIsTalliedButLeftForAPerson(): void
    {
        // Arrange
        self::bootKernel();
        $ballots = self::getContainer()->get(BallotInterface::class);
        [$first, $second] = $this->voterIds();
        $id = $this->overdueBallot('test.tie', SettlementMode::Automatic, [$first => ['a'], $second => ['b']]);

        // Act
        $result = $this->runCron();

        // Assert
        self::assertStringContainsString('1 left for a person', $result);
        $view = $ballots->view($id, $first);
        self::assertSame(BallotStatus::Tallied, $view?->status);
        self::assertTrue($view->isTied());
        self::assertSame(['a', 'b'], $view->tiedKeys);
    }

    public function testAConfirmedBallotWaitsForAPersonEvenWithAClearWinner(): void
    {
        // Arrange
        self::bootKernel();
        $ballots = self::getContainer()->get(BallotInterface::class);
        [$first, $second] = $this->voterIds();
        $id = $this->overdueBallot('test.confirmed', SettlementMode::Confirmed, [$first => ['a'], $second => ['a']]);

        // Act
        $this->runCron();

        // Assert
        $view = $ballots->view($id, $first);
        self::assertSame(BallotStatus::Tallied, $view?->status);
        self::assertSame('a', $view->winningKey);
    }

    public function testTheBellStaysSilentUntilAMemberHasSomethingToVoteOn(): void
    {
        // Arrange
        self::bootKernel();
        $provider = self::getContainer()->get(OpenBallotNotificationProvider::class);
        $ballots = self::getContainer()->get(BallotInterface::class);
        $member = $this->em()->getRepository(User::class)->find($this->voterIds()[0]);
        self::assertInstanceOf(User::class, $member);
        $before = $provider->getNotifications($member);

        // Act
        $ballots->open($this->request('test.bell', SettlementMode::Automatic, $this->voterIds()[0]));

        // Assert
        $after = $provider->getNotifications($member);
        self::assertCount(count($before) + 1, $after, 'an open ballot adds exactly one bell item');
    }

    /**
     * @param array<int, list<string>> $votes
     */
    private function overdueBallot(string $purpose, SettlementMode $mode, array $votes): int
    {
        $ballots = self::getContainer()->get(BallotInterface::class);
        $id = $ballots->open($this->request($purpose, $mode, (int) array_key_first($votes)));

        foreach ($votes as $userId => $keys) {
            $ballots->cast($id, $userId, $keys);
        }

        $this->em()->createQuery('UPDATE ' . Ballot::class . ' b SET b.deadline = :past WHERE b.id = :id')
            ->setParameter('past', new DateTimeImmutable('-1 hour'))
            ->setParameter('id', $id)
            ->execute();
        $this->em()->clear();

        return $id;
    }

    private function request(string $purpose, SettlementMode $mode, int $openedBy): BallotRequest
    {
        return new BallotRequest(
            $purpose,
            [new Candidate('a', 'Alpha'), new Candidate('b', 'Beta')],
            new DateTimeImmutable('+7 days'),
            $openedBy,
            null,
            settlementMode: $mode,
        );
    }

    private function runCron(): string
    {
        $output = new BufferedOutput();
        $result = self::getContainer()->get(SettleDueBallotsCron::class)->runCronTask($output);

        return $result->message . "\n" . $output->fetch();
    }

    /**
     * @return list<int>
     */
    private function voterIds(): array
    {
        $rows = $this->em()->createQueryBuilder()
            ->select('u.id')
            ->from(User::class, 'u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(2)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row): int => (int) $row['id'], $rows);
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
