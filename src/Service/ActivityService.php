<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Activity;
use App\Entity\User;
use App\Entity\UserActivity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class ActivityService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function log(UserActivity $type, User $user, string $message): void
    {
        $activity = new Activity();
        $activity->setCreatedAt(new DateTimeImmutable());
        $activity->setUser($user);
        $activity->setType($type);
        $activity->setMessage($message);

        $this->em->persist($activity);
        $this->em->flush();
    }
}
