<?php declare(strict_types=1);

namespace Plugin\Voting\Tests\Functional;

use App\Entity\Event;
use App\Entity\Location;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Voting\Entity\Poll;
use Plugin\Voting\Service\PollService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VenuePollTest extends KernelTestCase
{
    public function testTheWinningVenueIsWrittenOntoTheEventOnClose(): void
    {
        // Arrange
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $pollService = $container->get(PollService::class);

        $event = $this->futureEvent($em);
        $venues = $em->getRepository(Location::class)->findBy([], ['id' => 'ASC'], 2);
        self::assertCount(2, $venues, 'two venue fixtures are required');
        $winner = $venues[1];
        $originalVenue = $event->getLocation();

        $poll = $pollService->create($event, 'location', [(int) $venues[0]->getId(), (int) $winner->getId()], 7, 1);
        $pollService->castVote(2, $poll, [(int) $winner->getId()]);

        // Act
        $closure = $pollService->close($poll);
        self::assertSame((int) $winner->getId(), $closure->winningItemId);
        $pollService->commitOutcome($poll, (int) $closure->winningItemId);

        // Assert
        $em->refresh($event);
        self::assertSame($winner->getId(), $event->getLocation()?->getId());
        self::assertNotSame($originalVenue?->getId(), $event->getLocation()?->getId());
    }

    public function testATieLeavesTheEventVenueUntouched(): void
    {
        // Arrange
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $pollService = $container->get(PollService::class);

        $event = $this->futureEvent($em);
        $venues = $em->getRepository(Location::class)->findBy([], ['id' => 'ASC'], 2);
        $originalVenueId = $event->getLocation()?->getId();

        $poll = $pollService->create($event, 'location', [(int) $venues[0]->getId(), (int) $venues[1]->getId()], 7, 1);
        $pollService->castVote(2, $poll, [(int) $venues[0]->getId()]);
        $pollService->castVote(3, $poll, [(int) $venues[1]->getId()]);

        // Act
        $closure = $pollService->close($poll);

        // Assert
        self::assertNull($closure->winningItemId);
        self::assertCount(2, $closure->tiedItemIds);
        $em->refresh($event);
        self::assertSame($originalVenueId, $event->getLocation()?->getId());
    }

    public function testAPollWinnerIsRecordedEvenWithoutAnEvent(): void
    {
        // Arrange
        self::bootKernel();
        $container = self::getContainer();
        $pollService = $container->get(PollService::class);
        $poll = $pollService->create(null, 'location', [999101, 999102], 7, 1);

        // Act
        $pollService->commitOutcome($poll, 999101);

        // Assert
        self::assertSame(999101, $poll->getWinningItemId());
        self::assertNull($poll->getEvent());
    }

    private function futureEvent(EntityManagerInterface $em): Event
    {
        $event = $em->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.start > :now')
            ->andWhere('e.location IS NOT NULL')
            ->setParameter('now', new DateTime())
            ->orderBy('e.start', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$event instanceof Event) {
            self::fail('Required future event fixture with a venue missing');
        }

        return $event;
    }
}
