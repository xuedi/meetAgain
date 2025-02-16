<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\UserActivity;
use App\Entity\UserStatus;
use App\Service\ActivityService;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() !== UserStatus::Active) {
            throw new CustomUserMessageAccountStatusException('The user is not anymore or not jet active');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->activityService->log(UserActivity::Login, $user, []);
    }
}