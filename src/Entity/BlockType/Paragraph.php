<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class Paragraph implements BlockType
{
    public string $title;
    public string $content;

    private function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public static function fromJson(array $json): self
    {
        return new self($json['title'], $json['content']);
    }

    public static function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::Paragraph;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
