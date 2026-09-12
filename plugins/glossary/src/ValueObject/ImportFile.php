<?php declare(strict_types=1);

namespace Plugin\Glossary\ValueObject;

final readonly class ImportFile
{
    /**
     * @param list<string>       $columns    one name per column, empty when the file names none
     * @param list<list<string>> $rows
     * @param list<string>       $globalTags tags the file applies to every row
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public ?int $tagsColumn,
        public array $globalTags,
        public bool $html,
    ) {}
}
