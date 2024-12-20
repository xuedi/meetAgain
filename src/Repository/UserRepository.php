<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getFollowers(User $user, bool $excludeFriends = false): array
    {
        if ($excludeFriends === false) {
            return $user->getFollowers()->toArray();
        }
        $friendLessList = [];
        $following = $user->getFollowing();
        foreach ($user->getFollowers() as $follower) {
            if (!$following->contains($follower)) {
                $friendLessList[] = $follower;
            }
        }

        return $friendLessList;
    }

    public function getFollowing(User $user, bool $excludeFriends = false): array
    {
        if ($excludeFriends === false) {
            return $user->getFollowing()->toArray();
        }
        $friendLessList = [];
        $followers = $user->getFollowers();
        foreach ($user->getFollowing() as $follow) {
            if (!$followers->contains($follow)) {
                $friendLessList[] = $follow;
            }
        }

        return $friendLessList;
    }

    public function getFriends(User $user): array
    {
        $friendList = [];
        $followers = $user->getFollowers();
        foreach ($user->getFollowing() as $follow) {
            if ($followers->contains($follow)) {
                $friendList[] = $follow;
            }
        }

        return $friendList;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
