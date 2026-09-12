<?php declare(strict_types=1);

namespace Plugin\Glossary\ValueObject;

use Plugin\Glossary\Entity\Glossary;

final readonly class Question
{
    /**
     * @param list<array{id: int, text: string}> $choices
     */
    public function __construct(
        public Glossary $entry,
        public string $prompt,
        public string $answer,
        public ?string $promptLanguage,
        public ?string $answerLanguage,
        public ?string $secondary,
        public array $choices,
        public bool $marked,
    ) {}
}
