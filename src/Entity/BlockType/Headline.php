<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class Headline implements BlockType
{
    public string $title;

    private function __construct(string $title)
    {
        $this->title = $title;
    }

    public static function fromJson(array $data): self
    {
        return new self($data['title']);
    }

    public function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::Headline;
    }
}
