<?php declare(strict_types=1);

namespace App\Entity;

enum EventFilterRsvp: string
{
    case All = 'all';
    case My = 'my';
    case Friends = 'friends';
}
