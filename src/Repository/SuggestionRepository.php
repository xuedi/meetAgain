<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Suggestion;
use App\Entity\User;
use App\Enum\SuggestionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Suggestion> */
class SuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suggestion::class);
    }

    /** @return list<Suggestion> */
    public function findPending(): array
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', SuggestionStatus::Pending)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Suggestion> */
    public function findPendingByProposer(User $proposer): array
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.proposedBy = :proposer')
            ->setParameter('status', SuggestionStatus::Pending)
            ->setParameter('proposer', $proposer)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingForTargetType(string $targetType): int
    {
        return (int) $this
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->andWhere('s.targetType = :targetType')
            ->setParameter('status', SuggestionStatus::Pending)
            ->setParameter('targetType', $targetType)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removeForTargetType(string $targetType): void
    {
        $this
            ->createQueryBuilder('s')
            ->delete()
            ->where('s.targetType = :targetType')
            ->setParameter('targetType', $targetType)
            ->getQuery()
            ->execute();
    }
}
