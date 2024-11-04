<?php declare(strict_types=1);

namespace App\Entity;

enum EventIntervals: int
{
    case NonRecurring = 1;
    case Weekly = 2;
    case BiMonthly = 3;
    case Monthly = 4;
    case Quarterly = 5;
    case Yearly = 6;
}
