<?php declare(strict_types=1);

namespace App\Contribution;

final readonly class Section
{
    /**
     * @param list<Entry> $entries
     */
    public function __construct(
        public string $type,
        public string $labelKey,
        public string $icon,
        public array $entries,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
