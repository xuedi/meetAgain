<?php declare(strict_types=1);

namespace App\Review;

use App\Entity\User;
use App\Repository\UserRepository;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\ElectorateProviderInterface;
use Override;

final readonly class FieldBallotElectorate implements ElectorateProviderInterface
{
    public function __construct(
        private ChangeProposalService $proposals,
        private UserRepository $users,
    ) {}

    #[Override]
    public function supports(string $purpose): bool
    {
        return $purpose === FieldBallotService::PURPOSE;
    }

    #[Override]
    public function mayVote(int $userId, ?BallotSubject $subject): bool
    {
        if ($subject === null) {
            return false;
        }

        $user = $this->users->find($userId);

        return $user instanceof User && $this->proposals->canProposeTarget($subject->type, $subject->id, $user);
    }
}
