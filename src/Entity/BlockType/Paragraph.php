<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class Paragraph implements BlockType
{
    private function __construct(public string $title, public string $content)
    {
    }

    #[\Override]
    public static function fromJson(array $json): self
    {
        return new self($json['title'], $json['content']);
    }

    #[\Override]
    public static function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::Paragraph;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
