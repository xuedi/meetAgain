<?php declare(strict_types=1);

namespace App\Event;

use App\Filter\Event\EventFilterService;
use Module\Ballot\Contract\VisibilityFilterInterface;
use Override;

final readonly class BallotVisibility implements VisibilityFilterInterface
{
    private const string SUBJECT_EVENT = 'event';

    public function __construct(
        private EventFilterService $eventFilter,
    ) {}

    #[Override]
    public function getPriority(): int
    {
        return 0;
    }

    #[Override]
    public function narrowVisibleBallotIds(string $purpose, array $ballots, ?int $viewerUserId): ?array
    {
        $visible = [];
        $judged = false;

        foreach ($ballots as $ballot) {
            $subject = $ballot->subject;
            if ($subject === null || $subject->type !== self::SUBJECT_EVENT) {
                $visible[] = $ballot->id;
                continue;
            }

            $judged = true;
            if ($this->eventFilter->isEventAccessible($subject->id)) {
                $visible[] = $ballot->id;
            }
        }

        return $judged ? $visible : null;
    }
}
