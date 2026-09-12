<?php declare(strict_types=1);

namespace Plugin\Glossary\Localization;

use App\Localization\AbstractLocalizedRowSource;
use App\Localization\LocalizedContentRow;
use Override;
use Plugin\Glossary\Entity\Definition;
use Plugin\Glossary\Item\GlossaryTaggableTypeProvider;

final readonly class DefinitionSource extends AbstractLocalizedRowSource
{
    #[Override]
    public function getKey(): string
    {
        return 'glossary_definition';
    }

    #[Override]
    public function getLabelKey(): string
    {
        return 'glossary.localized_content_source';
    }

    #[Override]
    public function getOwnerType(): string
    {
        return GlossaryTaggableTypeProvider::ITEM_TYPE;
    }

    #[Override]
    public function findOutsideLocales(array $ownerIds, array $keepLocales): array
    {
        $rows = [];
        /** @var Definition $definition */
        foreach ($this->fetchEntities($ownerIds, $keepLocales) as $definition) {
            $entry = $definition->getGlossary();
            $rows[] = new LocalizedContentRow(
                sourceKey: $this->getKey(),
                ownerId: (int) $entry?->getId(),
                locale: (string) $definition->getLanguage(),
                ownerLabel: (string) $entry?->getPhrase(),
                preview: $this->preview($definition->getText()),
            );
        }

        return $rows;
    }

    #[Override]
    protected function getEntityClass(): string
    {
        return Definition::class;
    }

    #[Override]
    protected function getLocaleField(): string
    {
        return 'language';
    }

    #[Override]
    protected function getOwnerField(): string
    {
        return 'glossary';
    }
}
