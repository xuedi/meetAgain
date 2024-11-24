<?php declare(strict_types=1);

namespace App\Entity;

enum CmsBlockTypes: int
{
    case Headline = 1;
    case Text = 2;
    case Image = 3;
    case Video = 4;
    case Events = 5;
    case Gallery = 6;
    case TwoColumns = 7;
    case ThreeColumns = 8;
}
