<?php declare(strict_types=1);

namespace App\Contribution;

final readonly class Draft
{
    /**
     * @param array<string, mixed> $formOptions
     */
    public function __construct(
        public mixed $data,
        public string $label,
        public string $introKey,
        public array $formOptions = [],
    ) {}
}
