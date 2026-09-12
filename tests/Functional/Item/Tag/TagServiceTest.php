<?php declare(strict_types=1);

namespace Tests\Functional\Item\Tag;

use App\Entity\ItemTag;
use App\Entity\ItemTagAssignment;
use App\Enum\ItemAction;
use App\Item\Tag\AssignmentClosure;
use App\Item\Tag\TagService;
use App\Item\Tag\TypeRegistry;
use App\Repository\ItemTagAssignmentRepository;
use App\Repository\ItemTagRepository;
use App\Service\Config\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TagServiceTest extends KernelTestCase
{
    private const string TYPE = 'tagprobe';

    private EntityManagerInterface $em;
    private ItemTagRepository $tagRepo;
    private ItemTagAssignmentRepository $assignmentRepo;
    private AssignmentClosure $closure;
    private TagService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->tagRepo = $container->get(ItemTagRepository::class);
        $this->assignmentRepo = $container->get(ItemTagAssignmentRepository::class);

        $registry = $this->createStub(TypeRegistry::class);
        $registry->method('has')->willReturn(true);

        $this->closure = new AssignmentClosure($this->em, $this->tagRepo, $this->assignmentRepo);
        $this->service = new TagService(
            $this->em,
            $this->tagRepo,
            $this->assignmentRepo,
            $this->closure,
            $registry,
            $container->get(LanguageService::class),
            [],
            [],
            [],
            [],
        );
    }

    public function testTaggingWithASubTagPersistsEveryAncestorAlongsideIt(): void
    {
        // Arrange
        [$root, $branch, $leaf] = $this->branch();

        // Act
        $this->service->setTags(self::TYPE, 5001, [(int) $leaf->getId()]);

        // Assert
        $stored = $this->service->getTagIds(self::TYPE, 5001);
        sort($stored);
        $expected = [(int) $root->getId(), (int) $branch->getId(), (int) $leaf->getId()];
        sort($expected);
        static::assertSame($expected, $stored);
    }

    public function testAnUnknownTagIdIsDroppedOnWrite(): void
    {
        // Arrange
        [$root] = $this->branch();

        // Act
        $this->service->setTags(self::TYPE, 5002, [(int) $root->getId(), 987654]);

        // Assert
        static::assertSame([(int) $root->getId()], $this->service->getTagIds(self::TYPE, 5002));
    }

    public function testBadgesShowTheLeafMostTagsOnly(): void
    {
        // Arrange
        [, , $leaf] = $this->branch();
        $this->service->setTags(self::TYPE, 5003, [(int) $leaf->getId()]);

        // Act
        $labels = $this->service->getLabels(self::TYPE, 5003, 'en');

        // Assert
        static::assertSame(['Chicken'], $labels);
    }

    public function testTheVocabularyIsOrderedDepthFirst(): void
    {
        // Arrange
        $this->branch();
        $other = $this->tag('Fish');

        // Act
        $ordered = array_map(
            static fn(ItemTag $tag): string => $tag->getLabels()['en'],
            $this->service->getVocabulary(self::TYPE),
        );

        // Assert
        static::assertSame(['Meat', 'Poultry', 'Chicken', 'Fish'], $ordered);
        static::assertSame('Fish', $other->getLabels()['en']);
    }

    public function testDeletingATagTakesItsBranchAndItsAssignmentsWithIt(): void
    {
        // Arrange
        [$root, $branch, $leaf] = $this->branch();
        $this->service->setTags(self::TYPE, 5004, [(int) $leaf->getId()]);

        // Act
        $this->service->deleteTag($branch);

        // Assert
        static::assertSame([(int) $root->getId()], $this->service->getTagIds(self::TYPE, 5004));
        static::assertCount(1, $this->tagRepo->findForType(self::TYPE));
    }

    public function testSavingTheEditorRowsCreatesRenamesAndDeletes(): void
    {
        // Arrange
        [$root] = $this->branch();

        // Act
        $this->service->saveVocabulary(self::TYPE, [
            ['id' => (string) $root->getId(), 'parent' => '', 'labels' => ['en' => 'Protein']],
            ['id' => 'n1', 'parent' => (string) $root->getId(), 'labels' => ['en' => 'Tofu']],
        ]);

        // Assert
        $labels = array_map(static fn(ItemTag $tag): string => $tag->getLabels()['en'], $this->service->getVocabulary(self::TYPE));
        static::assertSame(['Protein', 'Tofu'], $labels);
        static::assertSame(2, $this->service->getVocabulary(self::TYPE)[1]->getDepth());
    }

    public function testABlankRowIsNotStored(): void
    {
        // Arrange + Act
        $this->service->saveVocabulary(self::TYPE, [
            ['id' => '', 'parent' => '', 'labels' => ['en' => '  ']],
            ['id' => '', 'parent' => '', 'labels' => ['en' => 'Kept']],
        ]);

        // Assert
        static::assertCount(1, $this->service->getVocabulary(self::TYPE));
    }

    public function testDeletingAnItemSweepsItsAssignments(): void
    {
        // Arrange
        [$root] = $this->branch();
        $this->service->setTags(self::TYPE, 5005, [(int) $root->getId()]);

        // Act
        $this->service->onItemAction(ItemAction::Deleted, self::TYPE, 5005);

        // Assert
        static::assertSame([], $this->service->getTagIds(self::TYPE, 5005));
    }

    public function testDescendantIdsReachEveryLevelBelowATag(): void
    {
        // Arrange
        [$root, $branch, $leaf] = $this->branch();

        // Act
        $descendants = $this->tagRepo->descendantIds($root);
        sort($descendants);

        // Assert
        $expected = [(int) $branch->getId(), (int) $leaf->getId()];
        sort($expected);
        static::assertSame($expected, $descendants);
    }

    public function testMovingATagUnderAParentAddsTheParentToItsItems(): void
    {
        // Arrange
        $fun = $this->tag('Fun');
        $slang = $this->tag('Slang');
        $this->service->setTags(self::TYPE, 5101, [(int) $slang->getId()]);

        // Act
        $this->service->moveTag($slang, $fun);

        // Assert
        static::assertSame($this->ids($fun, $slang), $this->sortedTagIds(5101));
    }

    public function testMovingATagOutDropsOnlyTheAncestorsNothingElseImplies(): void
    {
        // Arrange
        [$root, $branch, $leaf] = $this->branch();
        $beef = $this->tag('Beef', $root);
        $this->service->setTags(self::TYPE, 5102, [(int) $leaf->getId()]);
        $this->service->setTags(self::TYPE, 5103, [(int) $leaf->getId(), (int) $beef->getId()]);
        $this->service->setTags(self::TYPE, 5104, [(int) $branch->getId()]);

        // Act
        $this->service->moveTag($leaf, null);

        // Assert
        static::assertSame($this->ids($leaf), $this->sortedTagIds(5102));
        static::assertSame($this->ids($root, $beef, $leaf), $this->sortedTagIds(5103));
        static::assertSame($this->ids($root, $branch), $this->sortedTagIds(5104));
    }

    public function testMovingATagLeavesAManagedAssignmentAlone(): void
    {
        // Arrange
        $fun = $this->tag('Fun');
        $slang = $this->tag('Slang');
        $managed = $this->tag('Events', managed: true);
        $this->service->setTags(self::TYPE, 5105, [(int) $slang->getId()]);
        $this->assign(5105, $managed);

        // Act
        $this->service->moveTag($slang, $fun);

        // Assert
        static::assertSame($this->ids($fun, $slang, $managed), $this->sortedTagIds(5105));
    }

    public function testReparentingInTheEditorRewritesTheClosureOfItsItems(): void
    {
        // Arrange
        $fun = $this->tag('Fun');
        $slang = $this->tag('Slang');
        $this->service->setTags(self::TYPE, 5106, [(int) $slang->getId()]);

        // Act
        $this->service->saveVocabulary(self::TYPE, [
            ['id' => (string) $fun->getId(), 'parent' => '', 'labels' => ['en' => 'Fun']],
            ['id' => (string) $slang->getId(), 'parent' => (string) $fun->getId(), 'labels' => ['en' => 'Slang']],
        ]);

        // Assert
        static::assertSame($this->ids($fun, $slang), $this->sortedTagIds(5106));
    }

    public function testMovingAMidLevelTagCarriesTheItemsBelowItAlong(): void
    {
        // Arrange
        [, $branch, $leaf] = $this->branch();
        $farm = $this->tag('Farm');
        $this->service->setTags(self::TYPE, 5108, [(int) $leaf->getId()]);

        // Act
        $this->service->moveTag($branch, $farm);

        // Assert
        static::assertSame($this->ids($farm, $branch, $leaf), $this->sortedTagIds(5108));
    }

    public function testMovingATagOutInTheEditorDropsTheOldParent(): void
    {
        // Arrange
        $fun = $this->tag('Fun');
        $slang = $this->tag('Slang', $fun);
        $this->service->setTags(self::TYPE, 5109, [(int) $slang->getId()]);

        // Act
        $this->service->saveVocabulary(self::TYPE, [
            ['id' => (string) $fun->getId(), 'parent' => '', 'labels' => ['en' => 'Fun']],
            ['id' => (string) $slang->getId(), 'parent' => '', 'labels' => ['en' => 'Slang']],
        ]);

        // Assert
        static::assertSame($this->ids($slang), $this->sortedTagIds(5109));
    }

    public function testAMoveLeavesItemsOutsideTheMovedBranchUntouched(): void
    {
        // Arrange
        [, , $leaf] = $this->branch();
        $this->assign(5110, $leaf);
        $fun = $this->tag('Fun');
        $slang = $this->tag('Slang');

        // Act
        $this->service->moveTag($slang, $fun);

        // Assert
        static::assertSame($this->ids($leaf), $this->sortedTagIds(5110));
    }

    public function testMovingATagDeeperWhileDeletingItsChildKeepsTheNewAncestors(): void
    {
        // Arrange
        $kingdom = $this->tag('Kingdom');
        $phylum = $this->tag('Phylum', $kingdom);
        $class = $this->tag('Class', $phylum);
        $order = $this->tag('Order', $class);
        $family = $this->tag('Family');
        $genus = $this->tag('Genus', $family);
        $this->service->setTags(self::TYPE, 5111, [(int) $genus->getId()]);

        // Act
        $this->service->saveVocabulary(self::TYPE, [
            ['id' => (string) $kingdom->getId(), 'parent' => '', 'labels' => ['en' => 'Kingdom']],
            ['id' => (string) $phylum->getId(), 'parent' => (string) $kingdom->getId(), 'labels' => ['en' => 'Phylum']],
            ['id' => (string) $class->getId(), 'parent' => (string) $phylum->getId(), 'labels' => ['en' => 'Class']],
            ['id' => (string) $order->getId(), 'parent' => (string) $class->getId(), 'labels' => ['en' => 'Order']],
            ['id' => (string) $family->getId(), 'parent' => (string) $order->getId(), 'labels' => ['en' => 'Family']],
        ]);

        // Assert
        static::assertSame($this->ids($kingdom, $phylum, $class, $order, $family), $this->sortedTagIds(5111));
    }

    public function testRestoringTheClosureAddsMissingAncestorsAndChangesNothingOnASecondRun(): void
    {
        // Arrange
        [$root, $branch, $leaf] = $this->branch();
        $this->assign(5107, $leaf);

        // Act
        $this->closure->restore(self::TYPE);
        $this->closure->restore(self::TYPE);

        // Assert
        static::assertSame($this->ids($root, $branch, $leaf), $this->sortedTagIds(5107));
    }

    /** @return array{ItemTag, ItemTag, ItemTag} */
    private function branch(): array
    {
        $root = $this->tag('Meat');
        $branch = $this->tag('Poultry', $root);
        $leaf = $this->tag('Chicken', $branch);

        return [$root, $branch, $leaf];
    }

    private function tag(string $label, ?ItemTag $parent = null, bool $managed = false): ItemTag
    {
        $tag = new ItemTag();
        $tag->setItemType(self::TYPE);
        $tag->setLabels(['en' => $label]);
        $tag->setParent($parent);
        $tag->setManaged($managed);
        $tag->setPosition($this->tagRepo->nextPosition(self::TYPE));
        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    private function assign(int $itemId, ItemTag $tag): void
    {
        $this->em->persist(new ItemTagAssignment()->setItemType(self::TYPE)->setItemId($itemId)->setTag($tag));
        $this->em->flush();
    }

    /** @return list<int> */
    private function ids(ItemTag ...$tags): array
    {
        $ids = array_map(static fn(ItemTag $tag): int => (int) $tag->getId(), $tags);
        sort($ids);

        return $ids;
    }

    /** @return list<int> */
    private function sortedTagIds(int $itemId): array
    {
        $ids = $this->service->getTagIds(self::TYPE, $itemId);
        sort($ids);

        return $ids;
    }
}
