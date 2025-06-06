<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class EventTeaser implements BlockType
{
    private function __construct(
        public string $headline,
        public string $text,
    ) {
    }

    #[\Override]
    public static function fromJson(array $json): self
    {
        return new self(
            $json['headline'],
            $json['text'],
        );
    }

    #[\Override]
    public static function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::EventTeaser;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'headline' => $this->headline,
            'text' => $this->text,
        ];
    }
}
