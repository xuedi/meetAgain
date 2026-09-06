<?php declare(strict_types=1);

namespace App\Contribution;

final readonly class Entry
{
    public function __construct(
        public int|string $id,
        public string $label,
        public ?string $sublabel = null,
    ) {}
}
