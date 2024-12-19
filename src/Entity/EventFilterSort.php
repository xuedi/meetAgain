<?php declare(strict_types=1);

namespace App\Entity;

enum EventFilterSort: string
{
    case OldToNew = 'asc';
    case NewToOld = 'desc';
}
