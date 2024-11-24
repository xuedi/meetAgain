<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class Text implements BlockType
{
    public string $title;
    public string $content;

    private function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public static function fromJson(array $data): self
    {
        return new self($data['title'], $data['content']);
    }

    public function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::Text;
    }
}
