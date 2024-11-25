<?php declare(strict_types=1);

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

class Image implements BlockType
{
    public string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function fromJson(array $json): self
    {
        return new self($json['id']);
    }

    public static function getType(): CmsBlockTypes
    {
        return CmsBlockTypes::Image;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
