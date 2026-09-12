<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\Service;

use App\Item\Tag\FacetService;
use App\Item\Tag\TagService;
use App\Repository\ItemTagAssignmentRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Entity\TrainerDay;
use Plugin\Glossary\Enum\AnswerMode;
use Plugin\Glossary\Enum\Direction;
use Plugin\Glossary\Enum\Mode;
use Plugin\Glossary\Enum\Scope;
use Plugin\Glossary\Repository\TrainerCardRepository;
use Plugin\Glossary\Repository\TrainerDayRepository;
use Plugin\Glossary\Service\AnswerMatcher;
use Plugin\Glossary\Service\ConfigService;
use Plugin\Glossary\Service\GlossaryService;
use Plugin\Glossary\Service\SchedulerInterface;
use Plugin\Glossary\Service\TrainerService;
use Plugin\Glossary\ValueObject\Config;
use Plugin\Glossary\ValueObject\TrainerSession;
use ReflectionProperty;

class TrainerServiceTest extends TestCase
{
    private const int USER = 7;

    /** @var array<int, string> */
    private array $definitions = [];

    public function testAReviewSessionAddsOnlyWhatIsLeftOfTheDaysNewWordAllowance(): void
    {
        // Arrange
        $now = new DateTimeImmutable('2026-09-12 10:00');
        $day = new TrainerDay(self::USER, $now);
        for ($started = 0; $started < 8; ++$started) {
            $day->record(true, true);
        }
        $cardRepo = $this->createStub(TrainerCardRepository::class);
        $cardRepo->method('dueGlossaryIds')->willReturn([5]);
        $cardRepo->method('scheduledGlossaryIds')->willReturn([5]);
        $dayRepo = $this->createStub(TrainerDayRepository::class);
        $dayRepo->method('findDay')->willReturn($day);
        $service = $this->service([1, 2, 3, 4, 5, 6], $cardRepo, $dayRepo);

        // Act
        $session = $service->start(self::USER, Scope::Selection, [], Mode::Review, Direction::TermToDefinition, AnswerMode::Flip, 20, $now);

        // Assert
        self::assertSame([5, 1, 2], $session->queue);
    }

    public function testMultipleChoiceOffersTheAnswerOnceAmongDistinctOptions(): void
    {
        // Arrange
        $this->definitions = [1 => 'to love', 2 => 'eight', 3 => 'eight', 4 => 'dog', 5 => 'I; me'];
        $service = $this->service([1, 2, 3, 4, 5], $this->createStub(TrainerCardRepository::class), $this->createStub(TrainerDayRepository::class));
        $session = new TrainerSession(Scope::Selection, [], Mode::Practice, Direction::TermToDefinition, AnswerMode::Choice, [1], 42, 1);

        // Act
        $question = $service->question($session, self::USER);

        // Assert
        $choices = array_column($question->choices ?? [], 'text', 'id');
        self::assertCount(TrainerService::CHOICE_COUNT, $choices);
        self::assertSame('to love', $choices[1] ?? null);
        self::assertSame(array_values($choices), array_values(array_unique($choices)));
    }

    /** @param list<int> $visible */
    private function service(array $visible, TrainerCardRepository $cardRepo, TrainerDayRepository $dayRepo): TrainerService
    {
        $glossaryService = $this->createStub(GlossaryService::class);
        $glossaryService->method('getVisibleIds')->willReturn($visible);
        $glossaryService->method('getByIds')->willReturnCallback(fn(array $ids): array => array_combine($ids, array_map($this->entry(...), $ids)));
        $glossaryService->method('definitionFor')->willReturnCallback(fn(Glossary $entry): string => $this->definitions[(int) $entry->getId()] ?? 'meaning');

        $facetService = $this->createStub(FacetService::class);
        $facetService->method('withoutFacets')->willReturnCallback(static fn(callable $callback): mixed => $callback());

        $configService = $this->createStub(ConfigService::class);
        $configService->method('getConfig')->willReturn(Config::fromArray(['trainerEnabled' => true, 'newCardsPerDay' => 10]));

        $tagService = $this->createStub(TagService::class);
        $tagService->method('getDepths')->willReturn([]);

        return new TrainerService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $dayRepo,
            $glossaryService,
            $configService,
            $this->createStub(SchedulerInterface::class),
            new AnswerMatcher(),
            $tagService,
            $facetService,
            $this->createStub(ItemTagAssignmentRepository::class),
        );
    }

    private function entry(int $id): Glossary
    {
        $entry = new Glossary()->setPhrase('word ' . $id);
        new ReflectionProperty(Glossary::class, 'id')->setValue($entry, $id);

        return $entry;
    }
}
