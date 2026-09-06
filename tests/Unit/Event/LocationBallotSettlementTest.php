<?php declare(strict_types=1);

namespace Tests\Unit\Event;

use App\Event\BallotLocationChoice;
use App\Event\LocationBallotSettlement;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Service\Event\AttendeeUpdateNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotOutcome;
use Module\Ballot\Contract\BallotStatus;
use Module\Ballot\Contract\BallotSubject;
use PHPUnit\Framework\TestCase;

class LocationBallotSettlementTest extends TestCase
{
    public function testOnlyTheVenuePurposeIsClaimed(): void
    {
        // Arrange
        $settlement = $this->settlement($this->createStub(EventRepository::class));

        // Act & Assert
        self::assertTrue($settlement->supports(BallotLocationChoice::PURPOSE));
        self::assertFalse($settlement->supports('event.item.film'));
        self::assertFalse($settlement->supports('photo.contest'));
    }

    public function testLeavingItUndecidedNeverLooksUpTheEvent(): void
    {
        // Arrange
        $events = $this->createMock(EventRepository::class);
        $events->expects(self::never())->method('find');

        // Act & Assert
        $this->settlement($events)->settled($this->outcome(BallotLocationChoice::CANDIDATE_UNDECIDED));
    }

    public function testATieNeverLooksUpTheEvent(): void
    {
        // Arrange
        $events = $this->createMock(EventRepository::class);
        $events->expects(self::never())->method('find');

        // Act & Assert
        $this->settlement($events)->settled($this->outcome(null, ['4', '9']));
    }

    /**
     * @param list<string> $tiedKeys
     */
    private function outcome(?string $winningKey, array $tiedKeys = []): BallotOutcome
    {
        return new BallotOutcome(
            1,
            BallotLocationChoice::PURPOSE,
            BallotStatus::Settled,
            $winningKey,
            $tiedKeys,
            new BallotSubject(BallotLocationChoice::SUBJECT_TYPE, 7),
            null,
            3,
        );
    }

    private function settlement(EventRepository $events): LocationBallotSettlement
    {
        return new LocationBallotSettlement(
            $this->createStub(EntityManagerInterface::class),
            $events,
            $this->createStub(LocationRepository::class),
            $this->createStub(AttendeeUpdateNotifier::class),
        );
    }
}
