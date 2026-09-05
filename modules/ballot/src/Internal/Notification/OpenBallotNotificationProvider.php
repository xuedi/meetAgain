<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Notification;

use App\Entity\User;
use App\Service\Notification\User\NotificationItem;
use App\Service\Notification\User\NotificationProviderInterface;
use Module\Ballot\Contract\BallotInterface;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class OpenBallotNotificationProvider implements NotificationProviderInterface
{
    public function __construct(
        private BallotInterface $ballots,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function getNotifications(User $user): array
    {
        $open = $this->ballots->countOpenFor((int) $user->getId());
        if ($open === 0) {
            return [];
        }

        return [new NotificationItem(
            label: $this->translator->trans('ballot.notification_open', ['%count%' => $open]),
            icon: 'fa-check-to-slot',
            route: 'app_ballot_index',
        )];
    }
}
