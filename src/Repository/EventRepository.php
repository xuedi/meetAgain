<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Event> */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getUpcomingEvents(int $number = 3): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM App\Entity\Event e
            WHERE e.start > :date
            ORDER BY e.start ASC'
        )->setParameter('date', new DateTime())->setMaxResults($number);

        return $query->getResult();
    }

    public function findAllRecurring()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $query = $qb->select('e')
            ->from(Event::class, 'e')
            ->where($qb->expr()->isNotNull('e.recurringRule'))
            ->orderBy('e.start', 'ASC')
            ->getQuery();

        return $query->getResult();
    }
}
