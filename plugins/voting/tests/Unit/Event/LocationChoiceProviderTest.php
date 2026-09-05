<?php declare(strict_types=1);

namespace Plugin\Voting\Tests\Unit\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Publisher\PluginSettings\Resolver;
use PHPUnit\Framework\TestCase;
use Plugin\Voting\Entity\Poll;
use Plugin\Voting\Event\LocationChoiceProvider;
use Plugin\Voting\Repository\PollRepository;
use Plugin\Voting\Service\ConfigService;
use Plugin\Voting\Service\PollService;
use Plugin\Voting\ValueObject\Config;
use Plugin\Voting\ValueObject\PollClosure;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

class LocationChoiceProviderTest extends TestCase
{
    public function testTheEntryIsOfferedOnlyWhenThereIsSomethingToChooseBetween(): void
    {
        // Arrange
        $withBallot = $this->makeProvider(candidates: [4, 9]);
        $withOneVenue = $this->makeProvider(candidates: [4]);

        // Act & Assert
        self::assertTrue($withBallot->isAvailableFor(null));
        self::assertFalse($withOneVenue->isAvailableFor(null));
    }

    public function testTheEntryIsSelectedWhileAVenuePollRuns(): void
    {
        // Arrange
        $provider = $this->makeProvider(activePoll: new Poll());

        // Act & Assert
        self::assertTrue($provider->isActiveFor($this->event(5)));
    }

    public function testChoosingStartsOneVenuePoll(): void
    {
        // Arrange
        $pollService = $this->createMock(PollService::class);
        $pollService->method('getCandidateItemIds')->willReturn([4, 9]);
        $pollService->expects(self::once())->method('create')->with(
            self::anything(),
            'location',
            [4, 9],
            7,
            42,
        );
        $provider = $this->makeProvider(pollService: $pollService);

        // Act
        $provider->choose($this->event(5));
    }

    public function testAnEventThatAlreadyHasAVenuePollGetsNoSecondOne(): void
    {
        // Arrange
        $pollService = $this->createMock(PollService::class);
        $pollService->method('getCandidateItemIds')->willReturn([4, 9]);
        $pollService->expects(self::never())->method('create');
        $provider = $this->makeProvider(pollService: $pollService, activePoll: new Poll());

        // Act
        $provider->choose($this->event(5));
    }

    public function testReleasingClosesTheRunningPollWithoutCommittingAnOutcome(): void
    {
        // Arrange
        $poll = new Poll();
        $pollService = $this->createMock(PollService::class);
        $pollService->method('getCandidateItemIds')->willReturn([4, 9]);
        $pollService->expects(self::once())->method('close')->with($poll)->willReturn(new PollClosure(null, []));
        $pollService->expects(self::never())->method('commitOutcome');
        $provider = $this->makeProvider(pollService: $pollService, activePoll: $poll);

        // Act
        $provider->release($this->event(5));
    }

    public function testReleasingAnEventWithoutAPollDoesNothing(): void
    {
        // Arrange
        $pollService = $this->createMock(PollService::class);
        $pollService->method('getCandidateItemIds')->willReturn([4, 9]);
        $pollService->expects(self::never())->method('close');
        $provider = $this->makeProvider(pollService: $pollService);

        // Act
        $provider->release($this->event(5));
    }

    /** @param list<int> $candidates */
    private function makeProvider(
        array $candidates = [4, 9],
        ?Poll $activePoll = null,
        ?PollService $pollService = null,
    ): LocationChoiceProvider {
        if ($pollService === null) {
            $pollService = $this->createStub(PollService::class);
            $pollService->method('getCandidateItemIds')->willReturn($candidates);
        }

        $pollRepo = $this->createStub(PollRepository::class);
        $pollRepo->method('findActiveForEventAndType')->willReturn($activePoll);

        $resolver = $this->createStub(Resolver::class);
        $resolver->method('resolve')->willReturn(new Config()->setDefaultDurationDays(7));

        $user = new User();
        new ReflectionProperty(User::class, 'id')->setValue($user, 42);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new LocationChoiceProvider($pollService, $pollRepo, new ConfigService($resolver), $security);
    }

    private function event(int $id): Event
    {
        $event = new Event();
        new ReflectionProperty(Event::class, 'id')->setValue($event, $id);

        return $event;
    }
}
