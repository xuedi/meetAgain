<?php declare(strict_types=1);

namespace Plugin\Glossary\ValueObject;

use Plugin\Glossary\Enum\DuplicatePolicy;

final readonly class ImportMapping
{
    public function __construct(
        public int $termColumn,
        public int $definitionColumn,
        public ?int $secondaryColumn,
        public ?int $tagsColumn,
        public string $language,
        public ?int $targetTagId,
        public ?string $newTagLabel,
        public DuplicatePolicy $duplicatePolicy,
    ) {}
}
