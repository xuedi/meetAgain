<?php declare(strict_types=1);

namespace App\Entity;

enum EventTypes: int
{
    case All = 1;
    case Regular = 2;
    case Outdoor = 3;
    case Dinner = 4;
}
