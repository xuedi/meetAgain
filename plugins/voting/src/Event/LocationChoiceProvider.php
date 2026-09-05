<?php declare(strict_types=1);

namespace Plugin\Voting\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Event\LocationChoiceProviderInterface;
use Override;
use Plugin\Voting\Entity\Poll;
use Plugin\Voting\Outcome\LocationOutcome;
use Plugin\Voting\Repository\PollRepository;
use Plugin\Voting\Service\ConfigService;
use Plugin\Voting\Service\PollService;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class LocationChoiceProvider implements LocationChoiceProviderInterface
{
    public const string VALUE = 'voting:location_poll';

    private const int MINIMUM_CANDIDATES = 2;

    public function __construct(
        private PollService $pollService,
        private PollRepository $pollRepo,
        private ConfigService $config,
        private Security $security,
    ) {}

    #[Override]
    public function getPluginKey(): string
    {
        return 'voting';
    }

    #[Override]
    public function getValue(): string
    {
        return self::VALUE;
    }

    #[Override]
    public function getLabelKey(): string
    {
        return 'voting_attach.venue_decided_by_vote';
    }

    #[Override]
    public function isAvailableFor(?Event $event): bool
    {
        return count($this->pollService->getCandidateItemIds(LocationOutcome::ITEM_TYPE)) >= self::MINIMUM_CANDIDATES;
    }

    #[Override]
    public function isActiveFor(Event $event): bool
    {
        return $this->activePoll($event) !== null;
    }

    #[Override]
    public function choose(Event $event): void
    {
        $user = $this->security->getUser();
        if ($this->activePoll($event) !== null || !$user instanceof User) {
            return;
        }

        $this->pollService->create(
            $event,
            LocationOutcome::ITEM_TYPE,
            $this->pollService->getCandidateItemIds(LocationOutcome::ITEM_TYPE),
            $this->config->getConfig()->getDefaultDurationDays(),
            (int) $user->getId(),
        );
    }

    #[Override]
    public function release(Event $event): void
    {
        $poll = $this->activePoll($event);
        if ($poll === null) {
            return;
        }

        $this->pollService->close($poll);
    }

    private function activePoll(Event $event): ?Poll
    {
        $eventId = $event->getId();
        if ($eventId === null) {
            return null;
        }

        return $this->pollRepo->findActiveForEventAndType($eventId, LocationOutcome::ITEM_TYPE);
    }
}
