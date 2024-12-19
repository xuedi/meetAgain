<?php declare(strict_types=1);

namespace App\Entity;

enum EventFilterTime: int
{
    case All = 1;
    case Past = 2;
    case Future = 3;
}
