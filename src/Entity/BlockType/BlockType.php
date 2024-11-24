<?php

namespace App\Entity\BlockType;

use App\Entity\CmsBlockTypes;

interface BlockType
{
    public static function fromJson(array $json): self;
    public function getType(): CmsBlockTypes;
}
