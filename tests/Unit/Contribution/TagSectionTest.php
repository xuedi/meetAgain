<?php declare(strict_types=1);

namespace Tests\Unit\Contribution;

use App\Contribution\TagSection;
use App\Entity\User;
use App\Item\Tag\TaggableTypeProviderInterface;
use App\Item\Tag\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class TagSectionTest extends TestCase
{
    public function testTheListingIsOneEntryPerTaggableType(): void
    {
        // Arrange
        $section = $this->section(['film' => 'item.type_films', 'book' => 'item.type_books']);

        // Act
        $entries = $section->listForMember(new User());

        // Assert
        self::assertSame(['film', 'book'], array_map(static fn($entry): int|string => $entry->id, $entries));
        self::assertSame(['item.type_films', 'item.type_books'], array_map(static fn($entry): string => $entry->label, $entries));
    }

    public function testATypeWhosePluginIsOffIsNotOffered(): void
    {
        // Arrange
        $section = $this->section([]);

        // Act & Assert
        self::assertSame([], $section->listForMember(new User()));
        self::assertFalse($section->mayTouch(new User(), 'film'), 'the tag registry already drops a type whose plugin is off');
    }

    public function testAnActiveTypeIsTouchable(): void
    {
        // Arrange
        $section = $this->section(['film' => 'item.type_films']);

        // Act & Assert
        self::assertTrue($section->mayTouch(new User(), 'film'));
    }

    public function testAStewardIsSentToTheirOwnEditorInstead(): void
    {
        // Arrange
        $section = $this->section(['film' => 'item.type_films'], isSteward: true);

        // Act & Assert
        self::assertSame([], $section->listForMember(new User()));
        self::assertFalse($section->mayTouch(new User(), 'film'));
    }

    /**
     * @param array<string, string> $types type key => label key
     */
    private function section(array $types, bool $isSteward = false): TagSection
    {
        $providers = [];
        foreach ($types as $typeKey => $labelKey) {
            $provider = $this->createStub(TaggableTypeProviderInterface::class);
            $provider->method('getTypeKey')->willReturn($typeKey);
            $provider->method('getLabelKey')->willReturn($labelKey);
            $providers[] = $provider;
        }

        $registry = $this->createStub(TypeRegistry::class);
        $registry->method('all')->willReturn($providers);
        $registry->method('has')->willReturnCallback(static fn(string $typeKey): bool => isset($types[$typeKey]));

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn($isSteward);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new TagSection($registry, $security, $translator);
    }
}
