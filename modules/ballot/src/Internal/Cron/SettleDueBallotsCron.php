<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Cron;

use App\CronTaskInterface;
use App\Enum\CronTaskStatus;
use App\ValueObject\CronTaskResult;
use DateTimeImmutable;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\SettlementMode;
use Module\Ballot\Internal\Entity\Ballot;
use Module\Ballot\Internal\Repository\BallotRepository;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

readonly class SettleDueBallotsCron implements CronTaskInterface
{
    public function __construct(
        private BallotRepository $ballots,
        private BallotInterface $ballotService,
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function getIdentifier(): string
    {
        return 'ballot.settle-due';
    }

    #[Override]
    public function runCronTask(OutputInterface $output): CronTaskResult
    {
        $settled = 0;
        $waiting = 0;
        $errors = 0;

        foreach ($this->ballots->findDue(new DateTimeImmutable('now')) as $ballot) {
            try {
                if ($this->process($ballot, $output)) {
                    $settled++;
                    continue;
                }
                $waiting++;
            } catch (Throwable $e) {
                $errors++;
                $this->logger->error('SettleDueBallotsCron: failed to settle ballot', [
                    'ballot_id' => $ballot->getId(),
                    'error' => $e->getMessage(),
                ]);
                $output->writeln(sprintf('SettleDueBallotsCron: error on ballot %d: %s', (int) $ballot->getId(), $e->getMessage()));
            }
        }

        return new CronTaskResult(
            $this->getIdentifier(),
            $errors > 0 ? CronTaskStatus::error : CronTaskStatus::ok,
            sprintf('%d settled, %d left for a person, %d errors', $settled, $waiting, $errors),
        );
    }

    private function process(Ballot $ballot, OutputInterface $output): bool
    {
        $ballotId = (int) $ballot->getId();
        $outcome = $this->ballotService->tally($ballotId);

        if ($ballot->getSettlementMode() !== SettlementMode::Automatic || $outcome->winningKey === null) {
            $output->writeln(sprintf('SettleDueBallotsCron: ballot %d tallied, awaiting a decision', $ballotId));

            return false;
        }

        $this->ballotService->settle($ballotId, $outcome->winningKey);
        $output->writeln(sprintf('SettleDueBallotsCron: settled ballot %d on %s', $ballotId, $outcome->winningKey));

        return true;
    }
}
