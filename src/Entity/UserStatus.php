<?php declare(strict_types=1);

namespace App\Entity;

enum UserStatus: int
{
    case Registered = 0;
    case EmailVerified = 1;
    case Active = 2;
    case Blocked = 3;
    case Deleted = 4;
}
