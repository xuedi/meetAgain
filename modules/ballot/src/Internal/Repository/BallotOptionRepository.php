<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Module\Ballot\Internal\Entity\BallotOption;

/**
 * @extends ServiceEntityRepository<BallotOption>
 */
class BallotOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BallotOption::class);
    }
}
